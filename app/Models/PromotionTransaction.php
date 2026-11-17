<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PromotionTransaction extends Model
{
    use HasFactory;

    public const STATUS_PENDING               = 'pending';                // menunggu pembayaran (timer berjalan)
    public const STATUS_AWAITING_VERIFICATION = 'awaiting_verification';  // bukti dikirim, menunggu Admin
    public const STATUS_PAID                  = 'paid';
    public const STATUS_REJECTED              = 'rejected';               // ditolak Admin
    public const STATUS_FAILED                = 'failed';                 // dibatalkan / simulasi gagal
    public const STATUS_EXPIRED               = 'expired';
    public const STATUS_REFUNDED              = 'refunded';

    /** Status & nominal hanya diisi oleh PromotionService, tidak pernah langsung dari request. */
    protected $fillable = [
        'user_id', 'transaction_number', 'amount', 'status', 'payment_method', 'is_simulation', 'payment_reference',
        'qr_payload', 'proof_path', 'proof_hash', 'payer_name', 'payer_reference', 'payer_amount', 'payer_paid_at',
        'submitted_at', 'late_submission', 'verified_by', 'verified_at', 'decision_note', 'needs_admin_attention', 'attention_reason',
        'expires_at', 'paid_at', 'failed_at', 'failure_reason', 'refunded_at', 'refunded_amount', 'gateway_payload',
    ];

    protected $hidden = ['gateway_payload', 'proof_path', 'proof_hash'];

    protected function casts(): array
    {
        return [
            'amount'          => 'integer',
            'payer_amount'    => 'integer',
            'refunded_amount' => 'integer',
            'is_simulation'   => 'boolean',
            'late_submission' => 'boolean',
            'needs_admin_attention' => 'boolean',
            'expires_at'      => 'datetime',
            'paid_at'         => 'datetime',
            'payer_paid_at'   => 'datetime',
            'submitted_at'    => 'datetime',
            'verified_at'     => 'datetime',
            'failed_at'       => 'datetime',
            'refunded_at'     => 'datetime',
            'gateway_payload' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function promotion(): HasOne
    {
        return $this->hasOne(ProjectPromotion::class, 'promotion_transaction_id');
    }

    /** Pendapatan sah: Paid dari pembayaran nyata (bukan simulasi). */
    public function scopeRealRevenue($query)
    {
        return $query->where('is_simulation', false)->whereIn('status', [self::STATUS_PAID, self::STATUS_REFUNDED]);
    }

    /** Antrean Admin: bukti dikirim tepat waktu, atau konfirmasi terlambat yang belum diputuskan. */
    public function scopeAwaitingReview($query)
    {
        return $query->where(function ($q) {
            $q->where('status', self::STATUS_AWAITING_VERIFICATION)
              ->orWhere(fn ($l) => $l->where('status', self::STATUS_EXPIRED)->where('late_submission', true)->whereNull('verified_at'));
        });
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isAwaitingVerification(): bool
    {
        return $this->status === self::STATUS_AWAITING_VERIFICATION;
    }

    public function isLateReview(): bool
    {
        return $this->status === self::STATUS_EXPIRED && $this->late_submission && $this->verified_at === null;
    }

    public function needsReview(): bool
    {
        return $this->isAwaitingVerification() || $this->isLateReview();
    }

    /** Pending tetapi batas 5 menit sudah lewat (terdeteksi walau scheduler belum jalan). */
    public function isPaymentWindowOver(): bool
    {
        return $this->isPending() && $this->expires_at && $this->expires_at->lte(now());
    }

    public function remainingSeconds(): int
    {
        return $this->isPending() && $this->expires_at ? max(0, (int) ceil(now()->diffInSeconds($this->expires_at, false))) : 0;
    }

    public function getFormattedAmountAttribute(): string
    {
        return 'Rp' . number_format($this->amount, 0, ',', '.');
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->isPaymentWindowOver()) {
            return 'Kedaluwarsa';
        }
        if ($this->isLateReview()) {
            return 'Terlambat · Ditinjau Admin';
        }

        return match ($this->status) {
            self::STATUS_PENDING               => 'Menunggu Pembayaran',
            self::STATUS_AWAITING_VERIFICATION => 'Menunggu Verifikasi Admin',
            self::STATUS_PAID                  => $this->is_simulation ? 'Berhasil (Simulasi)' : 'Pembayaran Berhasil',
            self::STATUS_REJECTED              => 'Pembayaran Ditolak',
            self::STATUS_FAILED                => 'Gagal / Dibatalkan',
            self::STATUS_EXPIRED               => 'Pembayaran Kedaluwarsa',
            self::STATUS_REFUNDED              => 'Dikembalikan',
            default                            => ucfirst($this->status),
        };
    }

    public function getStatusToneAttribute(): string
    {
        if ($this->isPaymentWindowOver()) {
            return 'muted';
        }
        if ($this->isLateReview()) {
            return 'info';
        }

        return match ($this->status) {
            self::STATUS_PAID                  => 'success',
            self::STATUS_PENDING               => 'warning',
            self::STATUS_AWAITING_VERIFICATION => 'info',
            self::STATUS_REJECTED, self::STATUS_FAILED => 'danger',
            self::STATUS_REFUNDED              => 'info',
            default                            => 'muted',
        };
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        if ($this->is_simulation) {
            return 'Simulasi (Development Mode)';
        }

        return match ($this->payment_method) {
            'qris_manual' => 'QRIS Statis (verifikasi manual)',
            'qris'        => 'QRIS',
            default       => ucfirst(str_replace('_', ' ', $this->payment_method)),
        };
    }
}
