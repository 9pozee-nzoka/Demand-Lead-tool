<?php

namespace App\Models\Concerns;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Attach this trait to any model that is owned by a tenant organization.
 *
 * It adds:
 *  - belongsTo organization() relationship
 *  - scopeForOrganization() for explicit filtering
 *  - scopeForAuth() to automatically scope to the authenticated user's org
 *  - boot hook to auto-set organization_id on create (when authenticated)
 */
trait BelongsToOrganization
{
    public static function bootBelongsToOrganization(): void
    {
        // Auto-assign organization_id when creating if authenticated
        static::creating(function ($model) {
            if (empty($model->organization_id) && auth()->check()) {
                $model->organization_id = auth()->user()->organization_id;
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** Scope to a specific organization */
    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where($this->getTable().'.organization_id', $organizationId);
    }

    /** Scope to the currently authenticated user's organization */
    public function scopeForAuth(Builder $query): Builder
    {
        return $query->where(
            $this->getTable().'.organization_id',
            auth()->user()?->organization_id
        );
    }
}
