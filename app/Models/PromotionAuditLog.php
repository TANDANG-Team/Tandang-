<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromotionAuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['project_promotion_id', 'promotion_transaction_id', 'user_id', 'action', 'details', 'created_at'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(ProjectPromotion::class, 'project_promotion_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(?ProjectPromotion $promotion, string $action, ?string $details = null, ?User $user = null, ?PromotionTransaction $transaction = null): self
    {
        return self::create([
            'project_promotion_id'     => $promotion?->id,
            'promotion_transaction_id' => $transaction?->id ?? $promotion?->promotion_transaction_id,
            'user_id'                  => $user?->id,
            'action'                   => $action,
            'details'                  => $details,
            'created_at'               => now(),
        ]);
    }

    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'checkout'          => 'Checkout dibuat',
            'proof_submitted'   => 'Bukti pembayaran dikirim',
            'proof_late'        => 'Konfirmasi terlambat dikirim',
            'payment_approved'  => 'Pembayaran disetujui Admin',
            'payment_rejected'  => 'Pembayaran ditolak Admin',
            'payment_simulated' => 'Pembayaran berhasil (simulasi)',
            'payment_gateway'   => 'Pembayaran dikonfirmasi gateway',
            'payment_paid'     => 'Pembayaran berhasil',
            'payment_failed'   => 'Pembayaran gagal',
            'payment_expired'  => 'Tagihan kedaluwarsa',
            'activated'        => 'Promosi diaktifkan',
            'eligibility_flag' => 'Ditandai: Penyedia tidak lagi memenuhi syarat',
            'eligibility_override' => 'Admin menyetujui meski tidak memenuhi syarat',
            'cancelled'        => 'Dibatalkan',
            'ended'            => 'Masa tayang berakhir',
            'stopped_admin'    => 'Dihentikan Admin',
            'stopped_system'   => 'Dihentikan otomatis',
            default            => ucfirst(str_replace('_', ' ', $this->action)),
        };
    }
}
