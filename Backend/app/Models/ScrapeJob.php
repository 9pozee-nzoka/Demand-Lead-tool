<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScrapeJob extends Model
{
    use HasFactory, BelongsToOrganization;

    protected $fillable = [
        'source_scraper_id',
        'organization_id',
        'status',
        'started_at',
        'completed_at',
        'items_found',
        'items_new',
        'items_updated',
        'items_failed',
        'error_message',
        'metadata',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array',
        'items_found' => 'integer',
        'items_new' => 'integer',
        'items_updated' => 'integer',
        'items_failed' => 'integer',
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

    public function scrapedItems(): HasMany
    {
        return $this->hasMany(ScrapedItem::class);
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Helper Methods
     */
    public function start(): void
    {
        $this->update([
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    public function complete(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        // Update parent source scraper
        $this->sourceScraper->recordSuccess();
        $this->sourceScraper->calculateNextRun();
    }

    public function fail(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'completed_at' => now(),
            'error_message' => $errorMessage,
        ]);

        // Update parent source scraper
        $this->sourceScraper->recordError($errorMessage);
    }

    public function incrementFound(int $count = 1): void
    {
        $this->increment('items_found', $count);
    }

    public function incrementNew(int $count = 1): void
    {
        $this->increment('items_new', $count);
    }

    public function incrementUpdated(int $count = 1): void
    {
        $this->increment('items_updated', $count);
    }

    public function incrementFailed(int $count = 1): void
    {
        $this->increment('items_failed', $count);
    }

    public function getDurationAttribute(): ?int
    {
        if ($this->started_at && $this->completed_at) {
            return $this->started_at->diffInSeconds($this->completed_at);
        }
        return null;
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isRunning(): bool
    {
        return $this->status === 'running';
    }
}
