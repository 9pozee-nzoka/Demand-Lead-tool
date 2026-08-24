<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AlertRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'project_id',
        'name',
        'minimum_score',
        'minimum_growth',
        'intent',
        'location',
        'cooldown',
        'channels',
        'recipients',
        'quiet_hours',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'minimum_score'  => 'decimal:2',
            'minimum_growth' => 'decimal:2',
            'cooldown'       => 'integer',
            'channels'       => 'array',
            'recipients'     => 'array',
            'quiet_hours'    => 'array',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /** Check whether the rule matches a given opportunity */
    public function matches(Opportunity $opportunity): bool
    {
        if ($opportunity->opportunity_score < $this->minimum_score) {
            return false;
        }

        if ($this->intent !== 'any' && $opportunity->keyword?->intent !== $this->intent) {
            return false;
        }

        return true;
    }
}
