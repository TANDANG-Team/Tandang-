<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'user_id',
        'title',
        'category',
        'location',
        'work_method',
        'compensation',
        'required_competencies',
        'availability',
        'talents_needed',
        'start_date',
        'end_date',
        'registration_deadline',
        'description',
        'responsibilities',
        'workstyle_characteristics',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'registration_deadline' => 'date',
            'talents_needed' => 'integer',
            'workstyle_characteristics' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(ProjectApplication::class, 'project_id');
    }

    public function acceptedApplications(): HasMany
    {
        return $this->hasMany(ProjectApplication::class, 'project_id')->where('status', 'diterima');
    }

    public function pendingApplications(): HasMany
    {
        return $this->hasMany(ProjectApplication::class, 'project_id')->whereIn('status', ['menunggu', 'dalam_review']);
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    public function isArchived(): bool
    {
        return $this->status === self::STATUS_ARCHIVED;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Draf',
            self::STATUS_PUBLISHED => 'Dipublikasikan',
            self::STATUS_CLOSED => 'Ditutup',
            self::STATUS_ARCHIVED => 'Diarsipkan',
            default => ucfirst($this->status),
        };
    }
}
