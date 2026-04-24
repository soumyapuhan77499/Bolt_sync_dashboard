<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sync_tables', function (Blueprint $table) {
            if (!Schema::hasColumn('sync_tables', 'transfer_direction')) {
                $table->string('transfer_direction', 50)
                    ->default('source_to_destination')
                    ->after('name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sync_tables', function (Blueprint $table) {
            if (Schema::hasColumn('sync_tables', 'transfer_direction')) {
                $table->dropColumn('transfer_direction');
            }
        });
    }
};