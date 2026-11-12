<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationProfile extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'organization_name',
        'industry_type',
        'description',
        'address',
        'city',
        'phone',
        'website',
        'logo',
    ];

    /**
     * Get the user that owns the organization profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
