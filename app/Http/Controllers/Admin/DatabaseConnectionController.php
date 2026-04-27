<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DatabaseConnection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class DatabaseConnectionController extends Controller
{
    public function index()
    {
        $connections = DatabaseConnection::orderBy('connection_type')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        return view('admin.database_connections.index', compact('connections'));
    }

    public function create()
    {
        return view('admin.database_connections.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'              => 'required|string|max:150',
            'connection_type'   => 'required|in:source,destination',
            'driver'            => 'required|string|max:50',
            'host'              => 'required|string|max:255',
            'port'              => 'required|integer|min:1|max:65535',
            'database_name'     => 'required|string|max:150',
            'username'          => 'required|string|max:150',
            'password'          => 'nullable|string',
            'schema_name'       => 'nullable|string|max:100',
            'sslmode'           => 'required|in:disable,allow,prefer,require,verify-ca,verify-full',
            'supabase_url'      => 'nullable|url|max:255',
            'supabase_anon_key' => 'nullable|string',
            'status'            => 'required|in:active,inactive',
            'notes'             => 'nullable|string',
        ], [
            'name.required'            => 'Connection name is required.',
            'connection_type.required' => 'Connection type is required.',
            'host.required'            => 'Database host is required.',
            'port.required'            => 'Database port is required.',
            'database_name.required'   => 'Database name is required.',
            'username.required'        => 'Database username is required.',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        DatabaseConnection::create([
            'name'              => $request->name,
            'connection_type'   => $request->connection_type,
            'driver'            => $request->driver ?: 'pgsql',
            'host'              => $request->host,
            'port'              => $request->port,
            'database_name'     => $request->database_name,
            'username'          => $request->username,
            'password'          => $request->password,
            'schema_name'       => $request->schema_name ?: 'public',
            'sslmode'           => $request->sslmode ?: 'disable',
            'supabase_url'      => $request->supabase_url,
            'supabase_anon_key' => $request->supabase_anon_key,
            'is_active'         => false,
            'status'            => $request->status,
            'notes'             => $request->notes,
        ]);

        return redirect()
            ->route('admin.database-connections.index')
            ->with('success', 'Database connection added successfully.');
    }

    public function activate(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $connection = DatabaseConnection::findOrFail($id);

            DatabaseConnection::where('connection_type', $connection->connection_type)
                ->update([
                    'is_active' => false,
                    'updated_at' => now(),
                ]);

            $connection->update([
                'is_active' => true,
                'status'    => 'active',
            ]);

            DB::commit();

            return back()->with('success', 'Database connection activated successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Database connection activation failed', [
                'connection_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Activation failed: ' . $e->getMessage());
        }
    }

    public function deactivate($id)
    {
        try {
            $connection = DatabaseConnection::findOrFail($id);

            $connection->update([
                'is_active' => false,
                'status'    => 'inactive',
            ]);

            return back()->with('success', 'Database connection deactivated successfully.');
        } catch (\Throwable $e) {
            Log::error('Database connection deactivate failed', [
                'connection_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Deactivate failed: ' . $e->getMessage());
        }
    }

    public function test($id)
    {
        $db = DatabaseConnection::findOrFail($id);

        config([
            'database.connections.temp_test_connection' => [
                'driver'         => $db->driver ?: 'pgsql',
                'host'           => $db->host,
                'port'           => (int) $db->port,
                'database'       => $db->database_name,
                'username'       => $db->username,
                'password'       => $db->password,
                'charset'        => 'utf8',
                'prefix'         => '',
                'prefix_indexes' => true,
                'schema'         => $db->schema_name ?: 'public',
                'search_path'    => $db->schema_name ?: 'public',
                'sslmode'        => $db->sslmode ?: 'disable',
            ],
        ]);

        DB::purge('temp_test_connection');

        try {
            $row = DB::connection('temp_test_connection')->selectOne("
                SELECT 
                    current_database() AS database_name,
                    current_user AS current_user,
                    inet_server_addr() AS server_ip,
                    inet_server_port() AS server_port
            ");

            DB::disconnect('temp_test_connection');

            return back()->with(
                'success',
                'Connected successfully. Database: ' .
                ($row->database_name ?? 'unknown') .
                ', User: ' .
                ($row->current_user ?? 'unknown')
            );
        } catch (\Throwable $e) {
            DB::disconnect('temp_test_connection');

            Log::error('Database connection test failed', [
                'connection_id' => $id,
                'host' => $db->host,
                'port' => $db->port,
                'database' => $db->database_name,
                'username' => $db->username,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Connection failed: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $connection = DatabaseConnection::findOrFail($id);

            if ($connection->is_active) {
                return back()->with('error', 'Active connection cannot be deleted. Deactivate it first.');
            }

            $connection->delete();

            return back()->with('success', 'Database connection deleted successfully.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Delete failed: ' . $e->getMessage());
        }
    }
}