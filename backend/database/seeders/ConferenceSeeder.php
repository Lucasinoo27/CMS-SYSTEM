<?php

namespace Database\Seeders;

use App\Models\Conference;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ConferenceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $universities = [
            [
                'name' => 'University of Ljubljana, Biotechnical Faculty',
                'location' => 'Ljubljana, Slovenia',
                'description' => 'Conference hosted by the Department of Animal Science at the Biotechnical Faculty of University of Ljubljana.',
            ],
            [
                'name' => 'University of Zagreb, Faculty of Agriculture',
                'location' => 'Zagreb, Croatia',
                'description' => 'Annual agricultural science conference organized by the Faculty of Agriculture at University of Zagreb.',
            ],
            [
                'name' => 'Josip Juraj Strossmayer University of Osijek, Faculty of Agrobiotechnical Sciences',
                'location' => 'Osijek, Croatia',
                'description' => 'International conference on agrobiotechnical sciences hosted by Josip Juraj Strossmayer University.',
            ],
            [
                'name' => 'BOKU University',
                'location' => 'Vienna, Austria',
                'description' => 'University of Natural Resources and Life Sciences (BOKU) annual scientific conference.',
            ],
            [
                'name' => 'University of Padua',
                'location' => 'Padua, Italy',
                'description' => 'International symposium on agricultural and biological sciences by the University of Padua.',
            ],
            [
                'name' => 'Czech University of Life Sciences Prague',
                'location' => 'Prague, Czech Republic',
                'description' => 'Conference on sustainable agriculture and life sciences hosted by the Czech University of Life Sciences.',
            ],
            [
                'name' => 'Hungarian University of Agricultural and Life Sciences',
                'location' => 'Gödöllő, Hungary',
                'description' => 'Annual agricultural and life sciences research symposium from MATE University.',
            ],
            [
                'name' => 'Slovak University of Agriculture in Nitra',
                'location' => 'Nitra, Slovakia',
                'description' => 'International conference on agricultural innovation and sustainability in Central Europe.',
            ],
        ];

        // Start date is set to be within the next 3 to 9 months
        $startDate = now()->addMonths(rand(3, 9));
        
        foreach ($universities as $university) {
            // Generate random conference duration between 2 and 5 days
            $endDate = clone $startDate;
            $endDate->addDays(rand(2, 5));
            
            Conference::create([
                'name' => $university['name'] . ' Academic Conference ' . date('Y'),
                'slug' => Str::slug($university['name'] . '-conference-' . date('Y')),
                'description' => $university['description'],
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'location' => $university['location'],
                'status' => 'published',
            ]);
            
            // Increment start date by 14 days for the next conference
            $startDate->addDays(14);
        }
    }
}
