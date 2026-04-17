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
        Schema::create('health_checks', function (Blueprint $table) {
            $table->id();
            $table->string('target_name', 100);   // source_pg / destination_pg / admin_db
            $table->string('check_type', 100);    // connection / schema / replication / lag
            $table->string('status', 30)->default('pending'); // success / failed / warning
            $table->text('message')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();

            $table->index('target_name');
            $table->index('check_type');
            $table->index('status');
            $table->index('checked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('health_checks');
    }
};