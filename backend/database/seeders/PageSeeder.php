<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Page;
use App\Models\Conference;
use App\Models\User;
use Illuminate\Support\Str;

class PageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all conferences
        $conferences = Conference::all();
        
        // Get admin and editor users
        $admin = User::whereHas('roles', function($query) {
            $query->where('name', 'admin');
        })->first();
        
        $editor = User::whereHas('roles', function($query) {
            $query->where('name', 'editor');
        })->first();
        
        // Sample page templates - used to create pages for each conference
        $pageTemplates = [
            [
                'title' => 'About',
                'status' => 'published',
            ],
            [
                'title' => 'Program',
                'status' => 'published',
            ],
            [
                'title' => 'Speakers',
                'status' => 'published',
            ],
            [
                'title' => 'Registration',
                'status' => 'published',
            ],
            [
                'title' => 'Venue',
                'status' => 'published',
            ],
            [
                'title' => 'Call for Papers',
                'status' => 'published',
            ],
            [
                'title' => 'Sponsors',
                'status' => 'published',
            ],
            [
                'title' => 'Travel Information',
                'status' => 'published',
            ],
        ];
        
        // Create pages for each conference
        foreach ($conferences as $conference) {
            foreach ($pageTemplates as $index => $template) {
                // Alternate between admin and editor as creator
                $creator = $index % 2 === 0 ? $admin : $editor;
                
                Page::create([
                    'title' => $template['title'],
                    'slug' => $conference->slug . '-' . Str::slug($template['title']),
                    'status' => $template['status'],
                    'conference_id' => $conference->id,
                    'created_by' => $creator->id,
                    'updated_by' => $creator->id,
                ]);
            }
        }
    }
}
