<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Opportunity extends Model
{
    use HasFactory, BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'project_id',
        'keyword_id',
        'cluster_id',
        'location_id',
        'growth_score',
        'intent_score',
        'geo_score',
        'volume_score',
        'competition_score',
        'historical_score',
        'opportunity_score',
        'score_breakdown',
        'score_explanation',
        'scored_at',
        'priority',
        'title',
        'description',
        'explanation',
        'recommended_actions',
        'trend_state',
        'status',
        'detected_at',
        'expires_at',
        'dismissed_reason',
        'dismissed_at',
    ];

    protected function casts(): array
    {
        return [
            'growth_score'        => 'decimal:2',
            'intent_score'        => 'decimal:2',
            'geo_score'           => 'decimal:2',
            'volume_score'        => 'decimal:2',
            'competition_score'   => 'decimal:2',
            'historical_score'    => 'decimal:2',
            'opportunity_score'   => 'decimal:2',
            'score_breakdown'     => 'array',
            'recommended_actions' => 'array',
            'detected_at'         => 'datetime',
            'expires_at'          => 'datetime',
            'scored_at'           => 'datetime',
            'dismissed_at'        => 'datetime',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function keyword(): BelongsTo
    {
        return $this->belongsTo(Keyword::class);
    }

    public function cluster(): BelongsTo
    {
        return $this->belongsTo(DemandCluster::class, 'cluster_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(KeywordLocation::class, 'location_id');
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    public function landingPages(): HasMany
    {
        return $this->hasMany(LandingPage::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function keywords(): BelongsToMany
    {
        return $this->belongsToMany(Keyword::class, 'keyword_opportunity')
                    ->withTimestamps();
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeHighScore($query, float $threshold = 60.0)
    {
        return $query->where('opportunity_score', '>=', $threshold);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['detected', 'reviewed', 'actioned', 'converting']);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function getPriorityAttribute(): string
    {
        return match (true) {
            $this->opportunity_score >= 80 => 'very_high',
            $this->opportunity_score >= 60 => 'high',
            $this->opportunity_score >= 40 => 'moderate',
            default                        => 'low',
        };
    }

    public function scoreLabel(): string
    {
        return match (true) {
            $this->opportunity_score >= 80 => 'VERY HIGH',
            $this->opportunity_score >= 60 => 'HIGH',
            $this->opportunity_score >= 40 => 'MODERATE',
            default                        => 'LOW',
        };
    }
}
