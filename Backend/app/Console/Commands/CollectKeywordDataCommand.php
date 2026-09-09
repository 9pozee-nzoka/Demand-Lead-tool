<?php

namespace App\Console\Commands;

use App\Jobs\CollectKeywordData;
use App\Models\Keyword;
use Illuminate\Console\Command;

/**
 * Manually trigger keyword data collection
 * 
 * Usage:
 *   php artisan keywords:collect           # Collect for all active keywords
 *   php artisan keywords:collect --id=5    # Collect for specific keyword
 *   php artisan keywords:collect --sync    # Run synchronously (no queue)
 * 
 * Sprint 4
 */
class CollectKeywordDataCommand extends Command
{
    protected $signature = 'keywords:collect
                            {--id= : Specific keyword ID to collect}
                            {--sync : Run synchronously instead of queuing}
                            {--force : Include inactive keywords}';

    protected $description = 'Collect keyword trend data from Google Trends';

    public function handle(): int
    {
        $this->info('🔍 Starting keyword data collection...');

        // Get keywords to collect
        $query = Keyword::query();

        if ($id = $this->option('id')) {
            $query->where('id', $id);
        }

        if (!$this->option('force')) {
            $query->where('status', 'active');
        }

        $keywords = $query->get();

        if ($keywords->isEmpty()) {
            $this->warn('No keywords found to collect.');
            return self::FAILURE;
        }

        $this->info("Found {$keywords->count()} keyword(s) to collect.");

        // Dispatch jobs
        $progressBar = $this->output->createProgressBar($keywords->count());
        $progressBar->start();

        foreach ($keywords as $keyword) {
            $this->newLine();
            $this->line("Processing: {$keyword->term} (ID: {$keyword->id})");

            if ($this->option('sync')) {
                // Run synchronously for immediate feedback
                try {
                    $job = new CollectKeywordData($keyword);
                    $provider = app(\App\Services\Providers\GoogleTrendsProvider::class);
                    $job->handle($provider);
                    $this->info("  ✓ Collected data for '{$keyword->term}'");
                } catch (\Exception $e) {
                    $this->error("  ✗ Failed: {$e->getMessage()}");
                }
            } else {
                // Queue for background processing
                CollectKeywordData::dispatch($keyword);
                $this->info("  → Queued for background processing");
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        if ($this->option('sync')) {
            $this->info('✅ Data collection completed.');
        } else {
            $this->info('✅ Data collection jobs queued. Monitor progress with: php artisan queue:work');
        }

        return self::SUCCESS;
    }
}
