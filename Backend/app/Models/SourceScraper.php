<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class SourceScraper extends Model
{
    use HasFactory, SoftDeletes, BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'name',
        'type',
        'category',
        'base_url',
        'configuration',
        'credentials',
        'schedule',
        'status',
        'last_run_at',
        'next_run_at',
        'error_count',
        'success_count',
        'last_error',
    ];

    protected $casts = [
        'configuration' => 'array',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
        'error_count' => 'integer',
        'success_count' => 'integer',
    ];

    protected $hidden = [
        'credentials',
    ];

    /**
     * Relationships
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function scrapeJobs(): HasMany
    {
        return $this->hasMany(ScrapeJob::class);
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
     * Accessors & Mutators
     */
    public function setCredentialsAttribute($value): void
    {
        if ($value) {
            $this->attributes['credentials'] = Crypt::encryptString($value);
        }
    }

    public function getCredentialsAttribute($value): ?string
    {
        if ($value) {
            return Crypt::decryptString($value);
        }
        return null;
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeDueForRun($query)
    {
        return $query->active()
            ->where(function ($q) {
                $q->whereNull('next_run_at')
                  ->orWhere('next_run_at', '<=', now());
            });
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Helper Methods
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isPaused(): bool
    {
        return $this->status === 'paused';
    }

    public function hasErrors(): bool
    {
        return $this->error_count > 0;
    }

    public function recordSuccess(): void
    {
        $this->increment('success_count');
        $this->update([
            'error_count' => 0,
            'last_error' => null,
            'last_run_at' => now(),
            'status' => 'active',
        ]);
    }

    public function recordError(string $errorMessage): void
    {
        $this->increment('error_count');
        $this->update([
            'last_error' => $errorMessage,
            'last_run_at' => now(),
            'status' => $this->error_count >= 5 ? 'error' : $this->status,
        ]);
    }

    public function activate(): void
    {
        $this->update(['status' => 'active', 'error_count' => 0, 'last_error' => null]);
    }

    public function pause(): void
    {
        $this->update(['status' => 'paused']);
    }

    public function disable(): void
    {
        $this->update(['status' => 'disabled']);
    }

    public function calculateNextRun(): void
    {
        // Simple implementation - can be enhanced with proper cron parser
        $this->update(['next_run_at' => now()->addHours(6)]);
    }
}
