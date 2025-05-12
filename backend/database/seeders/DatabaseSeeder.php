<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Call role seeder first to ensure roles exist
        $this->call(RoleSeeder::class);

        // Create admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@university-cms.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
            ]
        );

        // Assign admin role to admin user
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole && !$admin->hasRole('admin')) {
            $admin->roles()->attach($adminRole);
        }

        // Create a test editor user
        $editor = User::firstOrCreate(
            ['email' => 'editor@university-cms.com'],
            [
                'name' => 'Editor User',
                'password' => Hash::make('password'),
            ]
        );

        // Assign editor role to editor user
        $editorRole = Role::where('name', 'editor')->first();
        if ($editorRole && !$editor->hasRole('editor')) {
            $editor->roles()->attach($editorRole);
        }
    }
}
