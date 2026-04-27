<?php

namespace App\Services;

use App\Models\DatabaseConnection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class DynamicDatabaseService
{
    public function setActiveConnection(string $type, string $runtimeName = 'dynamic_connection'): ?array
    {
        $connection = DatabaseConnection::where('connection_type', $type)
            ->where('status', 'active')
            ->where('is_active', true)
            ->first();

        if (!$connection) {
            return null;
        }

        Config::set("database.connections.{$runtimeName}", [
            'driver' => $connection->driver ?: 'pgsql',
            'host' => $connection->host,
            'port' => $connection->port,
            'database' => $connection->database_name,
            'username' => $connection->username,
            'password' => $connection->password,
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'schema' => $connection->schema_name ?: 'public',
            'search_path' => $connection->schema_name ?: 'public',
            'sslmode' => $connection->sslmode ?: 'disable',
        ]);

        DB::purge($runtimeName);

        return [
            'runtime_name' => $runtimeName,
            'connection' => $connection,
        ];
    }
}