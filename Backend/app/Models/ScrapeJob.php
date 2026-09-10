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
        'items_found' => 'integer',
        'items_new' => 'integer',
        'items_updated' => 'integer',
        'items_failed' => 'integer',
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

    public function scrapedItems(): HasMany
    {
        return $this->hasMany(ScrapedItem::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(SourceEvent::class);
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

    public function complete(int $found, int $new, int $updated, int $failed = 0): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'items_found' => $found,
            'items_new' => $new,
            'items_updated' => $updated,
            'items_failed' => $failed,
        ]);
    }

    public function fail(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'completed_at' => now(),
            'error_message' => $errorMessage,
        ]);
    }

    public function getDuration(): ?int
    {
        if ($this->started_at && $this->completed_at) {
            return $this->started_at->diffInSeconds($this->completed_at);
        }
        return null;
    }

    public function isSuccessful(): bool
    {
        return $this->status === 'completed' && $this->items_new > 0;
    }
}
