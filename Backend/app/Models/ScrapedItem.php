<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScrapedItem extends Model
{
    use HasFactory, SoftDeletes, BelongsToOrganization;

    protected $fillable = [
        'source_scraper_id',
        'scrape_job_id',
        'organization_id',
        'external_id',
        'content_hash',
        'title',
        'description',
        'content',
        'url',
        'source_name',
        'published_at',
        'intent',
        'relevance_score',
        'lead_score',
        'opportunity_score',
        'processing_status',
        'processed_at',
        'opportunity_id',
        'lead_id',
        'matched_keywords',
        'extracted_entities',
        'metadata',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'processed_at' => 'datetime',
        'relevance_score' => 'decimal:2',
        'lead_score' => 'decimal:2',
        'opportunity_score' => 'decimal:2',
        'matched_keywords' => 'array',
        'extracted_entities' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Relationships
     */
    public function sourceScraper(): BelongsTo
    {
        return $this->belongsTo(SourceScraper::class);
    }

    public function scrapeJob(): BelongsTo
    {
        return $this->belongsTo(ScrapeJob::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('processing_status', 'pending');
    }

    public function scopeProcessed($query)
    {
        return $query->where('processing_status', 'processed');
    }

    public function scopeMatched($query)
    {
        return $query->where('processing_status', 'matched');
    }

    public function scopeConverted($query)
    {
        return $query->where('processing_status', 'converted');
    }

    public function scopeHighScore($query, float $threshold = 70.0)
    {
        return $query->where('opportunity_score', '>=', $threshold);
    }

    public function scopeByIntent($query, string $intent)
    {
        return $query->where('intent', $intent);
    }

    /**
     * Helper Methods
     */
    public function markAsProcessed(): void
    {
        $this->update([
            'processing_status' => 'processed',
            'processed_at' => now(),
        ]);
    }

    public function markAsMatched(): void
    {
        $this->update(['processing_status' => 'matched']);
    }

    public function markAsConverted(): void
    {
        $this->update(['processing_status' => 'converted']);
    }

    public function ignore(): void
    {
        $this->update(['processing_status' => 'ignored']);
    }

    public function hasHighOpportunityScore(): bool
    {
        return $this->opportunity_score >= 70.0;
    }

    public function hasHighLeadScore(): bool
    {
        return $this->lead_score >= 70.0;
    }

    public function isCommercialIntent(): bool
    {
        return in_array($this->intent, ['commercial', 'transactional', 'tender']);
    }

    public function generateContentHash(): string
    {
        $content = $this->title . $this->description . $this->url;
        return hash('sha256', $content);
    }

    public static function findByContentHash(string $hash): ?self
    {
        return static::where('content_hash', $hash)->first();
    }

    public function isDuplicate(): bool
    {
        return static::where('content_hash', $this->content_hash)
            ->where('id', '!=', $this->id)
            ->exists();
    }
}
