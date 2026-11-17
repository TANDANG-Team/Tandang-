<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectPromotion extends Model
{
    use HasFactory;

    public const STATUS_PENDING_PAYMENT = 'pending_payment';
    public const STATUS_ACTIVE          = 'active';
    public const STATUS_ENDED           = 'ended';
    public const STATUS_STOPPED         = 'stopped';
    public const STATUS_CANCELLED       = 'cancelled';

    protected $fillable = [
        'project_id', 'user_id', 'promotion_package_id', 'promotion_transaction_id',
        'package_name', 'price', 'duration_hours', 'status', 'start_at', 'end_at',
        'stopped_at', 'stopped_by', 'stop_reason', 'remaining_seconds_at_stop',
    ];

    protected function casts(): array
    {
        return [
            'price'                     => 'integer',
            'duration_hours'            => 'integer',
            'start_at'                  => 'datetime',
            'end_at'                    => 'datetime',
            'stopped_at'                => 'datetime',
            'remaining_seconds_at_stop' => 'integer',
        ];
    }

    // ── Relasi ──────────────────────────────────────────────────────────────

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class)->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(PromotionPackage::class, 'promotion_package_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PromotionTransaction::class, 'promotion_transaction_id');
    }

    public function stopper(): BelongsTo
    {
        return $this->belongsTo(User::class, 'stopped_by');
    }

    public function events(): HasMany
    {
        return $this->hasMany(PromotionEvent::class);
    }

    public function attributions(): HasMany
    {
        return $this->hasMany(PromotionAttribution::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(PromotionAuditLog::class)->latest('created_at')->latest('id');
    }

    // ── Scope ───────────────────────────────────────────────────────────────

    /** Aktif menurut timestamp — benar walau scheduler belum sempat jalan. */
    public function scopeLive(Builder $query): Builder
    {
        $now = now();

        return $query->where('status', self::STATUS_ACTIVE)
            ->where('start_at', '<=', $now)
            ->where('end_at', '>', $now);
    }

    /** Layak tayang ke Talenta: aktif + proyek masih dibuka & masih butuh Talenta. */
    public function scopeForTalent(Builder $query): Builder
    {
        return $query->live()->whereHas('project', function ($q) {
            $q->withoutTrashed()->openForTalent();
        });
    }

    public function scopeWithStats(Builder $query): Builder
    {
        return $query->withCount([
            'events as impressions_total' => fn ($q) => $q->where('type', PromotionEvent::TYPE_IMPRESSION),
            'events as clicks_total'      => fn ($q) => $q->where('type', PromotionEvent::TYPE_CLICK),
            'attributions as applications_total',
        ]);
    }

    // ── Status ──────────────────────────────────────────────────────────────

    public function isLive(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && $this->start_at && $this->end_at
            && $this->start_at->lte(now()) && $this->end_at->gt(now());
    }

    public function isPendingPayment(): bool
    {
        return $this->status === self::STATUS_PENDING_PAYMENT;
    }

    /** Status efektif untuk ditampilkan (aktif yang sudah lewat end_at = berakhir). */
    public function getEffectiveStatusAttribute(): string
    {
        if ($this->status === self::STATUS_ACTIVE && $this->end_at && $this->end_at->lte(now())) {
            return self::STATUS_ENDED;
        }

        return $this->status;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->effective_status) {
            self::STATUS_PENDING_PAYMENT => 'Menunggu Pembayaran',
            self::STATUS_ACTIVE          => 'Aktif',
            self::STATUS_ENDED           => 'Berakhir',
            self::STATUS_STOPPED         => 'Dihentikan',
            self::STATUS_CANCELLED       => 'Dibatalkan',
            default                      => ucfirst($this->status),
        };
    }

    public function getStatusToneAttribute(): string
    {
        return match ($this->effective_status) {
            self::STATUS_ACTIVE          => 'success',
            self::STATUS_PENDING_PAYMENT => 'warning',
            self::STATUS_STOPPED         => 'danger',
            default                      => 'muted',
        };
    }

    /** Status tayang yang lebih jujur: aktif tapi proyeknya tidak lagi memenuhi syarat. */
    public function isSuppressed(): bool
    {
        $project = $this->project;

        return $this->isLive() && (! $project || $project->trashed() || ! $project->isOpenForTalent());
    }

    public function getRemainingSecondsAttribute(): int
    {
        return $this->isLive() ? max(0, now()->diffInSeconds($this->end_at, false)) : 0;
    }

    public function getRemainingLabelAttribute(): string
    {
        $seconds = (int) $this->remaining_seconds;
        if ($seconds <= 0) {
            return '—';
        }

        $days    = intdiv($seconds, 86400);
        $hours   = intdiv($seconds % 86400, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        return trim(($days ? "{$days} hari " : '') . ($hours ? "{$hours} jam " : '') . ($days ? '' : "{$minutes} menit"));
    }

    public function getFormattedPriceAttribute(): string
    {
        return 'Rp' . number_format($this->price, 0, ',', '.');
    }

    /** CTR aman dari pembagian nol. */
    public static function ctr(int $clicks, int $impressions): float
    {
        return $impressions > 0 ? round($clicks / $impressions * 100, 1) : 0.0;
    }
}
