<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ProjectAndProviderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Providers
        $provider1 = User::firstOrCreate(
            ['email' => 'kopi.kenangan@usaha.id'],
            [
                'name' => 'Kedai Kopi Kenangan Nusantara',
                'password' => Hash::make('password123'),
                'role' => 'penyedia_proyek',
                'is_active' => true,
            ]
        );

        $provider2 = User::firstOrCreate(
            ['email' => 'batik.parang@usaha.id'],
            [
                'name' => 'Batik Tulis Warisan Nusantara',
                'password' => Hash::make('password123'),
                'role' => 'penyedia_proyek',
                'is_active' => true,
            ]
        );

        $provider3 = User::firstOrCreate(
            ['email' => 'studio.kreatif@usaha.id'],
            [
                'name' => 'Studio Kreatif Bandung',
                'password' => Hash::make('password123'),
                'role' => 'penyedia_proyek',
                'is_active' => false, // sample deactivated user
            ]
        );

        // 2. Sample Projects
        $sampleProjects = [
            [
                'user_id' => $provider1->id,
                'provider_name' => 'Kedai Kopi Kenangan Nusantara',
                'title' => 'Pembuatan Landing Page Menu & Reservasi',
                'category' => 'Web Development',
                'description' => 'Membangun landing page modern dan responsif untuk menampilkan menu musiman dan form reservasi meja.',
                'responsibilities' => 'Membuat antarmuka web responsif, menghubungkan form ke WhatsApp / email reservasi, dan optimasi SEO lokal.',
                'location' => 'Remote',
                'work_type' => 'Remote',
                'compensation' => 'Rp 2.000.000 / Proyek',
                'deadline' => now()->addDays(20),
                'status' => 'Dipublikasikan',
                'applicants_count' => 4,
            ],
            [
                'user_id' => $provider1->id,
                'provider_name' => 'Kedai Kopi Kenangan Nusantara',
                'title' => 'Redesain Kemasan & Menu Board Kafe',
                'category' => 'Desain Grafis',
                'description' => 'Mendesain ulang kemasan cup take-away dan poster papan menu dinding bergaya vintage modern.',
                'responsibilities' => 'Membuat 3 alternatif konsep desain kemasan cup, ilustrasi menu kopi, dan file siap cetak.',
                'location' => 'Bandung',
                'work_type' => 'Hybrid',
                'compensation' => 'Rp 1.200.000 / Proyek',
                'deadline' => now()->addDays(14),
                'status' => 'Dipublikasikan',
                'applicants_count' => 2,
            ],
            [
                'user_id' => $provider2->id,
                'provider_name' => 'Batik Tulis Warisan Nusantara',
                'title' => 'Katalog Digital & Konten Instagram Reels UMKM Batik',
                'category' => 'Digital Marketing',
                'description' => 'Membuat 10 video reels edukasi batik tulis dan menyusun e-katalog produk dalam bentuk PDF interaktif.',
                'responsibilities' => 'Pengambilan video di workshop batik, editing video estetik dengan musik lokal, dan desain katalog PDF.',
                'location' => 'Yogyakarta',
                'work_type' => 'Onsite',
                'compensation' => 'Rp 1.800.000 / Proyek',
                'deadline' => now()->addDays(30),
                'status' => 'Dipublikasikan',
                'applicants_count' => 5,
            ],
            [
                'user_id' => $provider2->id,
                'provider_name' => 'Batik Tulis Warisan Nusantara',
                'title' => 'Sistem Inventaris Sederhana Stok Kain',
                'category' => 'Web Development',
                'description' => 'Aplikasi web internal ringan untuk mencatat keluar-masuk kain batik dan laporan stok bulanan.',
                'responsibilities' => 'Rancang database stok, buat form input barang masuk/keluar, dan export data ke Excel.',
                'location' => 'Remote',
                'work_type' => 'Remote',
                'compensation' => 'Rp 2.500.000 / Proyek',
                'deadline' => now()->addDays(45),
                'status' => 'Draf',
                'applicants_count' => 0,
            ],
            [
                'user_id' => $provider3->id,
                'provider_name' => 'Studio Kreatif Bandung',
                'title' => 'Desain Identitas Visual Brand Klien Event Festival',
                'category' => 'UI/UX Design',
                'description' => 'Mendesain prototipe aplikasi tiket acara dan aset visual promo festival seni pemuda.',
                'responsibilities' => 'Wireframing, UI high-fidelity di Figma, dan panduan desain komponen.',
                'location' => 'Bandung',
                'work_type' => 'Remote',
                'compensation' => 'Rp 3.000.000 / Proyek',
                'deadline' => now()->subDays(5),
                'status' => 'Ditutup',
                'applicants_count' => 8,
            ],
        ];

        foreach ($sampleProjects as $proj) {
            Project::updateOrCreate(
                [
                    'user_id' => $proj['user_id'],
                    'title' => $proj['title'],
                ],
                $proj
            );
        }
    }
}

