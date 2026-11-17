<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_ARCHIVED = 'archived';

    // Legacy status values used by old system
    /** Pilihan kategori di form Buat Proyek (penyedia tetap bisa mengetik kategori sendiri). */
    public const CATEGORY_OPTIONS = [
        'Administrasi',
        'Desain Grafis',
        'Digital Marketing',
        'Event & Acara',
        'Fotografi & Videografi',
        'Konten Media Sosial',
        'Kreatif',
        'Kuliner',
        'Operasional',
        'Pendidikan & Mengajar',
        'Penjualan & Layanan Pelanggan',
        'Penulisan & Copywriting',
        'Web Development',
    ];

    public const STATUS_DIPUBLIKASIKAN = 'Dipublikasikan';

    protected $fillable = [
        'user_id',
        'provider_name',
        'title',
        'category',
        'location',
        // new penyedia columns
        'work_method',
        'required_competencies',
        'availability',
        'talents_needed',
        'registration_deadline',
        'workstyle_characteristics',
        // legacy columns (kept for backward compat)
        'work_type',
        'compensation',
        'description',
        'responsibilities',
        'skills',
        'schedule',
        'quota',
        'deadline',
        'start_date',
        'end_date',
        'status',
        'workstyle_requirements',
        'workstyle_confirmed',
        'applicants_count',
        'cover_image',
    ];

    /** cover_url ikut ter-serialisasi agar modal detail (JSON) bisa menampilkan gambar. */
    protected $appends = ['cover_url'];

    protected static function booted(): void
    {
        // Hentikan penayangan promosi segera saat proyek ditutup/diarsipkan/dihapus.
        static::updated(function (Project $project) {
            if ($project->wasChanged('status') && ! $project->isPublished()) {
                app(\App\Services\PromotionService::class)->stopIneligibleForProject($project);
            }
        });
        static::deleted(function (Project $project) {
            app(\App\Services\PromotionService::class)->stopIneligibleForProject($project);
        });
    }

    protected function casts(): array
    {
        return [
            'start_date'               => 'date',
            'end_date'                 => 'date',
            'registration_deadline'    => 'date',
            'deadline'                 => 'date',
            'talents_needed'           => 'integer',
            'quota'                    => 'integer',
            'workstyle_characteristics'=> 'array',
            'workstyle_requirements'   => 'array',
            'workstyle_confirmed'      => 'boolean',
            'skills'                   => 'array',
        ];
    }

    // ── Accessor aliases so both old & new code can read the same value ──────

    /** work_method falls back to work_type */
    public function getWorkMethodAttribute(?string $value): string
    {
        if (!empty($value)) return $value;
        $wt = $this->getRawOriginal('work_type') ?? '';
        return match (strtolower($wt)) {
            'remote'            => 'remote',
            'hybrid'            => 'hybrid',
            default             => 'onsite',
        };
    }

    /** talents_needed falls back to quota */
    public function getTalentsNeededAttribute(?int $value): int
    {
        return $value ?? (int)($this->getRawOriginal('quota') ?? 1);
    }

    /** registration_deadline falls back to deadline */
    public function getRegistrationDeadlineAttribute($value)
    {
        if (!empty($value)) return $value ? \Carbon\Carbon::parse($value) : null;
        $dl = $this->getRawOriginal('deadline');
        return $dl ? \Carbon\Carbon::parse($dl) : null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(ProjectApplication::class, 'project_id');
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(ProjectPromotion::class, 'project_id');
    }

    /** Promosi yang sedang tayang menurut timestamp. */
    public function livePromotion(): HasOne
    {
        return $this->hasOne(ProjectPromotion::class, 'project_id')->live()->latest('start_at');
    }

    public function latestPromotion(): HasOne
    {
        return $this->hasOne(ProjectPromotion::class, 'project_id')->latestOfMany();
    }

    /**
     * Ada promosi aktif, checkout yang masih dalam batas waktu bayar, atau pembayaran
     * yang sedang/menunggu ditinjau Admin (mencegah Penyedia membayar dua kali).
     */
    public function hasBlockingPromotion(): bool
    {
        return $this->promotions()
            ->where(function ($q) {
                $q->where(fn ($a) => $a->live())
                  ->orWhereHas('transaction', function ($t) {
                      $t->where(fn ($p) => $p->where('status', PromotionTransaction::STATUS_PENDING)->where('expires_at', '>', now()))
                        ->orWhere(fn ($r) => $r->awaitingReview());
                  });
            })
            ->exists();
    }

    /** Masih dipublikasikan, belum dihapus, dan kuota Talenta belum penuh. */
    public function isOpenForTalent(): bool
    {
        return ! $this->trashed() && $this->isPublished() && ! $this->isQuotaFilled();
    }

    /** Batas pendaftaran (registration_deadline / deadline) sudah lewat → tidak lagi menerima lamaran. */
    public function isRegistrationOver(): bool
    {
        $deadline = $this->registration_deadline;

        return $deadline !== null && $deadline->copy()->endOfDay()->lt(now());
    }

    /** Alasan proyek tidak bisa dipromosikan (null = boleh). */
    public function promotionBlocker(): ?string
    {
        return match (true) {
            $this->trashed()              => 'Proyek sudah dihapus',
            $this->isDraft()              => 'Masih draf — publikasikan dulu',
            $this->isClosed()             => 'Proyek sudah ditutup',
            $this->isArchived()           => 'Proyek sudah diarsipkan',
            ! $this->isPublished()        => 'Belum dipublikasikan',
            $this->isRegistrationOver()   => 'Batas pendaftaran sudah lewat',
            $this->isQuotaFilled()        => 'Kuota Talenta sudah terpenuhi',
            $this->hasBlockingPromotion() => 'Sedang dipromosikan / pembayaran sedang diproses',
            default                       => null,
        };
    }

    public function isPromoted(): bool
    {
        return $this->relationLoaded('livePromotion')
            ? $this->livePromotion !== null
            : $this->livePromotion()->exists();
    }

    /** URL gambar sampul: unggahan Penyedia, atau ilustrasi bawaan sesuai kategori. */
    public function getCoverUrlAttribute(): string
    {
        return $this->cover_image
            ? route('project.cover', ['project' => $this->id, 'v' => substr(md5($this->cover_image), 0, 8)])
            : \App\Support\ProjectCover::fallback((string) $this->category, (string) $this->title);
    }

    public function acceptedApplications(): HasMany
    {
        return $this->hasMany(ProjectApplication::class, 'project_id')->where('status', 'diterima');
    }

    /**
     * Proyek yang boleh dipromosikan ke talenta / publik:
     * sudah dipublikasikan DAN kuota talenta belum terpenuhi oleh pelamar yang diterima.
     */
    public function scopeOpenForTalent($query)
    {
        return $query
            ->whereIn('status', [self::STATUS_PUBLISHED, self::STATUS_DIPUBLIKASIKAN])
            ->whereRaw(
                '(select count(*) from project_applications pa where pa.project_id = projects.id and pa.status = ?)'
                . ' < COALESCE(projects.talents_needed, projects.quota, 1)',
                [ProjectApplication::STATUS_DITERIMA]
            );
    }

    /**
     * Muat jumlah lamaran aktif & diterima dalam satu query (dipakai ProjectApplyService
     * untuk halaman daftar agar tidak N+1).
     */
    public function scopeWithApplyCounts($query)
    {
        return $query->withCount([
            'applications as active_applications_count' => fn ($q) => $q->whereIn('status', ProjectApplication::ACTIVE_STATUSES),
            'acceptedApplications',
        ]);
    }

    public function acceptedCount(): int
    {
        return array_key_exists('accepted_applications_count', $this->attributes)
            ? (int) $this->accepted_applications_count
            : $this->acceptedApplications()->count();
    }

    /** Kuota sudah terisi penuh oleh talenta yang diterima → promosi otomatis disembunyikan. */
    public function isQuotaFilled(): bool
    {
        return $this->acceptedCount() >= max(1, (int) $this->talents_needed);
    }

    public function pendingApplications(): HasMany
    {
        return $this->hasMany(ProjectApplication::class, 'project_id')->whereIn('status', ['menunggu', 'dalam_review']);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT       => 'Draf',
            self::STATUS_PUBLISHED   => 'Dipublikasikan',
            self::STATUS_CLOSED      => 'Ditutup',
            self::STATUS_ARCHIVED    => 'Diarsipkan',
            'Dipublikasikan'         => 'Dipublikasikan',
            'Ditutup'                => 'Ditutup',
            'Draf'                   => 'Draf',
            default                  => ucfirst($this->status),
        };
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /** isPublished: also accepts legacy 'Dipublikasikan' status */
    public function isPublished(): bool
    {
        return in_array($this->status, [self::STATUS_PUBLISHED, 'Dipublikasikan'], true);
    }

    public function isClosed(): bool
    {
        return in_array($this->status, [self::STATUS_CLOSED, 'Ditutup'], true);
    }

    public function isArchived(): bool
    {
        return $this->status === self::STATUS_ARCHIVED;
    }
}
