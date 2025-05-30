<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Conference;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Call the RoleSeeder first to create roles
        $this->call(RoleSeeder::class);

        // Create admin user
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@university.edu',
            'password' => Hash::make('password'),
        ]);

        // Assign admin role
        $adminRole = Role::where('name', 'admin')->first();
        $admin->roles()->attach($adminRole->id);

        // Create editor user
        $editor = User::create([
            'name' => 'Editor User',
            'email' => 'editor@university.edu',
            'password' => Hash::make('password'),
        ]);

        // Assign editor role
        $editorRole = Role::where('name', 'editor')->first();
        $editor->roles()->attach($editorRole->id);

        // Create sample conferences
        $this->call(ConferenceSeeder::class);
        
        // Create sample pages for each conference
        $this->call(PageSeeder::class);
        
        // Create sample content for each page
        $this->call(PageContentSeeder::class);
    }
}
