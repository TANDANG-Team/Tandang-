<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Data awal minimal: akun Admin dan paket Sponsored Project.
     * Akun Talenta/Penyedia, proyek, lamaran, dan rating dibuat lewat aplikasi.
     */
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            PromotionPackageSeeder::class,
        ]);
    }
}
