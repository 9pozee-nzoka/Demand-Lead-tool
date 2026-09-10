<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Services\Sources\SourceManager;
use Illuminate\Console\Command;

class SetupKenyaSources extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'sources:setup-kenya 
                            {--org= : Organization ID (defaults to first organization)}
                            {--test : Test sources after setup}
                            {--run : Run sources immediately after setup}';

    /**
     * The console command description.
     */
    protected $description = 'Set up Kenya news and tender sources with default configuration';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Setting up Kenya news sources...');
        $this->newLine();

        // Get organization
        $orgId = $this->option('org');
        $organization = $orgId 
            ? Organization::find($orgId) 
            : Organization::first();

        if (!$organization) {
            $this->error('❌ No organization found. Please create an organization first.');
            return self::FAILURE;
        }

        $this->info("Organization: {$organization->name}");
        $this->newLine();

        // Seed sources
        $this->call('db:seed', [
            '--class' => 'KenyaSourcesSeeder',
        ]);

        $this->newLine();

        // Get created sources
        $sources = $organization->sourceScraper()
            ->whereIn('type', ['rss', 'tender', 'webhook'])
            ->get();

        if ($sources->isEmpty()) {
            $this->error('❌ No sources were created. Check the seeder.');
            return self::FAILURE;
        }

        // Display sources
        $this->info('📊 Created Sources:');
        $this->newLine();

        $tableData = [];
        foreach ($sources as $source) {
            $tableData[] = [
                $source->id,
                $source->name,
                $source->type,
                $source->status,
                $source->schedule ?? 'N/A',
            ];
        }

        $this->table(
            ['ID', 'Name', 'Type', 'Status', 'Schedule'],
            $tableData
        );

        // Test sources if requested
        if ($this->option('test')) {
            $this->newLine();
            $this->info('🧪 Testing source connections...');
            $this->newLine();

            $manager = app(SourceManager::class);

            foreach ($sources->where('type', '!=', 'webhook') as $source) {
                $this->info("Testing: {$source->name}...");
                
                try {
                    $result = $manager->testSource($source);
                    
                    if ($result['success'] ?? false) {
                        $this->line("  ✓ Success - {$result['message']}");
                    } else {
                        $this->warn("  ⚠ Warning - {$result['message']}");
                    }
                } catch (\Throwable $e) {
                    $this->error("  ✗ Failed - {$e->getMessage()}");
                }
            }
        }

        // Run sources if requested
        if ($this->option('run')) {
            $this->newLine();
            $this->info('▶️  Running sources to collect initial data...');
            $this->newLine();

            $manager = app(SourceManager::class);

            foreach ($sources->where('status', 'active')->where('type', '!=', 'webhook') as $source) {
                $this->info("Running: {$source->name}...");
                
                try {
                    $job = $manager->runSource($source);
                    
                    $this->line("  ✓ Completed - Found: {$job->items_found}, New: {$job->items_new}");
                } catch (\Throwable $e) {
                    $this->error("  ✗ Failed - {$e->getMessage()}");
                }
            }
        }

        // Display webhook URLs
        $webhooks = $sources->where('type', 'webhook');
        if ($webhooks->isNotEmpty()) {
            $this->newLine();
            $this->info('🔗 Webhook URLs:');
            $this->newLine();

            foreach ($webhooks as $webhook) {
                $token = $webhook->configuration['authentication']['token'] ?? 'NOT_SET';
                $url = url("/api/v1/webhooks/receive/{$webhook->id}/{$token}");
                
                $this->line("  {$webhook->name}:");
                $this->line("  {$url}");
                $this->newLine();
            }
        }

        // Next steps
        $this->newLine();
        $this->info('✅ Setup complete!');
        $this->newLine();
        $this->info('📝 Next steps:');
        $this->line('  1. Start Horizon: php artisan horizon');
        $this->line('  2. View sources: php artisan sources:list');
        $this->line('  3. Run manual test: php artisan sources:run {source_id}');
        $this->line('  4. Check dashboard: GET /api/v1/dashboard');
        $this->newLine();

        return self::SUCCESS;
    }
}
