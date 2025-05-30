<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Page;
use App\Models\Content;
use App\Models\User;

class PageContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get admin and editor users
        $admin = User::whereHas('roles', function($query) {
            $query->where('name', 'admin');
        })->first();
        
        $editor = User::whereHas('roles', function($query) {
            $query->where('name', 'editor');
        })->first();

        // Sample content templates by page title
        $contentTemplates = [
            'About' => [
                [
                    'title' => 'Welcome',
                    'type' => 'text',
                    'content' => '<h2>Welcome to Our Conference</h2><p>This international conference brings together researchers, practitioners, and industry experts to discuss the latest advances and challenges in the field.</p><p>Join us for an exciting program of keynote presentations, paper sessions, workshops, and networking opportunities.</p>',
                    'order' => 1,
                ],
                [
                    'title' => 'About the Conference',
                    'type' => 'text',
                    'content' => '<h3>About the Conference</h3><p>Our conference is a premier forum for the presentation of new advances and research results in the fields of science and technology. The conference will bring together leading researchers, engineers and scientists in the domain of interest from around the world.</p><p>The conference aims to provide a platform for researchers and practitioners from both academia as well as industry to meet and share cutting-edge developments in the field.</p>',
                    'order' => 2,
                ],
                [
                    'title' => 'Organizing Committee',
                    'type' => 'text',
                    'content' => '<h3>Organizing Committee</h3><p>The conference is organized by a dedicated team of professionals who work tirelessly to ensure a successful event.</p><ul><li><strong>Conference Chair:</strong> Dr. Jane Smith, University of Science</li><li><strong>Program Chair:</strong> Prof. John Doe, Institute of Technology</li><li><strong>Publications Chair:</strong> Dr. Robert Johnson, Research Institute</li><li><strong>Local Arrangements Chair:</strong> Dr. Emily Williams, University of Science</li></ul>',
                    'order' => 3,
                ],
            ],
            'Program' => [
                [
                    'title' => 'Conference Schedule',
                    'type' => 'text',
                    'content' => '<h2>Conference Schedule</h2><p>Below is the preliminary schedule for the conference. Please note that this schedule is subject to change.</p><h3>Day 1 - Opening Day</h3><ul><li><strong>8:00 AM - 9:00 AM:</strong> Registration</li><li><strong>9:00 AM - 10:00 AM:</strong> Opening Ceremony</li><li><strong>10:00 AM - 11:00 AM:</strong> Keynote Speech</li><li><strong>11:00 AM - 12:00 PM:</strong> Session 1</li><li><strong>12:00 PM - 1:30 PM:</strong> Lunch Break</li><li><strong>1:30 PM - 3:00 PM:</strong> Session 2</li><li><strong>3:00 PM - 3:30 PM:</strong> Coffee Break</li><li><strong>3:30 PM - 5:00 PM:</strong> Session 3</li><li><strong>6:00 PM - 8:00 PM:</strong> Welcome Reception</li></ul>',
                    'order' => 1,
                ],
                [
                    'title' => 'Keynote Speakers',
                    'type' => 'text',
                    'content' => '<h3>Keynote Speakers</h3><p>We are proud to present our distinguished keynote speakers for this year\'s conference:</p><div class="speaker"><h4>Prof. Alan Smith</h4><p>University of Technology</p><p>Topic: "Future Directions in Technology"</p></div><div class="speaker"><h4>Dr. Lisa Johnson</h4><p>Institute of Advanced Research</p><p>Topic: "Innovation and Challenges in Modern Research"</p></div>',
                    'order' => 2,
                ],
            ],
            'Speakers' => [
                [
                    'title' => 'Featured Speakers',
                    'type' => 'text',
                    'content' => '<h2>Featured Speakers</h2><p>Our conference features talks from leading experts in the field. Below are some of our distinguished speakers:</p>',
                    'order' => 1,
                ],
                [
                    'title' => 'Keynote Speaker 1',
                    'type' => 'text',
                    'content' => '<div class="speaker-profile"><h3>Dr. Michael Chen</h3><p class="speaker-title">Chief Scientist, Future Technologies Inc.</p><div class="speaker-bio"><p>Dr. Chen is a renowned expert in artificial intelligence and machine learning with over 20 years of experience in the field. He has published more than 100 papers in top-tier conferences and journals, and his work on deep learning has been cited over 10,000 times.</p><p>His keynote address "AI: Present and Future Challenges" will explore the current state of AI and the major challenges that lie ahead.</p></div></div>',
                    'order' => 2,
                ],
                [
                    'title' => 'Keynote Speaker 2',
                    'type' => 'text',
                    'content' => '<div class="speaker-profile"><h3>Prof. Sarah Johnson</h3><p class="speaker-title">Director, Institute for Advanced Computing</p><div class="speaker-bio"><p>Professor Johnson leads the Institute for Advanced Computing, where she directs research on quantum computing and its applications. She has received numerous awards for her contributions to computer science, including the National Medal of Science.</p><p>Her talk "Quantum Computing: Breaking New Boundaries" will discuss recent breakthroughs in quantum computing and their implications for science and industry.</p></div></div>',
                    'order' => 3,
                ],
            ],
            'Registration' => [
                [
                    'title' => 'Registration Information',
                    'type' => 'text',
                    'content' => '<h2>Registration Information</h2><p>Registration for the conference is now open. Please register early to take advantage of early bird rates.</p><h3>Registration Fees</h3><table class="fee-table"><tr><th>Registration Type</th><th>Early Bird (Until March 1)</th><th>Regular Rate</th></tr><tr><td>Regular Attendee</td><td>$450</td><td>$550</td></tr><tr><td>Student</td><td>$250</td><td>$350</td></tr><tr><td>IEEE Member</td><td>$400</td><td>$500</td></tr></table><p>Registration includes access to all sessions, conference materials, coffee breaks, lunches, and the welcome reception.</p>',
                    'order' => 1,
                ],
                [
                    'title' => 'How to Register',
                    'type' => 'text',
                    'content' => '<h3>How to Register</h3><p>Registration can be completed online through our conference management system. Follow these steps:</p><ol><li>Create an account or log in to your existing account</li><li>Complete the registration form</li><li>Choose your registration type</li><li>Proceed to payment</li></ol><p>If you have any questions about the registration process, please contact us at registration@conference.org.</p>',
                    'order' => 2,
                ],
            ],
            'Venue' => [
                [
                    'title' => 'Conference Venue',
                    'type' => 'text',
                    'content' => '<h2>Conference Venue</h2><p>The conference will be held at the Grand Hotel & Conference Center, a state-of-the-art facility located in the heart of the city.</p><h3>Address</h3><p>Grand Hotel & Conference Center<br>123 Main Street<br>Metropolis, State 12345<br>Country</p><p>The venue is easily accessible by public transportation and is located just 20 minutes from the International Airport.</p>',
                    'order' => 1,
                ],
                [
                    'title' => 'Accommodations',
                    'type' => 'text',
                    'content' => '<h3>Accommodations</h3><p>We have negotiated special rates for conference attendees at the following hotels:</p><div class="hotel"><h4>Grand Hotel (Conference Venue)</h4><p>Special Rate: $180 per night</p><p>To book: Call +1-123-456-7890 and mention the conference code CONF2023</p></div><div class="hotel"><h4>City Center Hotel</h4><p>Special Rate: $150 per night</p><p>Distance from venue: 0.5 miles</p><p>To book: Visit their website and use code CONF2023</p></div><div class="hotel"><h4>Budget Inn</h4><p>Special Rate: $100 per night</p><p>Distance from venue: 1.5 miles</p><p>To book: Email reservations@budgetinn.com with subject line "Conference 2023"</p></div>',
                    'order' => 2,
                ],
            ],
            'Call for Papers' => [
                [
                    'title' => 'Call for Papers',
                    'type' => 'text',
                    'content' => '<h2>Call for Papers</h2><p>We invite submissions of original research papers on topics related to the conference theme and tracks. All submissions will be peer-reviewed for originality, technical content, and relevance.</p><h3>Topics of Interest</h3><p>Topics of interest include, but are not limited to:</p><ul><li>Artificial Intelligence and Machine Learning</li><li>Computer Vision and Image Processing</li><li>Natural Language Processing</li><li>Data Mining and Big Data Analytics</li><li>Internet of Things and Cyber-Physical Systems</li><li>Cloud Computing and Distributed Systems</li><li>Cybersecurity and Privacy</li><li>Human-Computer Interaction</li></ul>',
                    'order' => 1,
                ],
                [
                    'title' => 'Submission Guidelines',
                    'type' => 'text',
                    'content' => '<h3>Submission Guidelines</h3><p>All papers must be original and not simultaneously submitted to another journal or conference. Papers must be submitted in PDF format and should be formatted according to the conference template.</p><ul><li>Full papers: 8-10 pages</li><li>Short papers: 4-6 pages</li><li>Poster abstracts: 2 pages</li></ul><h3>Important Dates</h3><ul><li><strong>Paper submission deadline:</strong> March 15, 2023</li><li><strong>Notification of acceptance:</strong> May 1, 2023</li><li><strong>Camera-ready submission:</strong> June 1, 2023</li></ul>',
                    'order' => 2,
                ],
            ],
            'Sponsors' => [
                [
                    'title' => 'Our Sponsors',
                    'type' => 'text',
                    'content' => '<h2>Our Sponsors</h2><p>We are grateful to the following organizations for their generous support of our conference:</p><h3>Platinum Sponsors</h3><div class="sponsor-list"><div class="sponsor platinum"><h4>TechCorp</h4><p>Leading provider of technology solutions</p></div><div class="sponsor platinum"><h4>Innovation Labs</h4><p>Research and development for tomorrow\'s technology</p></div></div><h3>Gold Sponsors</h3><div class="sponsor-list"><div class="sponsor gold"><h4>DataSystems Inc.</h4><p>Big data solutions for enterprise</p></div><div class="sponsor gold"><h4>Global Networks</h4><p>Connecting the world through technology</p></div></div><h3>Silver Sponsors</h3><div class="sponsor-list"><div class="sponsor silver"><h4>Future Computing</h4></div><div class="sponsor silver"><h4>Digital Solutions</h4></div><div class="sponsor silver"><h4>Research Institute</h4></div></div>',
                    'order' => 1,
                ],
                [
                    'title' => 'Sponsorship Opportunities',
                    'type' => 'text',
                    'content' => '<h3>Sponsorship Opportunities</h3><p>We offer various sponsorship packages to organizations interested in supporting our conference and gaining visibility among attendees.</p><table class="sponsorship-table"><tr><th>Level</th><th>Benefits</th><th>Cost</th></tr><tr><td>Platinum</td><td>Logo on all materials, exhibition booth, 5 free registrations, full-page ad in program</td><td>$10,000</td></tr><tr><td>Gold</td><td>Logo on website and program, exhibition booth, 3 free registrations</td><td>$5,000</td></tr><tr><td>Silver</td><td>Logo on website and program, 1 free registration</td><td>$2,500</td></tr></table><p>For more information about sponsorship opportunities, please contact sponsors@conference.org.</p>',
                    'order' => 2,
                ],
            ],
            'Travel Information' => [
                [
                    'title' => 'Getting to the Conference',
                    'type' => 'text',
                    'content' => '<h2>Travel Information</h2><p>Here\'s everything you need to know about traveling to the conference.</p><h3>Airports</h3><p>The closest airport to the conference venue is Metropolis International Airport (MET), located approximately 20 miles from the venue. Several major airlines offer direct flights to MET from major cities worldwide.</p><h3>Ground Transportation</h3><ul><li><strong>Taxi:</strong> Taxis are available at the airport terminal. The fare to the conference venue is approximately $40-50.</li><li><strong>Shuttle:</strong> Airport shuttles are available for $20 one-way. Reservations can be made online.</li><li><strong>Public Transportation:</strong> The Airport Express train runs every 15 minutes from the airport to Downtown Station, which is a 10-minute walk from the venue. Fare: $12 one-way.</li></ul>',
                    'order' => 1,
                ],
                [
                    'title' => 'Local Transportation',
                    'type' => 'text',
                    'content' => '<h3>Getting Around the City</h3><p>The conference city has an excellent public transportation system, making it easy to get around.</p><ul><li><strong>Metro:</strong> The metro system covers most of the city. The nearest station to the venue is Central Station (Lines 1 and 3).</li><li><strong>Bus:</strong> Buses 15, 22, and 41 stop directly in front of the conference venue.</li><li><strong>Bike Sharing:</strong> The city offers a bike-sharing program with stations throughout the downtown area.</li></ul><h3>Local Attractions</h3><p>While you\'re at the conference, consider visiting these nearby attractions:</p><ul><li>National Museum (1.5 miles from venue)</li><li>Botanical Gardens (2 miles)</li><li>Historic Downtown District (0.5 miles)</li><li>Science Center (1 mile)</li></ul>',
                    'order' => 2,
                ],
            ],
        ];
        
        // Get all pages
        $pages = Page::all();
        
        foreach ($pages as $page) {
            // Check if the page title exists in our content templates
            $titleKey = array_keys($contentTemplates, '', true);
            foreach (array_keys($contentTemplates) as $key) {
                if (strpos($page->title, $key) !== false) {
                    $titleKey = $key;
                    break;
                }
            }
            
            // If no direct match, use the first template as default
            if (empty($titleKey) || !isset($contentTemplates[$titleKey])) {
                $titleKey = array_key_first($contentTemplates);
            }
            
            // Get the content templates for this page
            $templates = $contentTemplates[$titleKey];
            
            // Alternate between admin and editor as creator
            $creator = $page->id % 2 === 0 ? $admin : $editor;
            
            // Add content to the page
            foreach ($templates as $template) {
                Content::create([
                    'title' => $template['title'],
                    'type' => $template['type'],
                    'content' => $template['content'],
                    'page_id' => $page->id,
                    'order' => $template['order'],
                    'created_by' => $creator->id,
                    'updated_by' => $creator->id,
                ]);
            }
        }
    }
} 