<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public const ROLE_ADMIN = 'admin';
    public const ROLE_TALENTA = 'talenta_muda';
    public const ROLE_PENYEDIA = 'penyedia_proyek';

    /**
     * Get the organization profile associated with the user.
     */
    public function organizationProfile(): HasOne
    {
        return $this->hasOne(OrganizationProfile::class);
    }

    /**
     * Get projects created by this user.
     */
    public function projects(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Project::class, 'user_id');
    }

    /**
     * Get provider notifications for this user.
     */
    public function providerNotifications(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProviderNotification::class, 'user_id');
    }

    /**
     * Check if user is a project provider.
     */
    public function isPenyedia(): bool
    {
        return in_array($this->role, ['penyedia_proyek', 'penyedia', 'provider'], true);
    }

    /**
     * Check if user is young talent.
     */
    public function isTalenta(): bool
    {
        return in_array($this->role, ['talenta_muda', 'talenta'], true);
    }

    /**
     * Check if user is an admin.
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'superadmin'], true);
    }
}
