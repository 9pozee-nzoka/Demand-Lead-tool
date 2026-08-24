<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Opportunity extends Model
{
    use HasFactory;

    protected $fillable = [
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
        'title',
        'explanation',
        'recommended_actions',
        'trend_state',
        'status',
        'detected_at',
        'expires_at',
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
            'recommended_actions' => 'array',
            'detected_at'         => 'datetime',
            'expires_at'          => 'datetime',
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
