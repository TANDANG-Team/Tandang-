<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\OrganizationProfile;
use App\Models\Project;
use App\Models\ProjectApplication;
use App\Models\ProviderNotification;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class ProjectAndProviderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Clear existing projects & applications for clean synchronization
        Schema::disableForeignKeyConstraints();
        if (Schema::hasTable('applications')) {
            Application::truncate();
        }
        if (Schema::hasTable('project_applications')) {
            ProjectApplication::truncate();
        }
        if (Schema::hasTable('provider_notifications')) {
            ProviderNotification::truncate();
        }
        Project::truncate();
        Schema::enableForeignKeyConstraints();

        // 2. Create Providers (Selaras dengan Landing Page & Prototype & Penyedia Demo)
        $dapurNusa = User::updateOrCreate(
            ['email' => 'penyedia@tandang.id'],
            [
                'name' => 'Dimas Nugraha (Dapur Nusa)',
                'password' => Hash::make('demo12345'),
                'role' => 'penyedia_proyek',
                'is_active' => true,
            ]
        );
        OrganizationProfile::updateOrCreate(
            ['user_id' => $dapurNusa->id],
            [
                'organization_name' => 'Dapur Nusa',
                'industry_type' => 'Kuliner & F&B',
                'description' => 'Ruang kuliner lokal dengan cita rasa Nusantara.',
                'address' => 'Jl. Cendana No. 12',
                'city' => 'Bandung',
                'phone' => '+62 812 3456 7890',
                'website' => 'https://dapurnusa.id',
            ]
        );

        $kopiSejenak = User::firstOrCreate(
            ['email' => 'halo@kopisejenak.id'],
            [
                'name' => 'Kopi Sejenak',
                'password' => Hash::make('password123'),
                'role' => 'penyedia_proyek',
                'is_active' => true,
            ]
        );
        OrganizationProfile::firstOrCreate(
            ['user_id' => $kopiSejenak->id],
            [
                'organization_name' => 'Kopi Sejenak',
                'industry_type' => 'F&B / Coffee Shop',
                'city' => 'Jakarta Selatan',
            ]
        );

        $ruangRupa = User::firstOrCreate(
            ['email' => 'halo@ruangrupa.id'],
            [
                'name' => 'Ruang Rupa',
                'password' => Hash::make('password123'),
                'role' => 'penyedia_proyek',
                'is_active' => true,
            ]
        );
        OrganizationProfile::firstOrCreate(
            ['user_id' => $ruangRupa->id],
            [
                'organization_name' => 'Ruang Rupa',
                'industry_type' => 'Kriya & Kerajinan',
                'city' => 'Yogyakarta',
            ]
        );

        $kopiKenangan = User::firstOrCreate(
            ['email' => 'contact@kopikenangan.id'],
            [
                'name' => 'Kedai Kopi Kenangan Nusantara',
                'password' => Hash::make('password123'),
                'role' => 'penyedia_proyek',
                'is_active' => true,
            ]
        );
        OrganizationProfile::firstOrCreate(
            ['user_id' => $kopiKenangan->id],
            [
                'organization_name' => 'Kedai Kopi Kenangan Nusantara',
                'industry_type' => 'F&B & Kafe',
                'city' => 'Bandung',
            ]
        );

        $batikParang = User::firstOrCreate(
            ['email' => 'info@batikwarisan.id'],
            [
                'name' => 'Batik Tulis Warisan Nusantara',
                'password' => Hash::make('password123'),
                'role' => 'penyedia_proyek',
                'is_active' => true,
            ]
        );
        OrganizationProfile::firstOrCreate(
            ['user_id' => $batikParang->id],
            [
                'organization_name' => 'Batik Tulis Warisan Nusantara',
                'industry_type' => 'Fashion & Tekstil Tradisional',
                'city' => 'Yogyakarta',
            ]
        );

        // 3. Insert Exactly 6 Unified Projects (ID 1-6 Berurutan)
        $sampleProjects = [
            // Proyek 1 (Card 1 Landing Page)
            [
                'user_id' => $dapurNusa->id,
                'provider_name' => 'Dapur Nusa',
                'title' => 'Fotografer Acara Kuliner',
                'category' => 'Kreatif',
                'description' => 'Dokumentasikan festival kuliner dan suasana pengunjung untuk materi promosi media sosial.',
                'responsibilities' => 'Mengambil foto acara, menyeleksi hasil terbaik, dan mengirimkan 40 foto beresolusi tinggi yang telah disunting.',
                'skills' => ['Fotografi', 'Adobe Lightroom'],
                'location' => 'Bandung',
                'work_type' => 'Onsite',
                'work_method' => 'onsite',
                'schedule' => 'Akhir pekan',
                'availability' => 'Akhir pekan',
                'compensation' => 'Rp 750.000',
                'quota' => 2,
                'talents_needed' => 2,
                'required_competencies' => 'Fotografi, Adobe Lightroom, Komunikasi',
                'deadline' => now()->addDays(14),
                'registration_deadline' => now()->addDays(14),
                'start_date' => now()->addDays(5),
                'end_date' => now()->addDays(7),
                'status' => 'Dipublikasikan',
                'applicants_count' => 1,
                'workstyle_requirements' => [
                    'collaboration' => 4,
                    'structure' => 3,
                    'communication' => 4,
                    'environment' => 4,
                    'taskPreference' => 4,
                    'workPace' => 4,
                    'socialInteraction' => 4,
                    'planning' => 3,
                ],
                'workstyle_characteristics' => [
                    'independence_teamwork' => 'Kerja tim',
                    'structure_flexibility' => 'Fleksibel',
                    'written_verbal' => 'Langsung',
                    'remote_field' => 'Lapangan',
                    'analytical_practical' => 'Praktis',
                    'stable_dynamic' => 'Dinamis',
                    'limited_intense_interaction' => 'Interaksi intens',
                    'detailed_spontaneous_planning' => 'Spontan',
                ],
                'workstyle_confirmed' => true,
            ],
            // Proyek 2 (Card 2 Landing Page)
            [
                'user_id' => $kopiSejenak->id,
                'provider_name' => 'Kopi Sejenak',
                'title' => 'Penjaga Booth Local Market',
                'category' => 'Operasional',
                'description' => 'Sambut pengunjung pasar kreatif lokal dan perkenalkan varian produk kopi kemasan khas Nusantara.',
                'responsibilities' => 'Menjaga booth pameran, menyapa pengunjung, melayani pesanan, dan mencatat transaksi harian.',
                'skills' => ['Komunikasi', 'Pelayanan'],
                'location' => 'Jakarta Selatan',
                'work_type' => 'Onsite',
                'work_method' => 'onsite',
                'schedule' => 'Akhir pekan',
                'availability' => 'Akhir pekan',
                'compensation' => 'Rp 600.000',
                'quota' => 3,
                'talents_needed' => 3,
                'required_competencies' => 'Komunikasi, Penjualan, Pelayanan Pelanggan',
                'deadline' => now()->addDays(18),
                'registration_deadline' => now()->addDays(18),
                'start_date' => now()->addDays(10),
                'end_date' => now()->addDays(13),
                'status' => 'Dipublikasikan',
                'applicants_count' => 0,
                'workstyle_requirements' => [
                    'collaboration' => 5,
                    'structure' => 3,
                    'communication' => 5,
                    'environment' => 5,
                    'taskPreference' => 5,
                    'workPace' => 4,
                    'socialInteraction' => 5,
                    'planning' => 3,
                ],
                'workstyle_confirmed' => true,
            ],
            // Proyek 3 (Card 3 Landing Page)
            [
                'user_id' => $ruangRupa->id,
                'provider_name' => 'Ruang Rupa',
                'title' => 'Admin Data Produk',
                'category' => 'Administrasi',
                'description' => 'Rapikan dan susun database katalog produk kriya untuk peluncuran koleksi kerajinan musim baru.',
                'responsibilities' => 'Input data spesifikasi produk, validasi harga grosir/eceran, dan merapikan spreadsheet Google Sheets.',
                'skills' => ['Microsoft Excel', 'Ketelitian'],
                'location' => 'Remote',
                'work_type' => 'Remote',
                'work_method' => 'remote',
                'schedule' => 'Fleksibel',
                'availability' => 'Fleksibel',
                'compensation' => 'Rp 1.200.000',
                'quota' => 1,
                'talents_needed' => 1,
                'required_competencies' => 'Microsoft Excel, Google Sheets, Ketelitian Administrasi',
                'deadline' => now()->addDays(25),
                'registration_deadline' => now()->addDays(25),
                'start_date' => now()->addDays(8),
                'end_date' => now()->addDays(28),
                'status' => 'Dipublikasikan',
                'applicants_count' => 0,
                'workstyle_requirements' => [
                    'collaboration' => 2,
                    'structure' => 1,
                    'communication' => 2,
                    'environment' => 1,
                    'taskPreference' => 1,
                    'workPace' => 2,
                    'socialInteraction' => 2,
                    'planning' => 1,
                ],
                'workstyle_confirmed' => true,
            ],
            // Proyek 4: Web Development
            [
                'user_id' => $kopiKenangan->id,
                'provider_name' => 'Kedai Kopi Kenangan Nusantara',
                'title' => 'Pembuatan Landing Page Menu & Reservasi',
                'category' => 'Web Development',
                'description' => 'Membangun landing page satu halaman yang responsif untuk menampilkan menu seasonal dan form reservasi meja.',
                'responsibilities' => 'Mengembangkan antarmuka landing page responsif (HTML/CSS/JS atau Laravel Blade) dan integrasi form kirim ke WhatsApp.',
                'skills' => ['HTML/CSS', 'JavaScript', 'Responsive Web Design', 'Bootstrap/Tailwind'],
                'location' => 'Remote',
                'work_type' => 'Remote',
                'work_method' => 'remote',
                'schedule' => 'Fleksibel',
                'availability' => 'Fleksibel',
                'compensation' => 'Rp 2.000.000',
                'quota' => 1,
                'talents_needed' => 1,
                'required_competencies' => 'HTML/CSS, Tailwind/Bootstrap, Responsive Design',
                'deadline' => now()->addDays(20),
                'registration_deadline' => now()->addDays(20),
                'start_date' => now()->addDays(3),
                'end_date' => now()->addDays(24),
                'status' => 'Dipublikasikan',
                'applicants_count' => 0,
                'workstyle_requirements' => [
                    'collaboration' => 2,
                    'structure' => 4,
                    'communication' => 3,
                    'environment' => 1,
                    'taskPreference' => 4,
                    'workPace' => 3,
                    'socialInteraction' => 2,
                    'planning' => 4,
                ],
                'workstyle_confirmed' => true,
            ],
            // Proyek 5: Desain Grafis
            [
                'user_id' => $kopiKenangan->id,
                'provider_name' => 'Kedai Kopi Kenangan Nusantara',
                'title' => 'Redesain Kemasan & Menu Board Kafe',
                'category' => 'Desain Grafis',
                'description' => 'Merancang ulang packaging kemasan kopi biji 250g dan template menu board digital kafe dengan nuansa modern dan hangat.',
                'responsibilities' => 'Membuat mockup 3 varian kemasan, desain menu board vertikal untuk display TV kafe, dan deliver file siap cetak.',
                'skills' => ['Adobe Illustrator', 'Photoshop', 'Branding', 'Typography'],
                'location' => 'Bandung',
                'work_type' => 'Hybrid',
                'work_method' => 'hybrid',
                'schedule' => 'Akhir pekan',
                'availability' => 'Akhir pekan',
                'compensation' => 'Rp 1.500.000',
                'quota' => 1,
                'talents_needed' => 1,
                'required_competencies' => 'Adobe Illustrator, Photoshop, Mockup Kemasan',
                'deadline' => now()->addDays(15),
                'registration_deadline' => now()->addDays(15),
                'start_date' => now()->addDays(4),
                'end_date' => now()->addDays(20),
                'status' => 'Dipublikasikan',
                'applicants_count' => 0,
                'workstyle_requirements' => [
                    'collaboration' => 4,
                    'structure' => 3,
                    'communication' => 4,
                    'environment' => 3,
                    'taskPreference' => 4,
                    'workPace' => 3,
                    'socialInteraction' => 3,
                    'planning' => 4,
                ],
                'workstyle_confirmed' => true,
            ],
            // Proyek 6: Digital Marketing
            [
                'user_id' => $batikParang->id,
                'provider_name' => 'Batik Tulis Warisan Nusantara',
                'title' => 'Katalog Digital & Konten Instagram Reels UMKM Batik',
                'category' => 'Digital Marketing',
                'description' => 'Membuat 10 video reels edukasi batik tulis dan menyusun e-katalog produk dalam bentuk PDF interaktif.',
                'responsibilities' => 'Pengambilan video di workshop batik, editing video estetik dengan musik lokal, dan desain katalog PDF.',
                'skills' => ['CapCut', 'Canva', 'Social Media Management', 'Fotografi'],
                'location' => 'Yogyakarta',
                'work_type' => 'Onsite',
                'work_method' => 'onsite',
                'schedule' => 'Akhir pekan',
                'availability' => 'Akhir pekan',
                'compensation' => 'Rp 1.800.000',
                'quota' => 3,
                'talents_needed' => 3,
                'required_competencies' => 'CapCut/Premiere, Canva, Manajemen Konten Media Sosial',
                'deadline' => now()->addDays(30),
                'registration_deadline' => now()->addDays(30),
                'start_date' => now()->addDays(7),
                'end_date' => now()->addDays(40),
                'status' => 'Dipublikasikan',
                'applicants_count' => 0,
                'workstyle_requirements' => [
                    'collaboration' => 5,
                    'structure' => 2,
                    'communication' => 5,
                    'environment' => 5,
                    'taskPreference' => 5,
                    'workPace' => 4,
                    'socialInteraction' => 5,
                    'planning' => 3,
                ],
                'workstyle_confirmed' => true,
            ],
        ];

        foreach ($sampleProjects as $proj) {
            Project::create($proj);
        }

        // 4. Sample application for first talent (sesuai prototype)
        $firstTalent = User::where('role', 'talenta_muda')->first();
        $firstProject = Project::first();
        if ($firstTalent && $firstProject) {
            // Sisi talent model
            Application::create([
                'user_id' => $firstTalent->id,
                'project_id' => $firstProject->id,
                'message' => 'Saya siap bekerja di akhir pekan dan memiliki pengalaman fotografi festival kuliner.',
                'status' => 'Menunggu',
            ]);

            // Sisi provider modul model (ProjectApplication)
            ProjectApplication::create([
                'project_id' => $firstProject->id,
                'user_id' => $firstTalent->id,
                'status' => 'menunggu',
                'cover_letter' => 'Saya siap bekerja di akhir pekan dan memiliki pengalaman fotografi festival kuliner.',
                'match_score' => 91,
                'talent_snapshot' => [
                    'name' => $firstTalent->name,
                    'category' => $firstTalent->talentProfile?->category ?? 'Mahasiswa',
                    'institution' => $firstTalent->talentProfile?->institution ?? 'Universitas Indonesia',
                    'city' => $firstTalent->talentProfile?->city ?? 'Bandung',
                    'bio' => $firstTalent->talentProfile?->bio ?? 'Talenta muda antusias di bidang fotografi.',
                    'competencies' => ['Fotografi', 'Adobe Lightroom', 'Komunikasi'],
                ],
                'reviewed_at' => null,
            ]);

            // Buat notifikasi penyedia
            ProviderNotification::create([
                'user_id' => $firstProject->user_id,
                'title' => "Pelamar baru pada {$firstProject->title}",
                'message' => "{$firstTalent->name} baru saja mengajukan lamaran untuk proyek {$firstProject->title}.",
                'type' => 'applicant_new',
                'target_url' => '/penyedia/pelamar',
                'is_read' => false,
            ]);
        }
    }
}
