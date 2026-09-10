<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Models\SourceScraper;
use Illuminate\Console\Command;

class ListSources extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'sources:list 
                            {--org= : Organization ID}
                            {--type= : Filter by type (rss, tender, webhook)}
                            {--status= : Filter by status (active, paused, error)}';

    /**
     * The console command description.
     */
    protected $description = 'List all configured data sources';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Get organization
        $orgId = $this->option('org');
        $organization = $orgId 
            ? Organization::find($orgId) 
            : Organization::first();

        if (!$organization) {
            $this->error('❌ No organization found.');
            return self::FAILURE;
        }

        $this->info("Organization: {$organization->name}");
        $this->newLine();

        // Build query
        $query = SourceScraper::where('organization_id', $organization->id);

        if ($type = $this->option('type')) {
            $query->where('type', $type);
        }

        if ($status = $this->option('status')) {
            $query->where('status', $status);
        }

        $sources = $query->orderBy('created_at', 'desc')->get();

        if ($sources->isEmpty()) {
            $this->warn('No sources found.');
            $this->newLine();
            $this->info('💡 Tip: Run "php artisan sources:setup-kenya" to create default sources');
            return self::SUCCESS;
        }

        // Display summary
        $this->info("📊 Sources Summary:");
        $this->line("  Total: {$sources->count()}");
        $this->line("  Active: " . $sources->where('status', 'active')->count());
        $this->line("  Paused: " . $sources->where('status', 'paused')->count());
        $this->line("  Error: " . $sources->where('status', 'error')->count());
        $this->newLine();

        // Display table
        $tableData = [];
        foreach ($sources as $source) {
            $uptime = $source->success_count + $source->error_count > 0
                ? round(($source->success_count / ($source->success_count + $source->error_count)) * 100, 1)
                : 0;

            $tableData[] = [
                $source->id,
                $source->name,
                $source->type,
                $this->getStatusBadge($source->status),
                $source->success_count,
                $source->error_count,
                "{$uptime}%",
                $source->last_run_at ? $source->last_run_at->diffForHumans() : 'Never',
            ];
        }

        $this->table(
            ['ID', 'Name', 'Type', 'Status', 'Success', 'Errors', 'Uptime', 'Last Run'],
            $tableData
        );

        // Show commands
        $this->newLine();
        $this->info('💡 Available commands:');
        $this->line('  php artisan sources:run {id}           - Run a source manually');
        $this->line('  php artisan sources:test {id}          - Test source connection');
        $this->line('  php artisan sources:pause {id}         - Pause a source');
        $this->line('  php artisan sources:activate {id}      - Activate a source');
        $this->line('  php artisan sources:stats {id}         - View source statistics');

        return self::SUCCESS;
    }

    /**
     * Get colored status badge
     */
    protected function getStatusBadge(string $status): string
    {
        return match ($status) {
            'active' => "<fg=green>● ACTIVE</>",
            'paused' => "<fg=yellow>● PAUSED</>",
            'error' => "<fg=red>● ERROR</>",
            default => "<fg=gray>● {$status}</>",
        };
    }
}
