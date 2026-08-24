<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsageRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'metric',
        'quantity',
        'period',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** Increment a metric for the current calendar month */
    public static function increment(int $organizationId, string $metric, int $by = 1): void
    {
        static::updateOrCreate(
            [
                'organization_id' => $organizationId,
                'metric'          => $metric,
                'period'          => now()->format('Y-m'),
            ],
            ['quantity' => 0]
        )->increment('quantity', $by);
    }
}
