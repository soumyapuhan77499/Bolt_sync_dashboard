<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_run_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sync_run_id')->constrained('sync_runs')->cascadeOnDelete();
            $table->string('level')->default('info');
            $table->text('message');
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index('level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_run_logs');
    }
};