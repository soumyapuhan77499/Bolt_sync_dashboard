<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('backup_runs')) {
            Schema::create('backup_runs', function (Blueprint $table) {
                $table->id();
                $table->string('backup_type', 100)->nullable();
                $table->string('target_name', 150)->nullable();
                $table->string('file_name', 255)->nullable();
                $table->text('file_path')->nullable();
                $table->string('status', 50)->default('pending');
                $table->text('message')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('ended_at')->nullable();
                $table->string('created_by', 100)->nullable();
                $table->timestamps();
            });
        } else {
            Schema::table('backup_runs', function (Blueprint $table) {
                if (!Schema::hasColumn('backup_runs', 'backup_type')) {
                    $table->string('backup_type', 100)->nullable();
                }
                if (!Schema::hasColumn('backup_runs', 'target_name')) {
                    $table->string('target_name', 150)->nullable();
                }
                if (!Schema::hasColumn('backup_runs', 'file_name')) {
                    $table->string('file_name', 255)->nullable();
                }
                if (!Schema::hasColumn('backup_runs', 'file_path')) {
                    $table->text('file_path')->nullable();
                }
                if (!Schema::hasColumn('backup_runs', 'status')) {
                    $table->string('status', 50)->default('pending');
                }
                if (!Schema::hasColumn('backup_runs', 'message')) {
                    $table->text('message')->nullable();
                }
                if (!Schema::hasColumn('backup_runs', 'started_at')) {
                    $table->timestamp('started_at')->nullable();
                }
                if (!Schema::hasColumn('backup_runs', 'ended_at')) {
                    $table->timestamp('ended_at')->nullable();
                }
                if (!Schema::hasColumn('backup_runs', 'created_by')) {
                    $table->string('created_by', 100)->nullable();
                }
            });
        }

        if (!Schema::hasTable('health_checks')) {
            Schema::create('health_checks', function (Blueprint $table) {
                $table->id();
                $table->string('target_name', 150)->nullable();
                $table->string('check_type', 100)->nullable();
                $table->string('status', 50)->default('pending');
                $table->text('message')->nullable();
                $table->timestamp('checked_at')->nullable();
                $table->timestamps();
            });
        } else {
            Schema::table('health_checks', function (Blueprint $table) {
                if (!Schema::hasColumn('health_checks', 'target_name')) {
                    $table->string('target_name', 150)->nullable();
                }
                if (!Schema::hasColumn('health_checks', 'check_type')) {
                    $table->string('check_type', 100)->nullable();
                }
                if (!Schema::hasColumn('health_checks', 'status')) {
                    $table->string('status', 50)->default('pending');
                }
                if (!Schema::hasColumn('health_checks', 'message')) {
                    $table->text('message')->nullable();
                }
                if (!Schema::hasColumn('health_checks', 'checked_at')) {
                    $table->timestamp('checked_at')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        //
    }
};