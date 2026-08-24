<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    // Audit logs are append-only — never update them
    public $timestamps = false;

    protected $fillable = [
        'organization_id',
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'metadata',
        'ip_address',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata'   => 'array',
            'created_at' => 'datetime',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public static function record(
        string $action,
        ?Model $entity = null,
        array $metadata = [],
        ?int $organizationId = null,
        ?int $userId = null,
    ): self {
        return static::create([
            'organization_id' => $organizationId ?? auth()->user()?->organization_id,
            'user_id'         => $userId ?? auth()->id(),
            'action'          => $action,
            'entity_type'     => $entity ? get_class($entity) : null,
            'entity_id'       => $entity?->getKey(),
            'metadata'        => $metadata,
            'ip_address'      => request()->ip(),
            'created_at'      => now(),
        ]);
    }
}
