<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Conference;
use Illuminate\Support\Str;

class ConferenceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Sample conference data
        $conferences = [
            [
                'name' => 'European Higher Education Conference 2025',
                'slug' => 'european-higher-education-conference-2025',
                'description' => 'Annual gathering of European university representatives discussing the future of higher education.',
                'location' => 'Paris, France',
                'start_date' => '2025-09-15',
                'end_date' => '2025-09-18',
                'status' => 'published',
            ],
            [
                'name' => 'International Research Symposium',
                'slug' => 'international-research-symposium',
                'description' => 'A platform for researchers to present their latest findings across multiple disciplines.',
                'location' => 'Berlin, Germany',
                'start_date' => '2025-10-22',
                'end_date' => '2025-10-25',
                'status' => 'draft',
            ],
            [
                'name' => 'Student Exchange Program Workshop',
                'slug' => 'student-exchange-program-workshop',
                'description' => 'Workshop focused on improving student mobility and exchange programs across European universities.',
                'location' => 'London, UK',
                'start_date' => '2025-11-05',
                'end_date' => '2025-11-07',
                'status' => 'draft',
            ],
            [
                'name' => 'Digital Transformation in Education',
                'slug' => 'digital-transformation-in-education',
                'location' => 'Amsterdam, Netherlands',
                'description' => 'Conference exploring technological innovations and digital strategies in higher education.',
                'start_date' => '2026-01-18',
                'end_date' => '2026-01-20',
                'status' => 'draft',
            ],
        ];

        // Create each conference
        foreach ($conferences as $conferenceData) {
            Conference::create($conferenceData);
        }
    }
}
