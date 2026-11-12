<?php

namespace App\Services;

use App\Models\TalentProfile;
use Carbon\Carbon;

class TalentEligibilityService
{
    /**
     * Evaluasi kelayakan awal berdasarkan kategori
     */
    public function evaluateEligibility(string $category, array $data): array
    {
        return match ($category) {
            'Siswa SMK/SMA', 'Siswa SMK Aktif' => $this->evaluateSiswaSmk($data),
            'Mahasiswa Aktif', 'Mahasiswa' => $this->evaluateMahasiswa($data),
            'Fresh Graduate (≤ 2 Tahun)', 'Fresh Graduate' => $this->evaluateFreshGraduate($data),
            default => [
                'is_eligible' => false,
                'status' => 'undetermined',
                'errors' => ['category' => 'Kategori talenta tidak dikenali.'],
                'notes' => 'Kategori tidak valid.',
            ],
        };
    }

    /**
     * Evaluasi Siswa SMK Aktif
     */
    public function evaluateSiswaSmk(array $data): array
    {
        $currentYear = now()->year;
        $errors = [];

        $classLevel = $data['class_level'] ?? null;
        $academicYear = $data['academic_year'] ?? null;
        $gradYear = isset($data['graduation_year']) ? (int) $data['graduation_year'] : null;

        if (!in_array($classLevel, ['X', 'XI', 'XII', 'XIII'])) {
            $errors['class_level'] = 'Kelas harus dipilih antara X, XI, XII, atau XIII.';
        }

        if (!$gradYear) {
            $errors['graduation_year'] = 'Perkiraan tahun kelulusan wajib diisi.';
        } elseif ($gradYear < $currentYear) {
            $errors['graduation_year'] = "Perkiraan tahun kelulusan ({$gradYear}) tidak boleh di masa lampau untuk siswa aktif.";
        } elseif ($gradYear > $currentYear + 4) {
            $errors['graduation_year'] = "Perkiraan tahun kelulusan ({$gradYear}) tidak masuk akal (maksimal 4 tahun ke depan).";
        }

        // Cek konsistensi kelas dengan perkiraan tahun kelulusan
        if ($classLevel && $gradYear) {
            if ($classLevel === 'XII' && $gradYear > $currentYear + 1) {
                $errors['graduation_year'] = "Siswa kelas XII umumnya lulus pada tahun {$currentYear} atau " . ($currentYear + 1) . '.';
            } elseif ($classLevel === 'X' && $gradYear < $currentYear + 1) {
                $errors['graduation_year'] = 'Siswa kelas X umumnya diperkirakan lulus paling cepat tahun ' . ($currentYear + 2) . '.';
            }
        }

        if (!empty($errors)) {
            return [
                'is_eligible' => false,
                'status' => 'undetermined',
                'errors' => $errors,
                'notes' => 'Screening otomatis: Terdapat ketidaksesuaian data kelas atau tahun kelulusan.',
            ];
        }

        return [
            'is_eligible' => true,
            'status' => 'eligible',
            'errors' => [],
            'notes' => "Screening awal lolos: Siswa SMK Kelas {$classLevel}, Tahun Ajaran {$academicYear}, perkiraan lulus {$gradYear}. Menunggu verifikasi dokumen oleh admin.",
        ];
    }

    /**
     * Evaluasi Mahasiswa Aktif
     */
    public function evaluateMahasiswa(array $data): array
    {
        $currentYear = now()->year;
        $errors = [];

        $entryYear = isset($data['entry_year']) ? (int) $data['entry_year'] : null;
        $semester = isset($data['current_semester']) ? (int) $data['current_semester'] : null;
        $academicYear = $data['academic_year'] ?? null;

        if (!$entryYear) {
            $errors['entry_year'] = 'Tahun masuk wajib diisi.';
        } elseif ($entryYear > $currentYear) {
            $errors['entry_year'] = "Tahun masuk ({$entryYear}) tidak boleh melebihi tahun berjalan ({$currentYear}).";
        } elseif ($entryYear < $currentYear - 7) {
            $errors['entry_year'] = "Tahun masuk ({$entryYear}) melebihi batas wajar masa studi aktif perguruan tinggi (maksimal 7 tahun).";
        }

        if (!$semester || $semester < 1 || $semester > 14) {
            $errors['current_semester'] = 'Semester saat ini harus antara 1 sampai 14.';
        }

        // Validasi konsistensi tahun masuk dengan semester
        if ($entryYear && $semester) {
            $yearsSinceEntry = $currentYear - $entryYear;
            $maxExpectedSemester = ($yearsSinceEntry + 1) * 2 + 2; // toleransi 2 semester cuti/perpanjangan
            $minExpectedSemester = max(1, ($yearsSinceEntry - 1) * 2);

            if ($yearsSinceEntry === 0 && $semester > 3) {
                $errors['current_semester'] = "Mahasiswa tahun masuk {$entryYear} belum memungkinkan berada di Semester {$semester}. Mohon periksa kembali.";
            } elseif ($semester > $maxExpectedSemester) {
                $errors['current_semester'] = "Semester {$semester} tidak selaras dengan tahun masuk {$entryYear} (perkiraan maksimal semester {$maxExpectedSemester}). Mohon periksa kembali data Anda.";
            }
        }

        if (!empty($errors)) {
            return [
                'is_eligible' => false,
                'status' => 'undetermined',
                'errors' => $errors,
                'notes' => 'Screening otomatis: Terdapat ketidaksesuaian tahun masuk atau semester aktif.',
            ];
        }

        return [
            'is_eligible' => true,
            'status' => 'eligible',
            'errors' => [],
            'notes' => "Screening awal lolos: Mahasiswa Aktif Angkatan {$entryYear}, Semester {$semester}, Tahun Akademik {$academicYear}. Menunggu verifikasi dokumen oleh admin.",
        ];
    }

