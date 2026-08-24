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
        'priority',
        'status',
    ];

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

    /** Latest measurement for a given period window (7, 30, 90 days) */
    public function latestMeasurement(?string $source = null)
    {
        $query = $this->measurements()->latest('date');
        if ($source) {
            $query->where('source', $source);
        }
        return $query->first();
    }
}
