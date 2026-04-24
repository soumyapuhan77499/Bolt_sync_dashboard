<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->string('module_name', 100)->nullable();
                $table->string('action_name', 100)->nullable();
                $table->text('description')->nullable();
                $table->string('status', 50)->default('success');
                $table->string('admin_user_id', 100)->nullable();
                $table->string('admin_name', 150)->nullable();
                $table->string('ip_address', 100)->nullable();
                $table->text('user_agent')->nullable();
                $table->json('context')->nullable();
                $table->timestamp('logged_at')->nullable();
                $table->timestamps();
            });
        } else {
            Schema::table('audit_logs', function (Blueprint $table) {
                if (!Schema::hasColumn('audit_logs', 'module_name')) {
                    $table->string('module_name', 100)->nullable();
                }
                if (!Schema::hasColumn('audit_logs', 'action_name')) {
                    $table->string('action_name', 100)->nullable();
                }
                if (!Schema::hasColumn('audit_logs', 'description')) {
                    $table->text('description')->nullable();
                }
                if (!Schema::hasColumn('audit_logs', 'status')) {
                    $table->string('status', 50)->default('success');
                }
                if (!Schema::hasColumn('audit_logs', 'admin_user_id')) {
                    $table->string('admin_user_id', 100)->nullable();
                }
                if (!Schema::hasColumn('audit_logs', 'admin_name')) {
                    $table->string('admin_name', 150)->nullable();
                }
                if (!Schema::hasColumn('audit_logs', 'ip_address')) {
                    $table->string('ip_address', 100)->nullable();
                }
                if (!Schema::hasColumn('audit_logs', 'user_agent')) {
                    $table->text('user_agent')->nullable();
                }
                if (!Schema::hasColumn('audit_logs', 'context')) {
                    $table->json('context')->nullable();
                }
                if (!Schema::hasColumn('audit_logs', 'logged_at')) {
                    $table->timestamp('logged_at')->nullable();
                }
            });
        }

        if (!Schema::hasTable('app_settings')) {
            Schema::create('app_settings', function (Blueprint $table) {
                $table->id();
                $table->string('setting_key', 150)->unique();
                $table->string('setting_label', 150)->nullable();
                $table->string('setting_group', 100)->nullable();
                $table->text('setting_value')->nullable();
                $table->string('input_type', 50)->default('text');
                $table->boolean('is_editable')->default(true);
                $table->text('notes')->nullable();
                $table->string('updated_by', 100)->nullable();
                $table->timestamps();
            });
        } else {
            Schema::table('app_settings', function (Blueprint $table) {
                if (!Schema::hasColumn('app_settings', 'setting_key')) {
                    $table->string('setting_key', 150)->nullable();
                }
                if (!Schema::hasColumn('app_settings', 'setting_label')) {
                    $table->string('setting_label', 150)->nullable();
                }
                if (!Schema::hasColumn('app_settings', 'setting_group')) {
                    $table->string('setting_group', 100)->nullable();
                }
                if (!Schema::hasColumn('app_settings', 'setting_value')) {
                    $table->text('setting_value')->nullable();
                }
                if (!Schema::hasColumn('app_settings', 'input_type')) {
                    $table->string('input_type', 50)->default('text');
                }
                if (!Schema::hasColumn('app_settings', 'is_editable')) {
                    $table->boolean('is_editable')->default(true);
                }
                if (!Schema::hasColumn('app_settings', 'notes')) {
                    $table->text('notes')->nullable();
                }
                if (!Schema::hasColumn('app_settings', 'updated_by')) {
                    $table->string('updated_by', 100)->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        //
    }
};