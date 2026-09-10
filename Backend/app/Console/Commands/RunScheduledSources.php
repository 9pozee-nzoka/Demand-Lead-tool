<?php

namespace App\Console\Commands;

use App\Jobs\RunSourceScrape;
use App\Models\SourceScraper;
use App\Services\Sources\SourceManager;
use Illuminate\Console\Command;

class RunScheduledSources extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sources:run-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run all data sources that are due for execution';

    /**
     * Execute the console command.
     */
    public function handle(SourceManager $manager): int
    {
        $this->info('Checking for scheduled sources...');

        $sources = SourceScraper::dueForRun()->get();

        if ($sources->isEmpty()) {
            $this->info('No sources due for execution.');
            return self::SUCCESS;
        }

        $this->info("Found {$sources->count()} source(s) due for execution.");

        foreach ($sources as $source) {
            $this->line("Dispatching job for: {$source->name}");
            RunSourceScrape::dispatch($source);
        }

        $this->info('All source jobs dispatched to queue.');
        
        return self::SUCCESS;
    }
}
