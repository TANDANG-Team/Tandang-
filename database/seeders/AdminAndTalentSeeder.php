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
        User::updateOrCreate(
            ['email' => 'admin@tandang.id'],
            [
                'name' => 'Administrator Tandang',
                'password' => Hash::make('admin12345'),
                'role' => 'admin',
            ]
        );

        // 2. Sample Talents for Verification Testing
        $talentsData = [
            [
                'name' => 'Budi Pratama',
                'email' => 'budi.pratama@student.ui.ac.id',
                'category' => 'Mahasiswa Aktif',
                'institution' => 'Universitas Indonesia',
                'major' => 'Ilmu Komputer',
                'city' => 'Depok',
                'document' => 'KTM_Budi_Pratama_2026.pdf',
                'verification_status' => 'Menunggu Verifikasi',
                'submitted_at' => now()->subHours(2),
            ],
            [
                'name' => 'Siti Nurhaliza',
                'email' => 'siti.nurhaliza@smkn1cimahi.sch.id',
                'category' => 'Siswa SMK/SMA',
                'institution' => 'SMKN 1 Cimahi',
                'major' => 'Rekayasa Perangkat Lunak',
                'city' => 'Cimahi',
                'document' => 'KartuPelajar_Siti_Nurhaliza.jpg',
                'verification_status' => 'Menunggu Verifikasi',
                'submitted_at' => now()->subHours(5),
            ],
            [
                'name' => 'Ahmad Fauzi',
                'email' => 'ahmad.fauzi@alumni.telkomuniversity.ac.id',
                'category' => 'Fresh Graduate (≤ 2 Tahun)',
                'institution' => 'Telkom University',
                'major' => 'Sistem Informasi',
                'city' => 'Bandung',
                'document' => 'SKL_Ijazah_AhmadFauzi.pdf',
                'verification_status' => 'Terverifikasi',
                'submitted_at' => now()->subDays(2),
                'verified_at' => now()->subDay(),
            ],
            [
                'name' => 'Rina Wijaya',
                'email' => 'rina.wijaya@gmail.com',
                'category' => 'Fresh Graduate (≤ 2 Tahun)',
                'institution' => 'Universitas Gadjah Mada',
                'major' => 'Teknologi Informasi',
                'city' => 'Yogyakarta',
                'document' => 'Ijazah_Rina_2021.pdf',
                'verification_status' => 'Ditolak',
                'rejection_reason' => 'Tahun kelulusan pada dokumen ijazah melebihi batas ketentuan 2 tahun untuk kategori Talenta Muda.',
                'submitted_at' => now()->subDays(3),
                'verified_at' => now()->subDays(2),
            ],
            [
                'name' => 'Dimas Arya',
                'email' => 'dimas.arya@student.itb.ac.id',
                'category' => 'Mahasiswa Aktif',
                'institution' => 'Institut Teknologi Bandung',
                'major' => 'Desain Komunikasi Visual',
                'city' => 'Bandung',
                'document' => 'KTM_DimasArya_ITB.png',
                'verification_status' => 'Menunggu Verifikasi',
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
                ]
            );

            TalentProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'category' => $data['category'],
                    'institution' => $data['institution'],
                    'major' => $data['major'],
                    'city' => $data['city'],
                    'document' => $data['document'],
                    'verification_status' => $data['verification_status'],
                    'rejection_reason' => $data['rejection_reason'] ?? null,
                    'submitted_at' => $data['submitted_at'] ?? now(),
                    'verified_at' => $data['verified_at'] ?? null,
                ]
            );
        }
    }
}

