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
                'location' => 'Ljubljana, Slovenia',
                'start_date' => '2025-09-15',
                'end_date' => '2025-09-18',
                'status' => 'published',
            ],
            [
                'name' => 'International Research Symposium',
                'slug' => 'international-research-symposium',
                'description' => 'A platform for researchers to present their latest findings across multiple disciplines.',
                'location' => 'Zagreb, Croatia',
                'start_date' => '2025-10-22',
                'end_date' => '2025-10-25',
                'status' => 'published',
            ],
            [
                'name' => 'Student Exchange Program Workshop',
                'slug' => 'student-exchange-program-workshop',
                'description' => 'Workshop focused on improving student mobility and exchange programs across European universities.',
                'location' => 'Vienna, Austria',
                'start_date' => '2025-11-05',
                'end_date' => '2025-11-07',
                'status' => 'published',
            ],
            [
                'name' => 'Digital Transformation in Education',
                'slug' => 'digital-transformation-in-education',
                'location' => 'Prague, Czech Republic',
                'description' => 'Conference exploring technological innovations and digital strategies in higher education.',
                'start_date' => '2026-01-18',
                'end_date' => '2026-01-20',
                'status' => 'draft',
            ],
            [
                'name' => 'Sustainable Agriculture Research Forum',
                'slug' => 'sustainable-agriculture-research-forum',
                'description' => 'Forum for discussing sustainable agricultural practices and research findings.',
                'location' => 'Padua, Italy',
                'start_date' => '2026-03-10',
                'end_date' => '2026-03-12',
                'status' => 'draft',
            ],
            [
                'name' => 'Agricultural Biotechnology Conference',
                'slug' => 'agricultural-biotechnology-conference',
                'description' => 'Conference focusing on the latest developments in agricultural biotechnology.',
                'location' => 'Budapest, Hungary',
                'start_date' => '2026-04-15',
                'end_date' => '2026-04-17',
                'status' => 'draft',
            ],
            [
                'name' => 'Agroecology Workshop',
                'slug' => 'agroecology-workshop',
                'description' => 'Workshop on sustainable farming practices and ecological approaches to agriculture.',
                'location' => 'Osijek, Croatia',
                'start_date' => '2026-05-20',
                'end_date' => '2026-05-22',
                'status' => 'draft',
            ],
            [
                'name' => 'Agricultural Innovation Summit',
                'slug' => 'agricultural-innovation-summit',
                'description' => 'Summit focusing on innovative approaches and technologies in agriculture.',
                'location' => 'Nitra, Slovakia',
                'start_date' => '2026-06-25',
                'end_date' => '2026-06-27',
                'status' => 'draft',
            ],
        ];

        // Create each conference
        foreach ($conferences as $conferenceData) {
            Conference::create($conferenceData);
        }
    }
}
