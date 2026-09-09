<?php

use App\Jobs\CollectKeywordData;
use App\Jobs\ProcessDemandSignal;
use App\Models\Keyword;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// ── Artisan utility ────────────────────────────────────────────────────────
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ── Demand Engine Scheduler ────────────────────────────────────────────────

/**
 * Daily keyword ingestion — runs at 02:00 UTC.
 *
 * Dispatches one CollectKeywordData job per active keyword so each
 * keyword can retry independently and the queue workers handle
 * rate-limiting via the built-in backoff.
 *
 * In production set QUEUE_CONNECTION=redis so Laravel Horizon can
 * throttle the ingestion queue to respect Google Trends rate limits.
 */
Schedule::call(function () {
    $dispatched = 0;

    Keyword::where('status', 'active')
        ->with('project:id,organization_id,status')
        ->whereHas('project', fn ($q) => $q->where('status', 'active'))
        ->chunkById(200, function ($keywords) use (&$dispatched) {
            foreach ($keywords as $keyword) {
                // Spread dispatches with a small delay to avoid flooding
                // the queue at exactly midnight.
                CollectKeywordData::dispatch($keyword)
                    ->delay(now()->addSeconds($dispatched * 2));
                $dispatched++;
            }
        });

    logger()->info("Demand scheduler: dispatched {$dispatched} ingestion jobs.");
})
->dailyAt('02:00')
->name('demand:collect-all-keywords')
->withoutOverlapping(60)       // lock for up to 60 min so overlapping runs don't double-dispatch
->onOneServer();               // run only once in multi-server environments

/**
 * Process demand signals every 4 hours
 * 
 * Analyzes keywords that have recent measurements (last 24 hours)
 * and updates trend states, baselines, and creates opportunities.
 */
Schedule::call(function () {
    $processed = 0;

    // Get keywords with recent measurements
    Keyword::where('status', 'active')
        ->whereHas('project', fn ($q) => $q->where('status', 'active'))
        ->where(function($q) {
            $q->whereNull('trend_updated_at')
              ->orWhere('trend_updated_at', '<', now()->subHours(4));
        })
        ->whereHas('measurements', function($q) {
            $q->where('date', '>', now()->subHours(24)->toDateString());
        })
        ->chunkById(100, function ($keywords) use (&$processed) {
            foreach ($keywords as $keyword) {
                ProcessDemandSignal::dispatch($keyword)
                    ->delay(now()->addSeconds($processed));
                $processed++;
            }
        });

    logger()->info("Demand scheduler: dispatched {$processed} signal processing jobs.");
})
->everyFourHours()
->name('demand:process-signals')
->withoutOverlapping(30)
->onOneServer();

/**
 * Cleanup old measurements (keep last 180 days)
 * Runs weekly on Sunday at 03:00
 */
Schedule::call(function () {
    $deleted = \App\Models\KeywordMeasurement::where('date', '<', now()->subDays(180)->toDateString())
        ->delete();

    logger()->info("Demand scheduler: deleted {$deleted} old measurements.");
})
->weekly()
->sundays()
->at('03:00')
->name('demand:cleanup-old-measurements')
->onOneServer();

/**
 * Expire old opportunities (older than 30 days in 'detected' status)
 * Runs daily at 04:00
 */
Schedule::call(function () {
    $expired = \App\Models\Opportunity::where('status', 'detected')
        ->where('detected_at', '<', now()->subDays(30))
        ->update([
            'status' => 'expired',
            'expires_at' => now(),
        ]);

    logger()->info("Demand scheduler: expired {$expired} old opportunities.");
})
->dailyAt('04:00')
->name('demand:expire-opportunities')
->onOneServer();

/**
 * Clear cached analytics (market gaps, competitor analysis)
 * Runs every 6 hours to refresh AI insights
 */
Schedule::call(function () {
    \Illuminate\Support\Facades\Cache::forget('market_gaps_org_*');
    \Illuminate\Support\Facades\Cache::forget('competitor_analysis_*');
    
    logger()->info("Demand scheduler: cleared analytics cache.");
})
->everySixHours()
->name('demand:refresh-analytics-cache')
->onOneServer();

/**
 * Artisan command for manual triggering:
 *   php artisan demand:collect [--keyword_id=123]
 */
