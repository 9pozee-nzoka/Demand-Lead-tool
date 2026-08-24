<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use BelongsToOrganization, HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'project_id',
        'opportunity_id',
        'campaign_id',
        'name',
        'email',
        'phone',
        'location',
        'company',
        'source',
        'intent',
        'lead_score',
        'score_label',
        'status',
        'assigned_to',
        'qualification_summary',
        'qualification_data',
        'qualified_at',
        'contacted_at',
    ];

    protected function casts(): array
    {
        return [
            'lead_score'         => 'decimal:2',
            'qualification_data' => 'array',
            'qualified_at'       => 'datetime',
            'contacted_at'       => 'datetime',
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

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function events(): HasMany
    {
        return $this->hasMany(LeadEvent::class);
    }

    public function contact(): HasOne
    {
        return $this->hasOne(Contact::class);
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeHot($query)
    {
        return $query->where('score_label', 'hot');
    }

    public function scopeUnassigned($query)
    {
        return $query->whereNull('assigned_to');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** Recalculate score label from numeric score */
    public function recalculateLabel(): void
    {
        $this->score_label = match (true) {
            $this->lead_score >= 90 => 'hot',
            $this->lead_score >= 70 => 'warm',
            $this->lead_score >= 40 => 'potential',
            default                 => 'low',
        };
    }
}
