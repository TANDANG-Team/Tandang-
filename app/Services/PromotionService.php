<?php

namespace App\Services;

use App\Contracts\PromotionPaymentGateway;
use App\Exceptions\PromotionException;
use App\Models\Project;
use App\Models\ProjectPromotion;
use App\Models\PromotionAuditLog;
use App\Models\PromotionPackage;
use App\Models\PromotionTransaction;
use App\Models\ProviderNotification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Aturan bisnis Sponsored Project. Satu-satunya tempat yang mengubah status transaksi & promosi.
 *
 * Alur QRIS statis:
 *   checkout (Pending, 5 menit) → Penyedia kirim bukti → Menunggu Verifikasi (timer berhenti)
 *   → Admin cocokkan mutasi merchant → approveManual() = Paid + promosi aktif 96 jam | rejectManual().
 * Menekan "Saya Sudah Bayar" / mengunggah screenshot TIDAK pernah mengaktifkan promosi.
 */
class PromotionService
{
    public function __construct(
        private PromotionPaymentGateway $gateway,
        private PromotionEligibilityService $eligibility,
    ) {}

    // ── Checkout ────────────────────────────────────────────────────────────

    /** @throws PromotionException */
    public function checkout(Project $project, User $penyedia, ?PromotionPackage $package = null): ProjectPromotion
    {
        $package ??= PromotionPackage::current();

        if (! $package || ! $package->is_active) {
            throw new PromotionException('Paket Sponsored Project sedang tidak tersedia.');
        }
        if (! $penyedia->isPenyedia()) {
            throw new PromotionException('Hanya akun Penyedia yang dapat menggunakan Sponsored Project.');
        }
        if ((int) $project->user_id !== (int) $penyedia->id) {
            throw new PromotionException('Proyek tidak dimiliki oleh akun ini.');
        }
        // Syarat rating usaha (≥ 4,0 nilai asli & ≥ 3 penilaian valid) — divalidasi di server, bukan hanya tombol.
        if ($reason = $this->eligibility->providerBlocker($penyedia)) {
            throw new PromotionException($reason);
        }

        return DB::transaction(function () use ($project, $penyedia, $package) {
            $locked = Project::withTrashed()->whereKey($project->id)->lockForUpdate()->firstOrFail();
            $this->expireStaleTransactions($locked->id);

            if ($reason = $locked->promotionBlocker()) {
                throw new PromotionException("Proyek tidak bisa dipromosikan: {$reason}.");
            }

            $now = now();
            $transaction = PromotionTransaction::create([
                'user_id'            => $penyedia->id,
                'transaction_number' => $this->newTransactionNumber(),
                'amount'             => $package->price,                     // dari database, bukan input
                'status'             => PromotionTransaction::STATUS_PENDING,
                'payment_method'     => $this->gateway->name(),
                'is_simulation'      => false,
                'expires_at'         => $now->copy()->addSeconds(config('promotion.payment_window_seconds')),
            ]);

            $charge = $this->gateway->createCharge($transaction);
            $transaction->update([
                'payment_method'    => $charge['method'],
                'payment_reference' => $charge['reference'],
                'qr_payload'        => $charge['qr_payload'],
                'gateway_payload'   => $charge['raw'] ?? null,
            ]);

            $promotion = ProjectPromotion::create([
                'project_id'               => $locked->id,
                'user_id'                  => $penyedia->id,
                'promotion_package_id'     => $package->id,
                'promotion_transaction_id' => $transaction->id,
                'package_name'             => $package->name,
                'price'                    => $package->price,
                'duration_hours'           => $package->duration_hours,
                'status'                   => ProjectPromotion::STATUS_PENDING_PAYMENT,
            ]);

            PromotionAuditLog::record($promotion, 'checkout', "{$transaction->transaction_number} · {$transaction->formatted_amount} · batas bayar " . config('promotion.payment_window_seconds') / 60 . ' menit', $penyedia);

            return $promotion;
        });
    }

    // ── Konfirmasi pembayaran oleh Penyedia ─────────────────────────────────

