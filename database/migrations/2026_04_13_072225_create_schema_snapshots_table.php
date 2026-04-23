<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('schema_snapshots')) {
            Schema::create('schema_snapshots', function (Blueprint $table) {
                $table->id();
                $table->string('target_name', 50);
                $table->string('database_name', 150);
                $table->string('schema_name', 150)->default('public');
                $table->json('snapshot_data');
                $table->text('notes')->nullable();
                $table->string('created_by', 100)->nullable();
                $table->timestamps();
            });

            return;
        }

        Schema::table('schema_snapshots', function (Blueprint $table) {
            if (! Schema::hasColumn('schema_snapshots', 'target_name')) {
                $table->string('target_name', 50)->nullable()->after('id');
            }

            if (! Schema::hasColumn('schema_snapshots', 'database_name')) {
                $table->string('database_name', 150)->nullable()->after('target_name');
            }

            if (! Schema::hasColumn('schema_snapshots', 'schema_name')) {
                $table->string('schema_name', 150)->nullable()->after('database_name');
            }

            if (! Schema::hasColumn('schema_snapshots', 'snapshot_data')) {
                $table->json('snapshot_data')->nullable()->after('schema_name');
            }

            if (! Schema::hasColumn('schema_snapshots', 'notes')) {
                $table->text('notes')->nullable()->after('snapshot_data');
            }

            if (! Schema::hasColumn('schema_snapshots', 'created_by')) {
                $table->string('created_by', 100)->nullable()->after('notes');
            }
        });
    }

    public function down(): void
    {
        //
    }
};