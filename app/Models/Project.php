<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider_name',
        'title',
        'category',
        'description',
        'responsibilities',
        'location',
        'work_type',
        'compensation',
        'deadline',
        'status',
        'applicants_count',
    ];

    protected function casts(): array
    {
        return [
            'deadline' => 'date',
            'applicants_count' => 'integer',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