    /**
     * Simpan bukti "Saya Sudah Bayar". Tepat waktu → Menunggu Verifikasi (timer berhenti).
     * Terlambat → tetap Kedaluwarsa, ditandai untuk ditinjau Admin (tidak aktif otomatis).
     *
     * @param array{payer_name?:?string, payer_reference?:?string, payer_amount:int, payer_paid_at?:?string} $data
     * @return string 'on_time' | 'late'
     * @throws PromotionException
     */
    public function submitProof(PromotionTransaction $transaction, User $penyedia, array $data, string $proofPath, string $proofHash): string
    {
        $result = DB::transaction(function () use ($transaction, $penyedia, $data, $proofPath, $proofHash) {
            $trx = PromotionTransaction::whereKey($transaction->id)->lockForUpdate()->firstOrFail();
            $promotion = ProjectPromotion::where('promotion_transaction_id', $trx->id)->first();

            if ((int) $trx->user_id !== (int) $penyedia->id) {
                return 'Transaksi ini bukan milik akun Anda.';
            }
            if ($trx->is_simulation || ! in_array($trx->status, [PromotionTransaction::STATUS_PENDING, PromotionTransaction::STATUS_EXPIRED], true)) {
                return 'Konfirmasi tidak dapat dikirim untuk transaksi berstatus ' . $trx->status_label . '.';
            }
            if ($trx->late_submission || $trx->submitted_at) {
                return 'Konfirmasi untuk transaksi ini sudah pernah dikirim.';
            }

            // Satu bukti / referensi tidak boleh dipakai untuk beberapa transaksi.
            $reused = PromotionTransaction::whereKeyNot($trx->id)
                ->whereNotIn('status', [PromotionTransaction::STATUS_REJECTED, PromotionTransaction::STATUS_FAILED])
                ->where(function ($q) use ($proofHash, $data) {
                    $q->where('proof_hash', $proofHash);
                    if (filled($data['payer_reference'] ?? null)) {
                        $q->orWhere('payer_reference', $data['payer_reference']);
                    }
                })->exists();
            if ($reused) {
                return 'Bukti atau nomor referensi pembayaran ini sudah digunakan pada transaksi lain.';
            }

            $late = $trx->status === PromotionTransaction::STATUS_EXPIRED || $trx->isPaymentWindowOver();
            if ($late && $trx->isPending()) {
                $this->expireTransaction($trx, $promotion);
                $trx->refresh();
            }

            $trx->update([
                'proof_path'      => $proofPath,
                'proof_hash'      => $proofHash,
                'payer_name'      => $data['payer_name'] ?? null,
                'payer_reference' => $data['payer_reference'] ?? null,
                'payer_amount'    => $data['payer_amount'],
                'payer_paid_at'   => $data['payer_paid_at'] ?? null,
                'submitted_at'    => now(),
                'late_submission' => $late,
                'status'          => $late ? PromotionTransaction::STATUS_EXPIRED : PromotionTransaction::STATUS_AWAITING_VERIFICATION,
            ]);

            PromotionAuditLog::record($promotion, $late ? 'proof_late' : 'proof_submitted',
                'Nominal dilaporkan Rp' . number_format($data['payer_amount'], 0, ',', '.') . (filled($data['payer_reference'] ?? null) ? " · ref {$data['payer_reference']}" : ''),
                $penyedia, $trx);

            // Dana mungkin sudah terkirim: jangan ditolak otomatis, tandai agar Admin memutuskan (aktivasi/refund).
            if ($reason = $this->eligibility->providerBlocker($penyedia)) {
                $this->flagForAdmin($trx, $promotion, 'Saat konfirmasi pembayaran, Penyedia tidak memenuhi syarat promosi: ' . $reason);
            }

            return $late ? 'late' : 'on_time';
        });

        if (! in_array($result, ['late', 'on_time'], true)) {
            throw new PromotionException($result);
        }

        return $result;
    }

    // ── Keputusan Admin (QRIS manual) ───────────────────────────────────────

