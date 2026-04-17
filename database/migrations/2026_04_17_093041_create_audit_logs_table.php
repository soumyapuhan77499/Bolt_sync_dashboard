<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_user_id')->nullable();
            $table->string('module_name', 100);   // connections / schema / backup / health / auth
            $table->string('action_name', 100);   // login / logout / save / test / create / restore
            $table->string('target_type', 100)->nullable();
            $table->string('target_id', 100)->nullable();
            $table->json('payload_json')->nullable();
            $table->string('ip_address', 100)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('admin_user_id')
                ->references('id')
                ->on('admin_users')
                ->nullOnDelete();

            $table->index('admin_user_id');
            $table->index('module_name');
            $table->index('action_name');
            $table->index('target_type');
            $table->index('target_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};