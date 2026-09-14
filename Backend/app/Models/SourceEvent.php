<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SourceEvent extends Model
{
    use HasFactory, BelongsToOrganization;

    protected $fillable = [
        'source_scraper_id',
        'organization_id',
        'scrape_job_id',
        'event_type',
        'severity',
        'message',
        'metadata',
        'user_id',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Relationships
     */
    public function sourceScraper(): BelongsTo
    {
        return $this->belongsTo(SourceScraper::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function scrapeJob(): BelongsTo
    {
        return $this->belongsTo(ScrapeJob::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scopes
     */
    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeByEventType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    public function scopeErrors($query)
    {
        return $query->whereIn('severity', ['error', 'critical']);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Helper Methods
     */
    public function isError(): bool
    {
        return in_array($this->severity, ['error', 'critical']);
    }

    public function isCritical(): bool
    {
        return $this->severity === 'critical';
    }

    public function isWarning(): bool
    {
        return $this->severity === 'warning';
    }

    /**
     * Static helpers for logging events
     */
    public static function logSourceCreated(SourceScraper $source, ?User $user = null): self
    {
        return static::create([
            'source_scraper_id' => $source->id,
            'organization_id' => $source->organization_id,
            'event_type' => 'source_created',
            'severity' => 'info',
            'message' => "Source '{$source->name}' was created.",
            'user_id' => $user?->id,
        ]);
    }

    public static function logSourceActivated(SourceScraper $source, ?User $user = null): self
    {
        return static::create([
            'source_scraper_id' => $source->id,
            'organization_id' => $source->organization_id,
            'event_type' => 'source_activated',
            'severity' => 'info',
            'message' => "Source '{$source->name}' was activated.",
            'user_id' => $user?->id,
        ]);
    }

    public static function logSourcePaused(SourceScraper $source, ?User $user = null): self
    {
        return static::create([
            'source_scraper_id' => $source->id,
            'organization_id' => $source->organization_id,
            'event_type' => 'source_paused',
            'severity' => 'info',
            'message' => "Source '{$source->name}' was paused.",
            'user_id' => $user?->id,
        ]);
    }

    public static function logJobStarted(SourceScraper $source, ScrapeJob $job): self
    {
        return static::create([
            'source_scraper_id' => $source->id,
            'organization_id' => $source->organization_id,
            'scrape_job_id' => $job->id,
            'event_type' => 'job_started',
            'severity' => 'info',
            'message' => "Scrape job #{$job->id} started.",
        ]);
    }

    public static function logJobCompleted(SourceScraper $source, ScrapeJob $job): self
    {
        return static::create([
            'source_scraper_id' => $source->id,
            'organization_id' => $source->organization_id,
            'scrape_job_id' => $job->id,
            'event_type' => 'job_completed',
            'severity' => 'info',
            'message' => "Scrape job #{$job->id} completed. Found: {$job->items_found}, New: {$job->items_new}",
            'metadata' => [
                'items_found' => $job->items_found,
                'items_new' => $job->items_new,
                'items_updated' => $job->items_updated,
                'duration' => $job->duration,
            ],
        ]);
    }

    public static function logJobFailed(SourceScraper $source, ScrapeJob $job, string $error): self
    {
        return static::create([
            'source_scraper_id' => $source->id,
            'organization_id' => $source->organization_id,
            'scrape_job_id' => $job->id,
            'event_type' => 'job_failed',
            'severity' => 'error',
            'message' => "Scrape job #{$job->id} failed: {$error}",
            'metadata' => ['error' => $error],
        ]);
    }

    public static function logErrorThresholdExceeded(SourceScraper $source): self
    {
        return static::create([
            'source_scraper_id' => $source->id,
            'organization_id' => $source->organization_id,
            'event_type' => 'error_threshold_exceeded',
            'severity' => 'critical',
            'message' => "Source '{$source->name}' has exceeded error threshold ({$source->error_count} errors). Status changed to 'error'.",
            'metadata' => ['error_count' => $source->error_count],
        ]);
    }
}
