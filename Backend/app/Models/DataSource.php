<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class DataSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'name',
        'type',
        'encrypted_credentials',
        'status',
        'last_sync_at',
        'sync_meta',
    ];

    protected $hidden = [
        'encrypted_credentials',
    ];

    protected function casts(): array
    {
        return [
            'last_sync_at' => 'datetime',
            'sync_meta'    => 'array',
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
    // Credential helpers — always encrypt/decrypt, never expose raw values
    // -------------------------------------------------------------------------

    public function setCredentials(array $credentials): void
    {
        $this->encrypted_credentials = Crypt::encryptString(json_encode($credentials));
    }

    public function getCredentials(): array
    {
        if (empty($this->encrypted_credentials)) {
            return [];
        }

        return json_decode(Crypt::decryptString($this->encrypted_credentials), true) ?? [];
    }
}
