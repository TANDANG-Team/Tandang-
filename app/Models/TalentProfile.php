<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TalentProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category',
        'institution',
        'major',
        'city',
        'document',
        'verification_status',
        'rejection_reason',
        'submitted_at',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

