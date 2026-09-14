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
        'relevance_score' => 'float',
        'lead_score' => 'float',
        'opportunity_score' => 'float',
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

    public function scopeIgnored($query)
    {
        return $query->where('processing_status', 'ignored');
    }

    public function scopeByIntent($query, string $intent)
    {
        return $query->where('intent', $intent);
    }

    public function scopeHighOpportunity($query, float $minScore = 70.0)
    {
        return $query->where('opportunity_score', '>=', $minScore);
    }

    public function scopeHighLead($query, float $minScore = 70.0)
    {
        return $query->where('lead_score', '>=', $minScore);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
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
        $this->update([
            'processing_status' => 'matched',
            'processed_at' => now(),
        ]);
    }

    public function markAsConverted(): void
    {
        $this->update([
            'processing_status' => 'converted',
            'processed_at' => now(),
        ]);
    }

    public function markAsIgnored(): void
    {
        $this->update([
            'processing_status' => 'ignored',
            'processed_at' => now(),
        ]);
    }

    public function isPending(): bool
    {
        return $this->processing_status === 'pending';
    }

    public function isProcessed(): bool
    {
        return $this->processing_status === 'processed';
    }

    public function isConverted(): bool
    {
        return $this->processing_status === 'converted';
    }

    public function hasHighOpportunityScore(): bool
    {
        return $this->opportunity_score >= 70.0;
    }

    public function hasHighLeadScore(): bool
    {
        return $this->lead_score >= 70.0;
    }

    public function isTransactional(): bool
    {
        return $this->intent === 'transactional' || $this->intent === 'tender';
    }

    public function isInformational(): bool
    {
        return $this->intent === 'informational';
    }

    public function convertToOpportunity(): ?Opportunity
    {
        if ($this->opportunity_id) {
            return $this->opportunity;
        }

        // This would be handled by a service
        // Placeholder for now
        return null;
    }

    public function convertToLead(): ?Lead
    {
        if ($this->lead_id) {
            return $this->lead;
        }

        // This would be handled by a service
        // Placeholder for now
        return null;
    }

    /**
     * Generate content hash for deduplication
     */
    public static function generateContentHash(string $content): string
    {
        return hash('sha256', trim($content));
    }

    /**
     * Check if item already exists
     */
    public static function exists(string $contentHash): bool
    {
        return static::where('content_hash', $contentHash)->exists();
    }
}
