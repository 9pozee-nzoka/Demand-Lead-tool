<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KeywordLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'keyword_id',
        'country',
        'region',
        'city',
        'county',
        'lat',
        'lng',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function keyword(): BelongsTo
    {
        return $this->belongsTo(Keyword::class);
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class, 'location_id');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function displayName(): string
    {
        return collect([$this->city, $this->region, $this->country])
            ->filter()
            ->implode(', ');
    }
}
