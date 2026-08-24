<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DemandCluster extends Model
{
    use HasFactory;

    protected $table = 'demand_clusters';

    protected $fillable = [
        'project_id',
        'name',
        'category',
        'intent',
        'score',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function keywords(): BelongsToMany
    {
        return $this->belongsToMany(Keyword::class, 'cluster_keywords')
                    ->withPivot('relevance_score')
                    ->withTimestamps();
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class, 'cluster_id');
    }
}
