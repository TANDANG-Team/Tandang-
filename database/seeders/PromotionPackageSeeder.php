<?php

namespace Database\Seeders;

use App\Models\PromotionPackage;
use Illuminate\Database\Seeder;

/**
 * Paket MVP: satu paket Sponsored Project, Rp15.000 / 96 jam (4 hari).
 */
class PromotionPackageSeeder extends Seeder
{
    public function run(): void
    {
        PromotionPackage::updateOrCreate(
            ['slug' => PromotionPackage::DEFAULT_SLUG],
            [
                'name'           => 'Sponsored Project',
                'price'          => 15000,
                'duration_hours' => 96,
                'description'    => 'Penempatan prioritas di halaman Cari Proyek selama 4 × 24 jam beserta dashboard statistik dasar.',
                'features'       => [
                    'Kartu Sponsored di posisi teratas hasil Cari Proyek (bila relevan dengan filter Talenta)',
                    'Label "Dipromosikan" di daftar proyek',
                    'Dashboard statistik: impressions, klik, CTR, lamaran dari promosi',
                    'Berlaku untuk 1 proyek, tanpa langganan',
                ],
                'is_active'      => true,
            ]
        );

        // Nonaktifkan paket lain yang mungkin tersisa dari versi sebelumnya.
        PromotionPackage::where('slug', '!=', PromotionPackage::DEFAULT_SLUG)->update(['is_active' => false]);
    }
}
