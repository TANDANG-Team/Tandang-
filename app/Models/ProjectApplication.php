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
    public const STATUS_DIBATALKAN = 'dibatalkan';

    /**
     * Lamaran yang memakan slot kuota pelamar (pending + accepted).
     * Sengaja berupa daftar eksplisit: status lain (ditolak, atau status baru di masa depan
     * seperti "dibatalkan") otomatis tidak dihitung dan membuka slot.
     */
    public const ACTIVE_STATUSES = [
        self::STATUS_MENUNGGU,
        self::STATUS_DALAM_REVIEW,
        self::STATUS_DITERIMA,
    ];

    /** Lamaran yang masih boleh dibatalkan sendiri oleh talenta. */
    public const CANCELLABLE_STATUSES = [
        self::STATUS_MENUNGGU,
        self::STATUS_DALAM_REVIEW,
    ];

    protected $fillable = [
        'project_id',
        'user_id',
        'status',
        'cover_letter',
        'match_score',
        'talent_snapshot',
        'reviewed_at',
        'cancelled_at',
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
            }
        });
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
            'cancelled_at' => 'datetime',
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
            self::STATUS_DIBATALKAN => 'Dibatalkan',
            default => ucfirst($this->status),
        };
    }
}
