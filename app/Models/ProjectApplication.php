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

    /**
     * Status di sisi penyedia (project_applications) adalah sumber utama.
     * Setiap kali dibuat/berubah, salin ke tabel applications yang dibaca dashboard talenta.
     */
    protected static function booted(): void
    {
        static::saved(function (ProjectApplication $application) {
            if ($application->wasRecentlyCreated || $application->wasChanged('status')) {
                $application->syncTalentApplication();

                if ($application->status === self::STATUS_DITERIMA) {
                    // Keterlibatan proyek dimulai → buat catatan status pengerjaan terpisah.
                    app(\App\Services\Rating\WorkCompletionService::class)->ensureFor($application);

                    // Kuota bisa penuh karena penerimaan ini → hentikan promosi yang tidak lagi layak.
                    if ($application->project) {
                        app(\App\Services\PromotionService::class)->stopIneligibleForProject($application->project);
                    }
                }
            }
        });
    }

    public function workCompletion(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ProjectWorkCompletion::class, 'project_application_id');
    }

    public function promotionAttribution(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(PromotionAttribution::class, 'project_application_id');
    }

    public function syncTalentApplication(): void
    {
        Application::where('user_id', $this->user_id)
            ->where('project_id', $this->project_id)
            ->update(['status' => $this->status_label]);
    }

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
