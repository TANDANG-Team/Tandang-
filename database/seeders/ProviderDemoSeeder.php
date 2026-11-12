<?php

namespace Database\Seeders;

use App\Models\OrganizationProfile;
use App\Models\Project;
use App\Models\ProjectApplication;
use App\Models\ProviderNotification;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ProviderDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Ensure Provider demo account exists and has proper role & password
        $provider = User::updateOrCreate(
            ['email' => 'penyedia@tandang.id'],
            [
                'name' => 'Dimas Nugraha',
                'password' => Hash::make('demo12345'),
                'role' => 'penyedia_proyek',
            ]
        );

        // Ensure Organization Profile exists
        OrganizationProfile::updateOrCreate(
            ['user_id' => $provider->id],
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

        // 2. Ensure Talent demo account exists for testing cross-role applications
        $talent = User::updateOrCreate(
            ['email' => 'talenta@tandang.id'],
            [
                'name' => 'Nara Putri',
                'password' => Hash::make('demo12345'),
                'role' => 'talenta_muda',
            ]
        );

        // 3. Create initial demo projects if provider has none
        $project1 = Project::updateOrCreate(
            [
                'user_id' => $provider->id,
                'title' => 'Fotografer Acara Kuliner',
            ],
            [
                'category' => 'Kreatif',
                'location' => 'Bandung',
                'work_method' => 'onsite',
                'compensation' => 'Rp 750.000 + Transport',
                'required_competencies' => 'Fotografi, Adobe Lightroom, Komunikasi',
                'availability' => 'Akhir pekan',
                'talents_needed' => 1,
                'start_date' => now()->addDays(14)->toDateString(),
                'end_date' => now()->addDays(15)->toDateString(),
                'registration_deadline' => now()->addDays(7)->toDateString(),
                'description' => 'Mendokumentasikan festival kuliner lokal dan menu spesial Dapur Nusa.',
                'responsibilities' => 'Mengambil foto produk dan suasana selama acara berlangsung.',
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
                'status' => 'published',
            ]
        );

        $project2 = Project::updateOrCreate(
            [
                'user_id' => $provider->id,
                'title' => 'Penjaga Booth Local Market',
            ],
            [
                'category' => 'Operasional & Acara',
                'location' => 'Jakarta Selatan',
                'work_method' => 'onsite',
                'compensation' => 'Rp 500.000 / hari',
                'required_competencies' => 'Komunikasi, Penjualan, Pelayanan Pelanggan',
                'availability' => 'Full-time pameran',
                'talents_needed' => 2,
                'start_date' => now()->addDays(21)->toDateString(),
                'end_date' => now()->addDays(23)->toDateString(),
                'registration_deadline' => now()->addDays(14)->toDateString(),
                'description' => 'Menjaga booth pameran produk Dapur Nusa dan melayani pengunjung dengan ramah.',
                'responsibilities' => 'Melayani pembeli, mencatat stok harian, dan menjaga kebersihan booth.',
                'workstyle_characteristics' => [
                    'independence_teamwork' => 'Kerja tim',
                    'structure_flexibility' => 'Terstruktur',
                    'written_verbal' => 'Langsung',
                    'remote_field' => 'Lapangan',
                    'analytical_practical' => 'Praktis',
                    'stable_dynamic' => 'Stabil',
                    'limited_intense_interaction' => 'Interaksi intens',
                    'detailed_spontaneous_planning' => 'Rencana rinci',
                ],
                'status' => 'published',
            ]
        );

        // 4. Create sample application for testing
        ProjectApplication::updateOrCreate(
            [
                'project_id' => $project1->id,
                'user_id' => $talent->id,
            ],
            [
                'status' => 'dalam_review',
                'cover_letter' => 'Saya siap bekerja di akhir pekan dan memiliki portofolio pemotretan kuliner festival.',
                'match_score' => 91,
                'talent_snapshot' => [
                    'name' => 'Nara Putri',
                    'category' => 'Mahasiswa',
                    'institution' => 'Universitas Pendidikan Indonesia',
                    'city' => 'Bandung',
                    'bio' => 'Mahasiswa kreatif yang menyukai fotografi produk dan kolaborasi dengan usaha lokal.',
                    'competencies' => ['Fotografi', 'Adobe Lightroom', 'Komunikasi'],
                    'portfolio' => ['Festival Kampus 2025', 'Foto Produk Lokal'],
                    'assessment_summary' => 'Assessment diselesaikan. Preferensi tersimpan pada delapan dimensi dan digunakan dalam skor kecocokan proyek.',
                ],
                'reviewed_at' => now(),
            ]
        );

        // 5. Create sample notification
        ProviderNotification::updateOrCreate(
            [
                'user_id' => $provider->id,
                'title' => 'Pelamar baru pada Fotografer Acara Kuliner',
            ],
            [
                'message' => 'Nara Putri baru saja mengajukan lamaran untuk proyek Fotografer Acara Kuliner.',
                'type' => 'applicant_new',
                'target_url' => '/penyedia/pelamar',
                'is_read' => false,
            ]
        );
    }
}
