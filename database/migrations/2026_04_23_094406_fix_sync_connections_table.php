<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sync_connections', function (Blueprint $table) {
            if (!Schema::hasColumn('sync_connections', 'connection_type')) {
                $table->string('connection_type', 50)->nullable()->after('id');
            }

            if (!Schema::hasColumn('sync_connections', 'name')) {
                $table->string('name', 100)->nullable();
            }

            if (!Schema::hasColumn('sync_connections', 'host')) {
                $table->string('host', 255)->nullable();
            }

            if (!Schema::hasColumn('sync_connections', 'port')) {
                $table->integer('port')->nullable();
            }

            if (!Schema::hasColumn('sync_connections', 'database_name')) {
                $table->string('database_name', 255)->nullable();
            }

            if (!Schema::hasColumn('sync_connections', 'username')) {
                $table->string('username', 255)->nullable();
            }

            if (!Schema::hasColumn('sync_connections', 'password_encrypted')) {
                $table->text('password_encrypted')->nullable();
            }

            if (!Schema::hasColumn('sync_connections', 'schema_name')) {
                $table->string('schema_name', 100)->nullable();
            }

            if (!Schema::hasColumn('sync_connections', 'sslmode')) {
                $table->string('sslmode', 50)->nullable();
            }

            if (!Schema::hasColumn('sync_connections', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }

            if (!Schema::hasColumn('sync_connections', 'last_test_status')) {
                $table->string('last_test_status', 50)->nullable();
            }

            if (!Schema::hasColumn('sync_connections', 'last_test_message')) {
                $table->text('last_test_message')->nullable();
            }

            if (!Schema::hasColumn('sync_connections', 'last_tested_at')) {
                $table->timestamp('last_tested_at')->nullable();
            }

            if (!Schema::hasColumn('sync_connections', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }

            if (!Schema::hasColumn('sync_connections', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        //
    }
};