    /**
     * Evaluasi Fresh Graduate (Maksimal 24 Bulan Kelulusan)
     */
    public function evaluateFreshGraduate(array $data): array
    {
        $now = now();
        $errors = [];

        $gradMonth = isset($data['graduation_month']) ? (int) $data['graduation_month'] : null;
        $gradYear = isset($data['graduation_year']) ? (int) $data['graduation_year'] : null;

        if (!$gradMonth || $gradMonth < 1 || $gradMonth > 12) {
            $errors['graduation_month'] = 'Bulan kelulusan harus antara bulan 1 sampai 12.';
        }

        if (!$gradYear) {
            $errors['graduation_year'] = 'Tahun kelulusan wajib diisi.';
        }

        if (!empty($errors)) {
            return [
                'is_eligible' => false,
                'status' => 'undetermined',
                'errors' => $errors,
                'notes' => 'Screening otomatis: Data bulan atau tahun kelulusan belum lengkap.',
            ];
        }

        // Cek apakah tanggal kelulusan di masa depan
        $gradDate = Carbon::createFromDate($gradYear, $gradMonth, 1)->endOfMonth();
        if ($gradDate->isFuture() && $gradDate->diffInDays($now) > 31) {
            return [
                'is_eligible' => false,
                'status' => 'ineligible',
                'errors' => ['graduation_year' => 'Bulan dan tahun kelulusan tidak boleh berada di masa depan.'],
                'notes' => 'Ineligible: Tanggal kelulusan berada di masa depan.',
            ];
        }

        // Hitung selisih bulan (menggunakan perhitungan bulan dinamis, bukan tanggal hari)
        $diffInMonths = ($now->year - $gradYear) * 12 + ($now->month - $gradMonth);

        if ($diffInMonths < 0) {
            return [
                'is_eligible' => false,
                'status' => 'ineligible',
                'errors' => ['graduation_year' => 'Bulan dan tahun kelulusan tidak valid (di masa depan).'],
                'notes' => 'Ineligible: Kelulusan di masa depan.',
            ];
        }

        if ($diffInMonths > 24) {
            return [
                'is_eligible' => false,
                'status' => 'ineligible',
                'errors' => [
                    'graduation_year' => "Masa kelulusan Anda terhitung {$diffInMonths} bulan yang lalu. Kategori Fresh Graduate hanya diperuntukkan bagi lulusan maksimal 24 bulan (2 tahun).",
                ],
                'notes' => "Ineligible: Waktu kelulusan ({$gradMonth}/{$gradYear}) telah mencapai {$diffInMonths} bulan yang lalu, melebihi batas ketentuan 24 bulan.",
            ];
        }

        return [
            'is_eligible' => true,
            'status' => 'eligible',
            'errors' => [],
            'notes' => "Screening awal lolos: Fresh Graduate lulusan {$gradMonth}/{$gradYear} ({$diffInMonths} bulan yang lalu, memenuhi kriteria ≤ 24 bulan). Menunggu verifikasi dokumen oleh admin.",
        ];
    }

    /**
     * Cek ulang kelayakan saat talenta akan melamar proyek
     */
    public function checkOngoingEligibility(TalentProfile $talent): array
    {
        // 1. Cek status dasar
        if ($talent->verification_status !== 'verified') {
            return [
                'can_apply' => false,
                'reason' => 'Akun Anda belum terverifikasi oleh Administrator. Mohon lengkapi dan tunggu proses verifikasi dokumen.',
            ];
        }

        if ($talent->eligibility_status !== 'eligible') {
            return [
                'can_apply' => false,
                'reason' => 'Status kelayakan akun Anda tidak memenuhi kriteria talenta muda aktif.',
            ];
        }

        // 2. Jika kategori Fresh Graduate, cek apakah sudah melewati 24 bulan per hari ini
        if (str_contains($talent->category, 'Fresh Graduate') && $talent->graduation_year && $talent->graduation_month) {
            $now = now();
            $diffInMonths = ($now->year - $talent->graduation_year) * 12 + ($now->month - $talent->graduation_month);

            if ($diffInMonths > 24) {
                // Perbarui status menjadi expired & ineligible
                $talent->update([
                    'eligibility_status' => 'ineligible',
                    'verification_status' => 'expired',
                    'rejection_reason' => "Masa berlaku kategori Fresh Graduate Anda telah kedaluwarsa ({$diffInMonths} bulan sejak kelulusan, batas maksimal 24 bulan).",
                    'screening_notes' => "Kedaluwarsa: Kelulusan telah mencapai {$diffInMonths} bulan lalu saat evaluasi melamar proyek.",
                ]);

                return [
                    'can_apply' => false,
                    'reason' => "Masa kelayakan kategori Fresh Graduate Anda telah kedaluwarsa ({$diffInMonths} bulan sejak kelulusan). Anda tidak dapat lagi melamar proyek dengan kategori ini.",
                ];
            }
        }

        return [
            'can_apply' => true,
            'reason' => null,
        ];
    }
}

