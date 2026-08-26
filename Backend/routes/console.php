<?php

use App\Jobs\CollectKeywordData;
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
                CollectKeywordData::dispatch($keyword->id)
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
 * Artisan command for manual triggering:
 *   php artisan demand:collect [--keyword_id=123]
 */
Artisan::command('demand:collect {--keyword_id= : Collect a specific keyword ID only}', function () {
    $keywordId = $this->option('keyword_id');

    if ($keywordId) {
        CollectKeywordData::dispatch((int) $keywordId);
        $this->info("Dispatched CollectKeywordData for keyword #{$keywordId}");
        return;
    }

    $dispatched = 0;
    Keyword::where('status', 'active')
        ->whereHas('project', fn ($q) => $q->where('status', 'active'))
        ->chunkById(200, function ($keywords) use (&$dispatched) {
            foreach ($keywords as $kw) {
                CollectKeywordData::dispatch($kw->id);
                $dispatched++;
            }
        });

    $this->info("Dispatched {$dispatched} ingestion jobs.");
})->purpose('Manually trigger demand data collection');

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
