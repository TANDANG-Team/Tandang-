<?php

namespace App\Contracts;

use App\Models\PromotionTransaction;

/**
 * Titik integrasi metode pembayaran.
 *
 *  - ManualQrisGateway  : QRIS statis merchant. Tidak ada konfirmasi otomatis; Admin memverifikasi
 *                         dana masuk lalu memanggil PromotionService::approveManual().
 *  - (nanti) gateway    : QRIS dinamis yang dibuat server-side via Midtrans/Xendit dengan kredensial resmi.
 *                         Status Paid HANYA dari webhook yang signature-nya terverifikasi → PromotionService::markPaid().
 *
 * Simulasi development bukan gateway; ia ditangani PromotionService::simulatePaid() dan ditandai is_simulation.
 */
interface PromotionPaymentGateway
{
    /** @return array{method: string, reference: ?string, qr_payload: ?string, raw?: array} */
    public function createCharge(PromotionTransaction $transaction): array;

    public function name(): string;

    /** Apakah status Paid dapat dikonfirmasi otomatis (webhook resmi)? */
    public function confirmsAutomatically(): bool;
}