    /**
     * Admin menyatakan dana benar-benar diterima di rekening merchant → Paid + aktivasi.
     * Berlaku untuk transaksi Menunggu Verifikasi, atau konfirmasi terlambat yang ditinjau.
     *
     * @throws PromotionException
     */
    public function approveManual(PromotionTransaction $transaction, User $admin, ?string $note = null, bool $overrideEligibility = false): ProjectPromotion
    {
        if ($overrideEligibility && blank($note)) {
            throw new PromotionException('Tuliskan catatan keputusan bila menyetujui Penyedia yang tidak lagi memenuhi syarat.');
        }

        return $this->settle($transaction, $admin, function (PromotionTransaction $trx) {
            if ($trx->isPaid()) {
                return null; // idempoten
            }
            if ($trx->is_simulation || ! $trx->needsReview()) {
                return 'Transaksi tidak sedang menunggu verifikasi.';
            }
            if ($trx->payer_reference && PromotionTransaction::whereKeyNot($trx->id)->where('status', PromotionTransaction::STATUS_PAID)->where('payer_reference', $trx->payer_reference)->exists()) {
                return 'Nomor referensi ini sudah dipakai untuk transaksi lain yang lunas.';
            }

            return null;
        }, isSimulation: false, auditAction: 'payment_approved', note: $note, overrideEligibility: $overrideEligibility);
    }

    /** @throws PromotionException */
    public function rejectManual(PromotionTransaction $transaction, User $admin, string $reason): void
    {
        $error = DB::transaction(function () use ($transaction, $admin, $reason) {
            $trx = PromotionTransaction::whereKey($transaction->id)->lockForUpdate()->firstOrFail();
            if (! $trx->needsReview()) {
                return 'Transaksi tidak sedang menunggu verifikasi.';
            }
            $promotion = ProjectPromotion::where('promotion_transaction_id', $trx->id)->lockForUpdate()->first();

            $trx->update([
                'status'        => PromotionTransaction::STATUS_REJECTED,
                'verified_by'   => $admin->id,
                'verified_at'   => now(),
                'decision_note' => $reason,
                'failure_reason' => $reason,
                'needs_admin_attention' => false,
            ]);
            $promotion?->update(['status' => ProjectPromotion::STATUS_CANCELLED, 'stop_reason' => 'Pembayaran ditolak: ' . $reason]);

            PromotionAuditLog::record($promotion, 'payment_rejected', $reason, $admin, $trx);
            $this->notifyProvider($trx, 'Pembayaran promosi ditolak', "Pembayaran {$trx->transaction_number} ditolak Admin. Alasan: {$reason}", $promotion);

            return null;
        });

        if ($error) {
            throw new PromotionException($error);
        }
    }

    // ── Simulasi (Development Mode) ─────────────────────────────────────────

    /** Hanya untuk pengujian: tandai Paid (is_simulation) bila masih Pending & belum lewat 5 menit. */
    public function simulatePaid(PromotionTransaction $transaction, User $actor): ProjectPromotion
    {
        if (! config('promotion.simulation_enabled')) {
            throw new PromotionException('Simulasi pembayaran tidak tersedia di environment ini.');
        }

        return $this->settle($transaction, $actor, function (PromotionTransaction $trx) {
            if ($trx->isPaid()) {
                return null;
            }

            return $trx->isPending() ? null : "Transaksi berstatus {$trx->status_label} dan tidak dapat diproses.";
        }, isSimulation: true, auditAction: 'payment_simulated', requireOpenWindow: true);
    }

    /**
     * Konfirmasi otomatis dari payment gateway resmi (belum diaktifkan). Dipanggil hanya dari
     * handler webhook yang signature-nya sudah diverifikasi — tidak ada endpoint publik untuk ini.
     */
    public function markPaid(PromotionTransaction $transaction, User $actor, string $gatewayReference): ProjectPromotion
    {
        if (! $this->gateway->confirmsAutomatically()) {
            throw new PromotionException('Metode pembayaran aktif tidak mendukung konfirmasi otomatis.');
        }

        return $this->settle($transaction, $actor, function (PromotionTransaction $trx) use ($gatewayReference) {
            if ($trx->isPaid()) {
                return null;
            }
            if (! $trx->isPending()) {
                return 'Transaksi tidak menunggu pembayaran.';
            }

            return $gatewayReference === $trx->payment_reference ? null : 'Referensi pembayaran tidak cocok.';
        }, isSimulation: false, auditAction: 'payment_gateway', requireOpenWindow: true);
    }

