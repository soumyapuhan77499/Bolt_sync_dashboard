<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DatabaseConnection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    public function activate(Request $request, $id)
    {
        $connection = DatabaseConnection::findOrFail($id);

        DatabaseConnection::where('connection_type', $connection->connection_type)
            ->update(['is_active' => false]);

        $connection->update([
            'is_active' => true,
            'status' => 'active',
        ]);

        return back()->with('success', 'Database connection activated successfully.');
    }

    public function deactivate($id)
    {
        $connection = DatabaseConnection::findOrFail($id);

        $connection->update([
            'is_active' => false,
            'status' => 'inactive',
        ]);

        return back()->with('success', 'Database connection deactivated successfully.');
    }

    public function test($id)
    {
        $db = DatabaseConnection::findOrFail($id);

        config([
            'database.connections.temp_test_connection' => [
                'driver' => $db->driver ?: 'pgsql',
                'host' => $db->host,
                'port' => $db->port,
                'database' => $db->database_name,
                'username' => $db->username,
                'password' => $db->password,
                'charset' => 'utf8',
                'prefix' => '',
                'prefix_indexes' => true,
                'schema' => $db->schema_name ?: 'public',
                'search_path' => $db->schema_name ?: 'public',
                'sslmode' => $db->sslmode ?: 'disable',
            ]
        ]);

        DB::purge('temp_test_connection');

        try {
            $row = DB::connection('temp_test_connection')->selectOne('select current_database() as db');
            DB::disconnect('temp_test_connection');

            return back()->with('success', 'Connected successfully to: ' . ($row->db ?? 'unknown'));
        } catch (\Throwable $e) {
            DB::disconnect('temp_test_connection');

            return back()->with('error', 'Connection failed: ' . $e->getMessage());
        }
    }
}