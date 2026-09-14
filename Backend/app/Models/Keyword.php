<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Keyword extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
        'keyword',
        'normalized_keyword',
        'category',
        'intent',
        'match_type',
        'priority',
        'status',
        'notes',
        'trend_state',
        'baseline_7d',
        'baseline_30d',
        'baseline_90d',
        'current_interest',
        'growth_rate_7d',
        'growth_rate_30d',
        'growth_rate_90d',
        'volatility',
        'last_measured_at',
        'trend_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'baseline_7d' => 'decimal:2',
            'baseline_30d' => 'decimal:2',
            'baseline_90d' => 'decimal:2',
            'current_interest' => 'decimal:2',
            'growth_rate_7d' => 'decimal:2',
            'growth_rate_30d' => 'decimal:2',
            'growth_rate_90d' => 'decimal:2',
            'volatility' => 'decimal:2',
            'last_measured_at' => 'datetime',
            'trend_updated_at' => 'datetime',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(KeywordLocation::class);
    }

    public function measurements(): HasMany
    {
        return $this->hasMany(KeywordMeasurement::class);
    }

    /** Relationship: latest measurement (use as property or eager-load) */
    public function latestMeasurement(): HasMany
    {
        return $this->hasMany(KeywordMeasurement::class)
                    ->latest('date')
                    ->limit(1);
    }

    public function clusters(): BelongsToMany
    {
        return $this->belongsToMany(DemandCluster::class, 'cluster_keywords')
                    ->withPivot('relevance_score')
                    ->withTimestamps();
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeHighIntent($query)
    {
        return $query->whereIn('intent', ['commercial', 'transactional', 'local']);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** Get the single latest measurement record (eager-loaded via latestMeasurement relation or queried directly) */
    public function getLatestMeasurementRecord(?string $source = null): ?KeywordMeasurement
    {
        $query = $this->measurements()->latest('date');
        if ($source) {
            $query->where('source', $source);
        }
        return $query->first();
    }
}
