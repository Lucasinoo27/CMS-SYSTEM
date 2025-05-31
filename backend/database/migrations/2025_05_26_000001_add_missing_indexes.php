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
        // Add index to users table for email
        Schema::table('users', function (Blueprint $table) {
            $table->index('email');
        });

        // Add composite index to user_roles table
        Schema::table('user_roles', function (Blueprint $table) {
            $table->index(['user_id', 'role_id']);
        });

        // Add composite index to editor_conferences table
        Schema::table('editor_conferences', function (Blueprint $table) {
            $table->index(['user_id', 'conference_id']);
        });

        // Add composite index to pages table
        Schema::table('pages', function (Blueprint $table) {
            $table->index(['conference_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove indexes from users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['email']);
        });

        // Remove composite indexes from user_roles table
        Schema::table('user_roles', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'role_id']);
        });

        // Remove composite indexes from editor_conferences table
        Schema::table('editor_conferences', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'conference_id']);
        });

        // Remove composite indexes from pages table
        Schema::table('pages', function (Blueprint $table) {
            $table->dropIndex(['conference_id', 'status']);
        });
    }
}; 