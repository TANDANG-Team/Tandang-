<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rating extends Model
{
    public const ROLE_PENYEDIA = 'penyedia';
    public const ROLE_TALENTA  = 'talenta';

    /** Kolom nilai/ulasan tidak pernah diisi dari request edit; rating bersifat final setelah dikirim. */
    protected $fillable = [
        'project_work_completion_id', 'project_id', 'rater_id', 'ratee_id', 'rater_role', 'ratee_role',
        'scores', 'final_score', 'review', 'published_at',
        'review_hidden', 'hidden_reason', 'hidden_by', 'hidden_at',
        'voided_at', 'voided_by', 'void_reason',
    ];

    protected function casts(): array
    {
        return [
            'scores'        => 'array',
            'final_score'   => 'decimal:2',
            'published_at'  => 'datetime',
            'review_hidden' => 'boolean',
            'hidden_at'     => 'datetime',
            'voided_at'     => 'datetime',
        ];
    }

    public function completion(): BelongsTo
    {
        return $this->belongsTo(ProjectWorkCompletion::class, 'project_work_completion_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class)->withTrashed();
    }

    public function rater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rater_id');
    }

    public function ratee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ratee_id');
    }

    public function hider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hidden_by');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(RatingReport::class);
    }

    /**
     * Rating yang boleh dilihat publik: sudah dipublikasikan, atau periode penilaian
     * sudah berakhir (terdeteksi berdasarkan timestamp walau scheduler belum jalan).
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNotNull('published_at')
              ->orWhereHas('completion', fn ($c) => $c->where('rating_deadline_at', '<=', now()));
        });
    }

    /**
     * Rating yang dihitung ke reputasi: terpublikasi, tidak dibatalkan Admin karena pelanggaran,
     * dan berasal dari keterlibatan yang benar-benar Selesai.
     */
    public function scopeValid(Builder $query): Builder
    {
        return $query->published()
            ->whereNull('voided_at')
            ->whereHas('completion', fn ($c) => $c->where('status', ProjectWorkCompletion::STATUS_COMPLETED));
    }

    public function isVoided(): bool
    {
        return $this->voided_at !== null;
    }

    public function voider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null
            || ($this->completion?->rating_deadline_at && $this->completion->rating_deadline_at->lte(now()));
    }

    public function getVisibleReviewAttribute(): ?string
    {
        return $this->review_hidden ? null : $this->review;
    }
}
