<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\SourceScraper;
use Illuminate\Database\Seeder;

class KenyaSourcesSeeder extends Seeder
{
    /**
     * Seed Kenya news sources for all organizations
     */
    public function run(): void
    {
        $organizations = Organization::all();

        if ($organizations->isEmpty()) {
            $this->command->warn('No organizations found. Please create an organization first.');
            return;
        }

        foreach ($organizations as $organization) {
            $this->seedSourcesForOrganization($organization);
        }

        $this->command->info('Kenya news sources seeded successfully!');
    }

    /**
     * Seed sources for a specific organization
     */
    protected function seedSourcesForOrganization(Organization $organization): void
    {
        $this->command->info("Seeding sources for: {$organization->name}");

        // Check if sources already exist
        $existingCount = SourceScraper::where('organization_id', $organization->id)->count();
        if ($existingCount > 0) {
            $this->command->warn("  Organization already has {$existingCount} sources. Skipping...");
            return;
        }

        $sources = [
            // RSS Sources
            [
                'name' => 'Capital FM Business',
                'type' => 'rss',
                'category' => 'news',
                'base_url' => 'https://www.capitalfm.co.ke/business/feed/',
                'configuration' => [
                    'fetch_limit' => 50,
                    'keywords' => ['business', 'technology', 'investment', 'tender', 'procurement'],
                    'min_content_length' => 100,
                    'fetch_full_article' => false,
                ],
                'schedule' => '0 */6 * * *', // Every 6 hours
                'status' => 'active',
            ],
            [
                'name' => 'The Star Business',
                'type' => 'rss',
                'category' => 'news',
                'base_url' => 'https://www.the-star.co.ke/business/feed',
                'configuration' => [
                    'fetch_limit' => 50,
                    'keywords' => ['business', 'economy', 'investment', 'technology'],
                    'min_content_length' => 100,
                ],
                'schedule' => '0 */6 * * *',
                'status' => 'active',
            ],
            [
                'name' => 'Kenya News Agency',
                'type' => 'rss',
                'category' => 'news',
                'base_url' => 'https://www.kenyanews.go.ke/feed/',
                'configuration' => [
                    'fetch_limit' => 50,
                    'keywords' => ['government', 'tender', 'procurement', 'project'],
                    'min_content_length' => 100,
                ],
                'schedule' => '0 */4 * * *', // Every 4 hours for government news
                'status' => 'active',
            ],
            [
                'name' => 'TechCabal East Africa',
                'type' => 'rss',
                'category' => 'news',
                'base_url' => 'https://techcabal.com/feed/',
                'configuration' => [
                    'fetch_limit' => 30,
                    'keywords' => ['kenya', 'technology', 'startup', 'investment', 'fintech'],
                    'min_content_length' => 150,
                ],
                'schedule' => '0 */8 * * *', // Every 8 hours
                'status' => 'active',
            ],
            
            // Tender Source
            [
                'name' => 'Kenya Government Tenders',
                'type' => 'tender',
                'category' => 'procurement',
                'base_url' => 'https://tenders.go.ke',
                'configuration' => [
                    'tender_selector' => '.tender-row',
                    'fetch_limit' => 100,
                    'min_value' => 500000, // KES 500K minimum
                    'categories' => ['ICT', 'Technology', 'Consultancy', 'Software', 'Hardware'],
                    'exclude_expired' => true,
                ],
                'schedule' => '0 8,14 * * *', // 8 AM and 2 PM daily
                'status' => 'active',
            ],
            
            // Webhook Example (inactive by default)
            [
                'name' => 'Website Contact Form',
                'type' => 'webhook',
                'category' => 'leads',
                'base_url' => null,
                'configuration' => [
                    'authentication' => [
                        'type' => 'token',
                        'token' => bin2hex(random_bytes(32)), // Generate 64-char token
                    ],
                    'field_mapping' => [
                        'name' => 'name',
                        'email' => 'email',
                        'phone' => 'phone',
                        'company' => 'company',
                        'message' => 'message',
                    ],
                ],
                'schedule' => null, // Webhooks don't need scheduling
                'status' => 'paused', // Start paused - activate when ready
            ],
        ];

        foreach ($sources as $sourceData) {
            $source = SourceScraper::create([
                'organization_id' => $organization->id,
                'name' => $sourceData['name'],
                'type' => $sourceData['type'],
                'category' => $sourceData['category'],
                'base_url' => $sourceData['base_url'],
                'configuration' => $sourceData['configuration'],
                'schedule' => $sourceData['schedule'],
                'status' => $sourceData['status'],
                'error_count' => 0,
                'success_count' => 0,
            ]);

            // Calculate next run time for active sources
            if ($source->status === 'active' && $source->schedule) {
                $source->calculateNextRun();
            }

            $this->command->info("  ✓ Created: {$source->name} ({$source->type})");
        }
    }
}
