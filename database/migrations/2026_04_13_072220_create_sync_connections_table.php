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
        Schema::create('sync_connections', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('connection_type', 30); // source / destination
            $table->string('host', 255);
            $table->unsignedInteger('port')->default(5432);
            $table->string('database_name', 150);
            $table->string('username', 150);
            $table->longText('password_encrypted')->nullable();
            $table->string('schema_name', 100)->default('public');
            $table->string('sslmode', 50)->default('prefer');
            $table->boolean('is_active')->default(true);
            $table->string('last_test_status', 30)->nullable(); // success / failed
            $table->text('last_test_message')->nullable();
            $table->timestamp('last_tested_at')->nullable();
            $table->timestamps();

            $table->unique('connection_type');
            $table->index('is_active');
            $table->index('last_test_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_connections');
    }
};