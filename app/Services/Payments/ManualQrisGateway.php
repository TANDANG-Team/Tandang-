<?php

namespace App\Services\Payments;

use App\Contracts\PromotionPaymentGateway;
use App\Models\PromotionTransaction;

/**
 * QRIS statis milik merchant. Nominal diisi manual oleh pembayar, sehingga sistem
 * TIDAK dapat mengetahui pembayaran secara otomatis — Admin wajib mencocokkan mutasi merchant.
 */
class ManualQrisGateway implements PromotionPaymentGateway
{
    public function name(): string
    {
        return 'qris_manual';
    }

    public function confirmsAutomatically(): bool
    {
        return false;
    }

    public function createCharge(PromotionTransaction $transaction): array
    {
        return [
            'method'     => $this->name(),
            'reference'  => null,  // QRIS statis tidak punya referensi per transaksi
            'qr_payload' => null,
            'raw'        => ['gateway' => 'manual_qris', 'merchant' => config('promotion.qris.merchant_name')],
        ];
    }

    /** QRIS siap dipakai untuk pembayaran nyata hanya bila gambar & nama merchant dikonfigurasi. */
    public static function isConfigured(): bool
    {
        $image = config('promotion.qris.image');

        return filled(config('promotion.qris.merchant_name')) && $image && is_file(public_path($image));
    }
}
