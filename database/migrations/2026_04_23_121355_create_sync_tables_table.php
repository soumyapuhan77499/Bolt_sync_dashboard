<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_tables', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('source_table_name');
            $table->string('destination_table_name');
            $table->string('sync_mode')->default('manual');
            $table->string('primary_key_column')->default('id');
            $table->json('selected_columns')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->string('last_sync_status')->nullable();
            $table->text('last_sync_message')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('source_table_name');
            $table->index('destination_table_name');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_tables');
    }
};