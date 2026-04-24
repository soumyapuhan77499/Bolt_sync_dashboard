<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_compare_runs', function (Blueprint $table) {
            $table->id();
            $table->string('table_name', 150);
            $table->string('primary_key_column', 150)->nullable();
            $table->json('compared_columns')->nullable();
            $table->integer('row_limit')->default(500);

            $table->bigInteger('source_total_rows')->default(0);
            $table->bigInteger('destination_total_rows')->default(0);
            $table->bigInteger('source_loaded_rows')->default(0);
            $table->bigInteger('destination_loaded_rows')->default(0);

            $table->bigInteger('only_in_source_count')->default(0);
            $table->bigInteger('only_in_destination_count')->default(0);
            $table->bigInteger('changed_rows_count')->default(0);
            $table->bigInteger('same_rows_count')->default(0);

            $table->string('status', 50)->default('pending');
            $table->text('message')->nullable();
            $table->json('summary')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->bigInteger('created_by')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_compare_runs');
    }
};