    /** Simulasi gagal (development) — promosi tidak diaktifkan. */
    public function markFailed(PromotionTransaction $transaction, string $reason, ?User $actor = null): void
    {
        DB::transaction(function () use ($transaction, $reason, $actor) {
            $trx = PromotionTransaction::whereKey($transaction->id)->lockForUpdate()->firstOrFail();
            if (! $trx->isPending()) {
                throw new PromotionException('Hanya transaksi yang masih menunggu pembayaran yang bisa digagalkan.');
            }
            $promotion = ProjectPromotion::where('promotion_transaction_id', $trx->id)->lockForUpdate()->first();
            $trx->is_simulation = true;
            $this->failTransaction($trx, $promotion, $reason, $actor);
        });
    }

    /** Penyedia membatalkan checkout yang belum dikonfirmasi. */
    public function cancel(ProjectPromotion $promotion, User $penyedia): void
    {
        DB::transaction(function () use ($promotion, $penyedia) {
            $promotion = ProjectPromotion::whereKey($promotion->id)->lockForUpdate()->firstOrFail();
            $trx = PromotionTransaction::whereKey($promotion->promotion_transaction_id)->lockForUpdate()->first();
            if (! $promotion->isPendingPayment() || ! $trx?->isPending()) {
                throw new PromotionException('Hanya checkout yang belum dikonfirmasi yang bisa dibatalkan.');
            }
            $this->failTransaction($trx, $promotion, 'Dibatalkan oleh Penyedia sebelum membayar.', $penyedia, 'cancelled');
        });
    }

    // ── Penghentian ─────────────────────────────────────────────────────────

    public function stopByAdmin(ProjectPromotion $promotion, User $admin, string $reason): ProjectPromotion
    {
        return DB::transaction(function () use ($promotion, $admin, $reason) {
            $promotion = ProjectPromotion::whereKey($promotion->id)->lockForUpdate()->firstOrFail();
            if (! $promotion->isLive()) {
                throw new PromotionException('Hanya promosi yang sedang tayang yang bisa dihentikan.');
            }
            $this->stop($promotion, $reason, $admin);
            PromotionAuditLog::record($promotion, 'stopped_admin', $reason, $admin);

            return $promotion->fresh();
        });
    }

    public function stopIneligibleForProject(Project $project): int
    {
        if ($project->isOpenForTalent()) {
            return 0;
        }

        $reason = match (true) {
            $project->trashed()       => 'Proyek dihapus',
            ! $project->isPublished() => 'Proyek tidak lagi dipublikasikan (' . $project->status_label . ')',
            default                   => 'Kuota Talenta sudah terpenuhi',
        };

        $count = 0;
        ProjectPromotion::where('project_id', $project->id)->live()->get()
            ->each(function (ProjectPromotion $promotion) use ($reason, &$count) {
                $this->stop($promotion, $reason, null);
                PromotionAuditLog::record($promotion, 'stopped_system', $reason);
                $count++;
            });

        return $count;
    }

    // ── Scheduler ───────────────────────────────────────────────────────────

    /** @return array{expired_transactions:int, ended:int, stopped:int} */
    public function runMaintenance(): array
    {
        $expired = $this->expireStaleTransactions();

        $ended = 0;
        ProjectPromotion::where('status', ProjectPromotion::STATUS_ACTIVE)
            ->where('end_at', '<=', now())
            ->get()
            ->each(function (ProjectPromotion $promotion) use (&$ended) {
                $updated = ProjectPromotion::whereKey($promotion->id)
                    ->where('status', ProjectPromotion::STATUS_ACTIVE)
                    ->update(['status' => ProjectPromotion::STATUS_ENDED, 'updated_at' => now()]);
                if ($updated) {
                    PromotionAuditLog::record($promotion, 'ended', 'Berakhir ' . $promotion->end_at->format('d/m/Y H:i'));
                    $ended++;
                }
            });

        $stopped = 0;
        ProjectPromotion::live()->with('project')->get()
            ->each(function (ProjectPromotion $promotion) use (&$stopped) {
                if ($promotion->project) {
                    $stopped += $this->stopIneligibleForProject($promotion->project);
                }
            });

        return ['expired_transactions' => $expired, 'ended' => $ended, 'stopped' => $stopped];
    }

