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
                'meta_description' => 'Information about the conference, its history, and organizing committee.',
                'layout' => 'default',
                'is_published' => true,
            ],
            [
                'title' => 'Program',
                'meta_description' => 'Conference program, schedule, and sessions information.',
                'layout' => 'full-width',
                'is_published' => true,
            ],
            [
                'title' => 'Speakers',
                'meta_description' => 'Information about keynote speakers and presenters.',
                'layout' => 'sidebar',
                'is_published' => true,
            ],
            [
                'title' => 'Registration',
                'meta_description' => 'Registration information, deadlines, and fees.',
                'layout' => 'default',
                'is_published' => true,
            ],
            [
                'title' => 'Venue',
                'meta_description' => 'Conference venue, accommodations, and travel information.',
                'layout' => 'sidebar',
                'is_published' => true,
            ],
            [
                'title' => 'Call for Papers',
                'meta_description' => 'Information about submitting papers and research to the conference.',
                'layout' => 'default', 
                'is_published' => true,
            ],
            [
                'title' => 'Sponsors',
                'meta_description' => 'Organizations and institutions sponsoring the conference.',
                'layout' => 'full-width',
                'is_published' => false, // Draft page
            ],
            [
                'title' => 'Travel Information',
                'meta_description' => 'Practical information for traveling to the conference.',
                'layout' => 'sidebar',
                'is_published' => false, // Draft page
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
                    'meta_description' => $template['meta_description'],
                    'layout' => $template['layout'],
                    'is_published' => $template['is_published'],
                    'conference_id' => $conference->id,
                    'created_by' => $creator->id,
                    'updated_by' => $creator->id,
                ]);
            }
        }
    }
}
