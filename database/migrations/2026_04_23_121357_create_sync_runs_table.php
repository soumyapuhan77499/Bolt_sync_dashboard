<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_runs', function (Blueprint $table) {
            $table->id();
            $table->string('module_name');
            $table->string('run_type')->nullable();
            $table->foreignId('sync_table_id')->nullable()->constrained('sync_tables')->nullOnDelete();
            $table->string('source_table_name')->nullable();
            $table->string('destination_table_name')->nullable();
            $table->string('status')->default('pending');
            $table->integer('records_processed')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->text('message')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('module_name');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_runs');
    }
};