    /** Pending yang lewat 5 menit → Expired (berdasarkan waktu server). */
    public function expireStaleTransactions(?int $projectId = null): int
    {
        $query = PromotionTransaction::where('status', PromotionTransaction::STATUS_PENDING)
            ->where('expires_at', '<=', now());

        if ($projectId) {
            $query->whereHas('promotion', fn ($q) => $q->where('project_id', $projectId));
        }

        $count = 0;
        $query->get()->each(function (PromotionTransaction $trx) use (&$count) {
            $this->expireTransaction($trx, ProjectPromotion::where('promotion_transaction_id', $trx->id)->first());
            $count++;
        });

        return $count;
    }

    // ── Internal ────────────────────────────────────────────────────────────

    /**
     * Tandai Paid & aktifkan promosi 96 jam dalam satu transaksi DB dengan baris terkunci.
     * Pesan error dikembalikan dari dalam closure agar perubahan status (mis. kedaluwarsa) tetap tersimpan.
     */
    private function settle(PromotionTransaction $transaction, User $actor, callable $guard, bool $isSimulation, string $auditAction, bool $requireOpenWindow = false, ?string $note = null, bool $overrideEligibility = false): ProjectPromotion
    {
        $result = DB::transaction(function () use ($transaction, $actor, $guard, $isSimulation, $auditAction, $requireOpenWindow, $note, $overrideEligibility) {
            $trx = PromotionTransaction::whereKey($transaction->id)->lockForUpdate()->firstOrFail();
            $promotion = ProjectPromotion::where('promotion_transaction_id', $trx->id)->lockForUpdate()->firstOrFail();

            if ($error = $guard($trx)) {
                return $error;
            }
            if ($trx->isPaid()) {
                return $promotion; // sudah diproses → tidak ada aktivasi ganda
            }
            if ($requireOpenWindow && $trx->isPaymentWindowOver()) {
                $this->expireTransaction($trx, $promotion);
                return 'Waktu pembayaran (5 menit) sudah habis. Silakan buat pembayaran baru.';
            }
            if ((int) $trx->amount !== (int) $promotion->price) {
                return 'Nominal transaksi tidak sesuai harga paket.';
            }

            $project = Project::withTrashed()->whereKey($promotion->project_id)->lockForUpdate()->first();

            // Cek ulang seluruh syarat (rating Penyedia + proyek) tepat sebelum aktivasi.
            if ($block = $this->eligibility->activationBlocker($promotion, $project)) {
                $providerOnly = $block['type'] === 'provider';

                if ($isSimulation) {
                    // Belum ada dana nyata → transaksi tidak boleh dilanjutkan.
                    $this->failTransaction($trx, $promotion, 'Tidak memenuhi syarat saat aktivasi: ' . $block['reason'], $actor);
                    return $block['reason'];
                }
                if (! ($providerOnly && $overrideEligibility)) {
                    // Dana nyata (verifikasi Admin / gateway): jangan hilangkan hak Penyedia — tandai untuk Admin.
                    $this->flagForAdmin($trx, $promotion, 'Tidak memenuhi syarat saat aktivasi: ' . $block['reason']);
                    return $block['reason'] . ' Transaksi ditandai untuk penanganan Admin (tolak dengan pengembalian dana, atau setujui dengan catatan bila ada kebijakan khusus).'
                        . ($providerOnly ? '' : ' Persetujuan tidak dapat dipaksakan karena proyek tidak lagi layak tayang.');
                }
                PromotionAuditLog::record($promotion, 'eligibility_override', $block['reason'] . ($note ? " · {$note}" : ''), $actor, $trx);
            }

            $trx->update([
                'status'        => PromotionTransaction::STATUS_PAID,
                'paid_at'       => now(),
                'is_simulation' => $isSimulation,
                'verified_by'   => $isSimulation ? null : $actor->id,
                'verified_at'   => $isSimulation ? null : now(),
                'decision_note' => $note,
                'needs_admin_attention' => false, // sudah diputuskan; attention_reason tetap untuk jejak audit
            ]);
            PromotionAuditLog::record($promotion, $auditAction, $trx->transaction_number . ($note ? " · {$note}" : '') . ($trx->late_submission ? ' · konfirmasi terlambat' : ''), $actor, $trx);

            $start = now();
            $end   = $start->copy()->addHours($promotion->duration_hours); // tepat 4 × 24 jam
            $promotion->update(['status' => ProjectPromotion::STATUS_ACTIVE, 'start_at' => $start, 'end_at' => $end, 'stop_reason' => null]);
            PromotionAuditLog::record($promotion, 'activated', 'Tayang ' . $start->format('d/m/Y H:i') . ' – ' . $end->format('d/m/Y H:i') . ' UTC', $actor);

            $this->notifyProvider($trx, 'Sponsored Project aktif', "Promosi \"{$project->title}\" aktif selama 4 hari.", $promotion);

            return $promotion->fresh();
        });

        if (is_string($result)) {
            throw new PromotionException($result);
        }

        return $result;
    }

