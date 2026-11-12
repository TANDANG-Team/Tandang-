<?php

namespace Database\Seeders;

use App\Models\TalentProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminAndTalentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create or Update Admin User
        $admin = User::updateOrCreate(
            ['email' => 'admin@tandang.id'],
            [
                'name' => 'Administrator Tandang',
                'password' => Hash::make('admin12345'),
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        // 2. Sample Talents with Profile & Verification Data
        $talentsData = [
            [
                'name' => 'Budi Pratama',
                'email' => 'budi.pratama@student.ui.ac.id',
                'category' => 'Mahasiswa Aktif',
                'institution' => 'Universitas Indonesia',
                'major' => 'Ilmu Komputer',
                'city' => 'Depok',
                'bio' => 'Mahasiswa tingkat akhir yang antusias dengan web development, khususnya ekosistem Laravel dan Tailwind CSS.',
                'skills' => ['HTML/CSS', 'JavaScript', 'Laravel', 'Tailwind CSS', 'PHP'],
                'availability' => 'Fleksibel',
                'work_mode' => 'Remote',
                'entry_year' => 2022,
                'current_semester' => 6,
                'academic_year' => '2025/2026',
                'document' => 'KTM_Budi_Pratama_2026.pdf',
                'document_path' => 'talent_documents/sample_ktm_budi.pdf',
                'document_original_name' => 'KTM_Budi_Pratama_2026.pdf',
                'document_size' => 580,
                'eligibility_status' => 'eligible',
                'verification_status' => 'verified',
                'screening_notes' => 'Screening otomatis lolos: Mahasiswa Aktif Angkatan 2022, Semester 6, Tahun Akademik 2025/2026.',
                'submitted_at' => now()->subHours(2),
                'verified_at' => now()->subHour(),
                'verified_by' => $admin->id,
                'assessment_answers' => [
                    'collaboration' => 4,
                    'structure' => 4,
                    'communication' => 3,
                    'environment' => 1,
                    'taskPreference' => 4,
                    'workPace' => 3,
                    'socialInteraction' => 3,
                    'planning' => 4,
                ],
                'assessment_completed_at' => now()->subDays(1),
                'portfolios' => [
                    [
                        'id' => 1,
                        'title' => 'Portal Informasi Kampus UI',
                        'image' => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=500&auto=format&fit=crop&q=60',
                    ],
                ],
            ],
            [
                'name' => 'Siti Nurhaliza',
                'email' => 'siti.nurhaliza@smkn1cimahi.sch.id',
                'category' => 'Siswa SMK/SMA',
                'institution' => 'SMKN 1 Cimahi',
                'major' => 'Rekayasa Perangkat Lunak',
                'city' => 'Cimahi',
                'bio' => 'Siswa SMK jurusan RPL dengan ketertarikan pada frontend website dan desain interaktif.',
                'skills' => ['HTML/CSS', 'JavaScript', 'Bootstrap'],
                'availability' => 'Akhir pekan',
                'work_mode' => 'Keduanya',
                'class_level' => 'XII',
                'academic_year' => '2025/2026',
                'graduation_year' => (int) date('Y'),
                'document' => 'KartuPelajar_Siti_Nurhaliza.pdf',
                'eligibility_status' => 'eligible',
                'verification_status' => 'pending',
                'screening_notes' => 'Screening awal lolos: Siswa SMK Kelas XII, Tahun Ajaran 2025/2026, perkiraan kelulusan ' . date('Y') . '. Menunggu verifikasi dokumen oleh admin.',
                'submitted_at' => now()->subHours(5),
            ],
            [
                'name' => 'Ahmad Fauzi',
                'email' => 'ahmad.fauzi@alumni.telkomuniversity.ac.id',
                'category' => 'Fresh Graduate (≤ 2 Tahun)',
                'institution' => 'Telkom University',
                'major' => 'Sistem Informasi',
                'city' => 'Bandung',
                'bio' => 'Fresh graduate dengan pengalaman magang di industri media & desain kreatif.',
                'skills' => ['Adobe Illustrator', 'Photoshop', 'Branding', 'Typography', 'Figma'],
                'availability' => 'Akhir pekan',
                'work_mode' => 'Hybrid',
                'graduation_month' => (int) date('n'),
                'graduation_year' => (int) date('Y') - 1, // 12 bulan lalu (≤ 24 bulan)
                'document' => 'SKL_Ijazah_AhmadFauzi.pdf',
                'eligibility_status' => 'eligible',
                'verification_status' => 'verified',
                'screening_notes' => 'Screening awal lolos: Fresh Graduate kelulusan 12 bulan yang lalu (memenuhi kriteria ≤ 24 bulan).',
                'submitted_at' => now()->subDays(2),
                'verified_at' => now()->subDay(),
                'verified_by' => $admin->id,
                'assessment_answers' => [
                    'collaboration' => 4,
                    'structure' => 3,
                    'communication' => 4,
                    'environment' => 3,
                    'taskPreference' => 4,
                    'workPace' => 4,
                    'socialInteraction' => 4,
                    'planning' => 3,
                ],
                'assessment_completed_at' => now()->subDays(2),
            ],
            [
                'name' => 'Rina Wijaya',
                'email' => 'rina.wijaya@gmail.com',
                'category' => 'Fresh Graduate (≤ 2 Tahun)',
                'institution' => 'Universitas Gadjah Mada',
                'major' => 'Teknologi Informasi',
                'city' => 'Yogyakarta',
                'bio' => 'Tertarik pada pembuatan konten digital dan media promosi visual.',
                'skills' => ['CapCut', 'Canva', 'Social Media Management', 'Fotografi'],
                'availability' => 'Akhir pekan',
                'work_mode' => 'Onsite',
                'graduation_month' => 5,
                'graduation_year' => (int) date('Y') - 3, // Lebih dari 24 bulan yang lalu
                'document' => 'Ijazah_Rina_Lama.pdf',
                'eligibility_status' => 'ineligible',
                'verification_status' => 'rejected',
                'rejection_reason' => 'Tahun kelulusan pada dokumen ijazah melebihi batas ketentuan maksimal 24 bulan untuk kategori Fresh Graduate.',
                'screening_notes' => 'Ineligible: Masa kelulusan telah melampaui batas 24 bulan.',
                'submitted_at' => now()->subDays(3),
                'verified_at' => now()->subDays(2),
                'verified_by' => $admin->id,
            ],
            [
                'name' => 'Dimas Arya',
                'email' => 'dimas.arya@student.itb.ac.id',
                'category' => 'Mahasiswa Aktif',
                'institution' => 'Institut Teknologi Bandung',
                'major' => 'Desain Komunikasi Visual',
                'city' => 'Bandung',
                'bio' => 'Mahasiswa DKV yang berfokus pada desain antarmuka pengguna (UI/UX) dan ilustrasi digital.',
                'skills' => ['Figma', 'UI/UX', 'Wireframing', 'Design System'],
                'availability' => 'Fleksibel',
                'work_mode' => 'Remote',
                'entry_year' => (int) date('Y') - 1,
                'current_semester' => 3,
                'academic_year' => '2025/2026',
                'document' => 'KTM_DimasArya_ITB.pdf',
                'eligibility_status' => 'eligible',
                'verification_status' => 'pending',
                'screening_notes' => 'Screening awal lolos: Mahasiswa Aktif Semester 3, Tahun Masuk ' . ((int) date('Y') - 1) . '. Menunggu verifikasi dokumen oleh admin.',
                'submitted_at' => now()->subMinutes(30),
            ],
        ];

        foreach ($talentsData as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password123'),
                    'role' => 'talenta_muda',
                    'is_active' => true,
                ]
            );

            TalentProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'category' => $data['category'],
                    'institution' => $data['institution'],
                    'major' => $data['major'],
                    'city' => $data['city'],
                    'bio' => $data['bio'] ?? null,
                    'skills' => $data['skills'] ?? [],
                    'availability' => $data['availability'] ?? 'Fleksibel',
                    'work_mode' => $data['work_mode'] ?? 'Keduanya',
                    'class_level' => $data['class_level'] ?? null,
                    'academic_year' => $data['academic_year'] ?? null,
                    'graduation_year' => $data['graduation_year'] ?? null,
                    'entry_year' => $data['entry_year'] ?? null,
                    'current_semester' => $data['current_semester'] ?? null,
                    'graduation_month' => $data['graduation_month'] ?? null,
                    'document' => $data['document'] ?? null,
                    'document_path' => $data['document_path'] ?? null,
                    'document_original_name' => $data['document_original_name'] ?? null,
                    'document_size' => $data['document_size'] ?? null,
                    'eligibility_status' => $data['eligibility_status'] ?? 'undetermined',
                    'verification_status' => $data['verification_status'] ?? 'not_submitted',
                    'rejection_reason' => $data['rejection_reason'] ?? null,
                    'screening_notes' => $data['screening_notes'] ?? null,
                    'submitted_at' => $data['submitted_at'] ?? now(),
                    'verified_at' => $data['verified_at'] ?? null,
                    'verified_by' => $data['verified_by'] ?? null,
                    'assessment_answers' => $data['assessment_answers'] ?? null,
                    'assessment_completed_at' => $data['assessment_completed_at'] ?? null,
                    'portfolios' => $data['portfolios'] ?? [],
                ]
            );
        }
    }
}
