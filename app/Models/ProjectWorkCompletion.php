<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Status pengerjaan satu Talenta pada satu proyek (keterlibatan = accepted application).
 */
class ProjectWorkCompletion extends Model
{
    public const STATUS_IN_PROGRESS          = 'in_progress';
    public const STATUS_PENDING_CONFIRMATION = 'pending_confirmation';
    public const STATUS_COMPLETED            = 'completed';
    public const STATUS_DISPUTED             = 'disputed';

    protected $fillable = [
        'project_application_id', 'project_id', 'talent_id', 'provider_id', 'status',
        'requested_by', 'requested_at', 'confirmed_by', 'confirmed_at', 'completed_at', 'rating_deadline_at',
        'disputed_by', 'disputed_at', 'dispute_reason', 'resolved_by', 'resolved_at', 'resolution_note',
    ];

    protected function casts(): array
    {
        return [
            'requested_at'       => 'datetime',
            'confirmed_at'       => 'datetime',
            'completed_at'       => 'datetime',
            'rating_deadline_at' => 'datetime',
            'disputed_at'        => 'datetime',
            'resolved_at'        => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(ProjectApplication::class, 'project_application_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class)->withTrashed();
    }

    public function talent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'talent_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function disputer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disputed_by');
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function isParticipant(User $user): bool
    {
        return in_array((int) $user->id, [(int) $this->talent_id, (int) $this->provider_id], true);
    }

    public function counterpartOf(User $user): ?User
    {
        return (int) $user->id === (int) $this->talent_id ? $this->provider : $this->talent;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isRatingWindowOpen(): bool
    {
        return $this->isCompleted() && $this->rating_deadline_at && $this->rating_deadline_at->isFuture();
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_IN_PROGRESS          => 'Sedang Berlangsung',
            self::STATUS_PENDING_CONFIRMATION => 'Menunggu Konfirmasi Selesai',
            self::STATUS_COMPLETED            => 'Selesai',
            self::STATUS_DISPUTED             => 'Disengketakan',
            default                           => ucfirst($this->status),
        };
    }

    public function getStatusToneAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_COMPLETED            => 'success',
            self::STATUS_PENDING_CONFIRMATION => 'warning',
            self::STATUS_DISPUTED             => 'danger',
            default                           => 'info',
        };
    }
}
