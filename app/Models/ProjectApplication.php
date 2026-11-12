<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectApplication extends Model
{
    use HasFactory;

    public const STATUS_MENUNGGU = 'menunggu';
    public const STATUS_DALAM_REVIEW = 'dalam_review';
    public const STATUS_DITERIMA = 'diterima';
    public const STATUS_DITOLAK = 'ditolak';

    protected $fillable = [
        'project_id',
        'user_id',
        'status',
        'cover_letter',
        'match_score',
        'talent_snapshot',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'match_score' => 'integer',
            'talent_snapshot' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_MENUNGGU => 'Menunggu',
            self::STATUS_DALAM_REVIEW => 'Dalam Review',
            self::STATUS_DITERIMA => 'Diterima',
            self::STATUS_DITOLAK => 'Ditolak',
            default => ucfirst($this->status),
        };
    }
}