    /** Tandai transaksi agar Admin memutuskan; tidak mengubah status/dana. */
    private function flagForAdmin(PromotionTransaction $trx, ?ProjectPromotion $promotion, string $reason): void
    {
        if ($trx->needs_admin_attention && $trx->attention_reason === $reason) {
            return;
        }
        $trx->update(['needs_admin_attention' => true, 'attention_reason' => mb_substr($reason, 0, 500)]);
        if ($promotion) {
            PromotionAuditLog::record($promotion, 'eligibility_flag', $reason, null, $trx);
        }
    }

    private function stop(ProjectPromotion $promotion, string $reason, ?User $by): void
    {
        $promotion->update([
            'status'                    => ProjectPromotion::STATUS_STOPPED,
            'stopped_at'                => now(),
            'stopped_by'                => $by?->id,
            'stop_reason'               => $reason,
            'remaining_seconds_at_stop' => max(0, (int) now()->diffInSeconds($promotion->end_at, false)),
        ]);
    }

    private function expireTransaction(PromotionTransaction $trx, ?ProjectPromotion $promotion): void
    {
        $updated = PromotionTransaction::whereKey($trx->id)
            ->where('status', PromotionTransaction::STATUS_PENDING)
            ->update(['status' => PromotionTransaction::STATUS_EXPIRED, 'updated_at' => now()]);

        if ($updated && $promotion) {
            ProjectPromotion::whereKey($promotion->id)
                ->where('status', ProjectPromotion::STATUS_PENDING_PAYMENT)
                ->update(['status' => ProjectPromotion::STATUS_CANCELLED, 'stop_reason' => 'Batas waktu pembayaran habis.', 'updated_at' => now()]);
            PromotionAuditLog::record($promotion, 'payment_expired', $trx->transaction_number, null, $trx);
        }
    }

    private function failTransaction(?PromotionTransaction $trx, ?ProjectPromotion $promotion, string $reason, ?User $actor, string $action = 'payment_failed'): void
    {
        $trx?->fill(['status' => PromotionTransaction::STATUS_FAILED, 'failed_at' => now(), 'failure_reason' => $reason])->save();
        $promotion?->update(['status' => ProjectPromotion::STATUS_CANCELLED, 'stop_reason' => $reason]);

        if ($promotion) {
            PromotionAuditLog::record($promotion, $action, $reason, $actor, $trx);
        }
    }

    private function notifyProvider(PromotionTransaction $trx, string $title, string $message, ?ProjectPromotion $promotion): void
    {
        ProviderNotification::create([
            'user_id'    => $trx->user_id,
            'title'      => $title,
            'message'    => $message,
            'type'       => 'promotion',
            'target_url' => $promotion ? route('penyedia.promotions.payment', $promotion, false) : '/penyedia/promosi',
            'is_read'    => false,
        ]);
    }

    private function newTransactionNumber(): string
    {
        do {
            $number = 'TDG-' . now()->format('ymd') . '-' . strtoupper(Str::random(6));
        } while (PromotionTransaction::where('transaction_number', $number)->exists());

        return $number;
    }
}
