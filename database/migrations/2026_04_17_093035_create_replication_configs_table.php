<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('replication_configs', function (Blueprint $table) {
            if (!Schema::hasColumn('replication_configs', 'name')) {
                $table->string('name', 150)->nullable()->after('id');
            }

            if (!Schema::hasColumn('replication_configs', 'source_connection_type')) {
                $table->string('source_connection_type', 50)->nullable();
            }

            if (!Schema::hasColumn('replication_configs', 'destination_connection_type')) {
                $table->string('destination_connection_type', 50)->nullable();
            }

            if (!Schema::hasColumn('replication_configs', 'publication_name')) {
                $table->string('publication_name', 150)->nullable();
            }

            if (!Schema::hasColumn('replication_configs', 'subscription_name')) {
                $table->string('subscription_name', 150)->nullable();
            }

            if (!Schema::hasColumn('replication_configs', 'replication_mode')) {
                $table->string('replication_mode', 50)->nullable();
            }

            if (!Schema::hasColumn('replication_configs', 'source_schema_name')) {
                $table->string('source_schema_name', 100)->nullable();
            }

            if (!Schema::hasColumn('replication_configs', 'destination_schema_name')) {
                $table->string('destination_schema_name', 100)->nullable();
            }

            if (!Schema::hasColumn('replication_configs', 'source_tables')) {
                $table->text('source_tables')->nullable();
            }

            if (!Schema::hasColumn('replication_configs', 'sync_inserts')) {
                $table->boolean('sync_inserts')->default(true);
            }

            if (!Schema::hasColumn('replication_configs', 'sync_updates')) {
                $table->boolean('sync_updates')->default(true);
            }

            if (!Schema::hasColumn('replication_configs', 'sync_deletes')) {
                $table->boolean('sync_deletes')->default(false);
            }

            if (!Schema::hasColumn('replication_configs', 'status')) {
                $table->string('status', 50)->default('draft');
            }

            if (!Schema::hasColumn('replication_configs', 'last_message')) {
                $table->text('last_message')->nullable();
            }

            if (!Schema::hasColumn('replication_configs', 'notes')) {
                $table->text('notes')->nullable();
            }

            if (!Schema::hasColumn('replication_configs', 'started_at')) {
                $table->timestamp('started_at')->nullable();
            }

            if (!Schema::hasColumn('replication_configs', 'stopped_at')) {
                $table->timestamp('stopped_at')->nullable();
            }

            if (!Schema::hasColumn('replication_configs', 'last_checked_at')) {
                $table->timestamp('last_checked_at')->nullable();
            }

            if (!Schema::hasColumn('replication_configs', 'created_by')) {
                $table->string('created_by', 100)->nullable();
            }

            if (!Schema::hasColumn('replication_configs', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
        });
    }

    public function down(): void
    {
        //
    }
};