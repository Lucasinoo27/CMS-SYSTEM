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
        // Add composite indexes to conferences table
        Schema::table('conferences', function (Blueprint $table) {
            $table->index(['status', 'start_date']);
        });
        
        // Add composite index to pages table
        Schema::table('pages', function (Blueprint $table) {
            // This index already exists as a foreign key
            // $table->index(['conference_id', 'is_published']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove composite indexes from conferences table
        Schema::table('conferences', function (Blueprint $table) {
            $table->dropIndex(['status', 'start_date']);
        });
    }
};