Artisan::command('demand:collect {--keyword_id= : Collect a specific keyword ID only}', function () {
    $keywordId = $this->option('keyword_id');

    if ($keywordId) {
        $keyword = Keyword::findOrFail($keywordId);
        CollectKeywordData::dispatch($keyword);
        $this->info("Dispatched CollectKeywordData for keyword #{$keywordId}");
        return;
    }

    $dispatched = 0;
    Keyword::where('status', 'active')
        ->whereHas('project', fn ($q) => $q->where('status', 'active'))
        ->chunkById(200, function ($keywords) use (&$dispatched) {
            foreach ($keywords as $kw) {
                CollectKeywordData::dispatch($kw);
                $dispatched++;
            }
        });

    $this->info("Dispatched {$dispatched} ingestion jobs.");
})->purpose('Manually trigger demand data collection');

/**
 * Artisan command for processing demand signals:
 *   php artisan demand:process [--keyword_id=123]
 */
Artisan::command('demand:process {--keyword_id= : Process a specific keyword ID only}', function () {
    $keywordId = $this->option('keyword_id');

    if ($keywordId) {
        $keyword = Keyword::findOrFail($keywordId);
        ProcessDemandSignal::dispatch($keyword);
        $this->info("Dispatched ProcessDemandSignal for keyword #{$keywordId}");
        return;
    }

    $dispatched = 0;
    Keyword::where('status', 'active')
        ->whereHas('project', fn ($q) => $q->where('status', 'active'))
        ->chunkById(100, function ($keywords) use (&$dispatched) {
            foreach ($keywords as $kw) {
                ProcessDemandSignal::dispatch($kw);
                $dispatched++;
            }
        });

    $this->info("Dispatched {$dispatched} signal processing jobs.");
})->purpose('Manually trigger demand signal processing');

/**
 * Promote a user to super-admin:
 *   php artisan admin:promote admin@yourdomain.com
 */
Artisan::command('admin:promote {email : The email address to promote}', function () {
    $user = \App\Models\User::where('email', $this->argument('email'))->firstOrFail();
    $user->update(['is_super_admin' => true]);
    $this->info("✓ {$user->email} is now a super-admin.");
})->purpose('Promote a user to super-admin');

Artisan::command('admin:demote {email : The email address to demote}', function () {
    $user = \App\Models\User::where('email', $this->argument('email'))->firstOrFail();
    $user->update(['is_super_admin' => false]);
    $this->info("✓ {$user->email} super-admin access revoked.");
})->purpose('Revoke super-admin access from a user');


/**
 * Health check for scheduler
 *   php artisan demand:health
 */
Artisan::command('demand:health', function () {
    $this->info('=== Demand Engine Health Check ===');
    $this->newLine();

    // Check active keywords
    $activeKeywords = Keyword::where('status', 'active')
        ->whereHas('project', fn ($q) => $q->where('status', 'active'))
        ->count();
    $this->line("Active keywords: {$activeKeywords}");

    // Check recent measurements (last 24h)
    $recentMeasurements = \App\Models\KeywordMeasurement::where('date', '>', now()->subHours(24)->toDateString())->count();
    $this->line("Measurements (24h): {$recentMeasurements}");

    // Check trends updated recently
    $trendsUpdated = Keyword::where('trend_updated_at', '>', now()->subHours(6))->count();
    $this->line("Trends updated (6h): {$trendsUpdated}");

    // Check pending jobs
    $pendingJobs = \Illuminate\Support\Facades\DB::table('jobs')->count();
    $this->line("Pending jobs: {$pendingJobs}");

    // Check failed jobs
    $failedJobs = \Illuminate\Support\Facades\DB::table('failed_jobs')->count();
    $this->line("Failed jobs: {$failedJobs}");

    // Check active opportunities
    $activeOpportunities = \App\Models\Opportunity::whereIn('status', ['detected', 'scored', 'reviewed'])->count();
    $this->line("Active opportunities: {$activeOpportunities}");

    $this->newLine();
    
    if ($failedJobs > 10) {
        $this->error('⚠ High number of failed jobs detected!');
    } elseif ($recentMeasurements === 0 && $activeKeywords > 0) {
        $this->warn('⚠ No recent measurements. Check if scheduler is running.');
    } else {
        $this->info('✓ System health looks good!');
    }
})->purpose('Check demand engine health status');

/**
 * Retry all failed jobs
 *   php artisan demand:retry-failed
 */
Artisan::command('demand:retry-failed', function () {
    $failedJobs = \Illuminate\Support\Facades\DB::table('failed_jobs')->get();
    
    if ($failedJobs->isEmpty()) {
        $this->info('No failed jobs to retry.');
        return;
    }

    $this->info("Found {$failedJobs->count()} failed jobs. Retrying...");
    
    foreach ($failedJobs as $job) {
        Artisan::call('queue:retry', ['id' => $job->uuid]);
    }

    $this->info('✓ All failed jobs have been queued for retry.');
})->purpose('Retry all failed jobs');
