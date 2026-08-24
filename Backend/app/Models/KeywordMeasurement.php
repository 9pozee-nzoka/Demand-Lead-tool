<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KeywordMeasurement extends Model
{
    use HasFactory;

    protected $fillable = [
        'keyword_id',
        'source',
        'date',
        'interest',
        'volume',
        'growth',
        'competition',
        'cpc',
        'geo',
        'raw_data',
    ];

    protected function casts(): array
    {
        return [
            'date'        => 'date',
            'interest'    => 'integer',
            'volume'      => 'integer',
            'growth'      => 'decimal:2',
            'competition' => 'decimal:4',
            'cpc'         => 'decimal:2',
            'raw_data'    => 'array',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function keyword(): BelongsTo
    {
        return $this->belongsTo(Keyword::class);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeForSource($query, string $source)
    {
        return $query->where('source', $source);
    }

    public function scopeInPeriod($query, int $days)
    {
        return $query->where('date', '>=', now()->subDays($days)->toDateString());
    }
}
