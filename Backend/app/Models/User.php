<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'organization_id',
        'name',
        'email',
        'phone',
        'password',
        'role',
        'status',
        'is_super_admin',
        'google2fa_secret',
        'google2fa_enabled',
        'two_factor_recovery_codes',
        'two_factor_enabled_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'google2fa_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'       => 'datetime',
            'password'                => 'hashed',
            'google2fa_enabled'       => 'boolean',
            'is_super_admin'          => 'boolean',
            'two_factor_enabled_at'   => 'datetime',
        ];
    }

    // -------------------------------------------------------------------------
    // Role helpers
    // -------------------------------------------------------------------------

    public function isOwner(): bool       { return $this->role === 'owner'; }
    public function isAdmin(): bool       { return in_array($this->role, ['owner', 'admin'], true); }
    public function isSales(): bool       { return in_array($this->role, ['owner', 'admin', 'sales'], true); }
    public function isAnalyst(): bool     { return in_array($this->role, ['owner', 'admin', 'analyst'], true); }
    public function isSuperAdmin(): bool  { return (bool) $this->is_super_admin; }

    // -------------------------------------------------------------------------
    // Two-Factor Authentication helpers
    // -------------------------------------------------------------------------

    public function has2FAEnabled(): bool
    {
        return (bool) $this->google2fa_enabled;
    }

    public function hasRecoveryCodes(): bool
    {
        return !empty($this->two_factor_recovery_codes);
    }

    public function getRemainingRecoveryCodesCount(): int
    {
        if (!$this->hasRecoveryCodes()) {
            return 0;
        }

        try {
            $codes = json_decode(\Illuminate\Support\Facades\Crypt::decryptString($this->two_factor_recovery_codes), true);
            return count($codes ?? []);
        } catch (\Exception $e) {
            return 0;
        }
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function assignedLeads(): HasMany
    {
        return $this->hasMany(Lead::class, 'assigned_to');
    }

    public function assignedDeals(): HasMany
    {
        return $this->hasMany(Deal::class, 'assigned_to');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_user_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeForOrganization($query, int $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }
}
