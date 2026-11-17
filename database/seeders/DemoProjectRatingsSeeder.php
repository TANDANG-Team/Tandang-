<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\OrganizationProfile;
use App\Models\Project;
use App\Models\ProjectApplication;
use App\Models\ProjectWorkCompletion;
use App\Models\Rating;
use App\Models\TalentProfile;
use App\Models\User;
use App\Services\Rating\RatingCalculator;
use App\Services\Rating\RatingService;
use App\Services\Rating\WorkCompletionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * DATA DEMO — rating dua arah untuk demonstrasi aplikasi.
 *
 * - Hanya dijalankan eksplisit:  php artisan db:seed --class=DemoProjectRatingsSeeder
 * - Ditolak di production.
 * - Semua data milik akun ber-domain @demo.tandang.test, nama diberi "(Demo)" dan judul proyek "[Demo]".
 *   Tidak menyentuh akun/proyek/rating asli.
 * - Idempoten: akun dicocokkan via email, proyek via (pemilik, judul), lamaran via (proyek, talenta),
 *   rating via (keterlibatan, pemberi). Menjalankan ulang tidak menggandakan data.
 * - Nilai akhir setiap rating DIHITUNG dari skor aspek oleh RatingCalculator; rata-rata profil
 *   dihitung aplikasi dari record ini (tidak ada angka rata-rata yang di-hardcode).
 */
class DemoProjectRatingsSeeder extends Seeder
{
    public const DOMAIN = 'demo.tandang.test';

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->error('DemoProjectRatingsSeeder tidak boleh dijalankan di production.');
            return;
        }

        $password = (string) env('DEMO_ACCOUNT_PASSWORD', 'demo12345');

        DB::transaction(function () use ($password) {
            // ── Akun ──────────────────────────────────────────────────────────
            $providerA = $this->provider('penyedia.a', 'Kopi Senja Nusantara (Demo)', 'Kuliner & Kafe', 'Bandung', $password);
            $providerB = $this->provider('penyedia.b', 'Studio Hijau Kreatif (Demo)', 'Agensi Kreatif', 'Yogyakarta', $password);
            // Rating < 4,0 → untuk menguji pembatasan Sponsored Project.
            $providerC = $this->provider('penyedia.c', 'Toko Kriya Lestari (Demo)', 'Kerajinan & Ritel', 'Surakarta', $password);

            $talentA = $this->talent('talenta.a', 'Alya Rahmawati (Demo)', 'Mahasiswa Aktif', 'Universitas Padjadjaran', 'Desain Komunikasi Visual', $password);
            $talentB = $this->talent('talenta.b', 'Bima Saputra (Demo)', 'Fresh Graduate', 'Politeknik Negeri Bandung', 'Manajemen Pemasaran', $password);
            $talentC = $this->talent('talenta.c', 'Citra Lestari (Demo)', 'Mahasiswa Aktif', 'Universitas Gadjah Mada', 'Ilmu Komputer', $password);
            $talentD = $this->talent('talenta.d', 'Dimas Kurniawan (Demo)', 'Siswa SMK', 'SMK Negeri 1 Cimahi', 'Multimedia', $password); // empty state

            // ── Proyek ────────────────────────────────────────────────────────
            $p1 = $this->project($providerA, '[Demo] Desain Feed Instagram Kopi Musiman', 'Desain Grafis', 'Rp750.000 / proyek', 4, 'Remote');
            $p2 = $this->project($providerA, '[Demo] Fotografer Menu Baru Kafe', 'Fotografi & Videografi', 'Rp600.000 / proyek', 4, 'Onsite');
            $p3 = $this->project($providerA, '[Demo] Penjaga Booth Bazar Akhir Pekan', 'Event & Acara', 'Rp150.000 / hari', 5, 'Onsite');
            $p4 = $this->project($providerB, '[Demo] Landing Page Kampanye Daur Ulang', 'Web Development', 'Rp1.200.000 / proyek', 3, 'Remote');
            $p5 = $this->project($providerB, '[Demo] Relawan Konten Edukasi Lingkungan', 'Konten Media Sosial', 'Tanpa bayaran (sertifikat & portofolio)', 4, 'Hybrid');
            $this->project($providerB, '[Demo] Admin Media Sosial Paruh Waktu', 'Digital Marketing', 'Rp1.000.000 / bulan', 2, 'Remote'); // terbuka, tanpa keterlibatan
            $pc1 = $this->project($providerC, '[Demo] Foto Katalog Produk Kriya', 'Fotografi & Videografi', 'Rp500.000 / proyek', 3, 'Onsite');
            $pc2 = $this->project($providerC, '[Demo] Penulisan Deskripsi Produk Marketplace', 'Copywriting', 'Rp400.000 / proyek', 2, 'Remote');
            $this->project($providerC, '[Demo] Pengemasan Pesanan Musim Liburan', 'Operasional', 'Rp120.000 / hari', 4, 'Onsite'); // terbuka → tombol promosi tetap nonaktif

            // Skor aspek — Penyedia → Talenta: quality, timeliness, communication, responsibility, teamwork
            // Skor aspek — Talenta → Penyedia: accuracy, payment (null = N/A), professionalism, clarity, environment
            $done = [
                // [talenta, proyek, selesai N hari lalu, skor utk talenta, skor utk penyedia, ulasan utk talenta, ulasan utk penyedia]
                [$talentA, $p1, 40, [5, 5, 5, 4, 5], [5, 5, 4, 4, 5], 'Desainnya rapi dan konsisten dengan identitas brand. Revisi cepat ditangani.', 'Brief jelas sejak awal dan pembayaran tepat waktu.'],
                [$talentA, $p2, 25, [5, 4, 5, 5, 4], [5, 4, 5, 4, 4], 'Foto produk tajam dan pencahayaannya bagus. Komunikatif selama sesi.', 'Tim kafe ramah, jadwal pemotretan sesuai kesepakatan.'],
                [$talentA, $p4, 30, [5, 4, 4, 5, 5], [4, 4, 5, 4, 4], 'Halaman selesai sesuai spesifikasi dan responsif di ponsel.', 'Profesional, hanya saja instruksi awal sempat berubah.'],
                [$talentB, $p1, 38, [4, 4, 5, 5, 4], [4, 5, 5, 4, 5], 'Ide kontennya segar, perlu sedikit dorongan untuk tenggat.', 'Lingkungan kerja menyenangkan dan masukan konstruktif.'],
                [$talentB, $p5, 20, [5, 4, 4, 5, 4], [5, null, 4, 4, 4], 'Bertanggung jawab dan aktif berdiskusi selama kampanye.', 'Sejak awal transparan bahwa ini kegiatan relawan. Pengalaman berharga.'],
                [$talentC, $p2, 24, [5, 5, 5, 5, 4], [5, 5, 5, 4, 4], 'Sangat detail dan selalu tepat waktu. Hasil melampaui ekspektasi.', 'Deskripsi pekerjaan sesuai kenyataan, pembayaran cepat.'],
                [$talentC, $p3, 15, [5, 5, 4, 5, 5], [5, 4, 4, 5, 5], 'Melayani pengunjung booth dengan sigap dan ramah.', 'Instruksi tugas di lokasi sangat jelas.'],
                [$talentC, $p4, 28, [5, 4, 5, 5, 5], [5, 4, 4, 4, 4], 'Kode bersih dan terdokumentasi. Mudah diajak berdiskusi.', 'Komunikasi baik dan kompensasi sesuai.'],
            ];

            foreach ($done as [$talent, $project, $daysAgo, $talentScores, $providerScores, $talentReview, $providerReview]) {
                $completion = $this->engagement($talent, $project);
                $completedAt = now()->subDays($daysAgo)->setTime(10, 0);
                $this->markCompleted($completion, $completedAt);

                $this->rating($completion, $project->user, $talent, 'talent_aspects', $talentScores, $talentReview, $completedAt->copy()->addDays(1));
                $this->rating($completion, $talent, $project->user, 'provider_aspects', $providerScores, $providerReview, $completedAt->copy()->addDays(2));
                Rating::where('project_work_completion_id', $completion->id)->update(['published_at' => $completedAt->copy()->addDays(2)]);
            }

            // Penyedia C: hanya Talenta yang menilai; Penyedia C tidak memberi rating dan periode 7 hari sudah
            // lewat sehingga rating Talenta terpublikasi otomatis. Rata-rata Talenta A/B/C tidak berubah.
            $oneSided = [
                [$talentA, $pc1, 35, [4, 3, 4, 4, 4], 'Hasil foto diterima, tetapi pembayaran mundur seminggu dari jadwal.'],
                [$talentB, $pc1, 32, [4, 4, 4, 3, 4], 'Pekerjaan sesuai deskripsi, hanya arahan teknis kurang jelas.'],
                [$talentC, $pc2, 27, [4, 4, 4, 4, 3], 'Komunikasi cukup baik, suasana kerja agak terburu-buru.'],
            ];
            foreach ($oneSided as [$talent, $project, $daysAgo, $providerScores, $providerReview]) {
                $completion = $this->engagement($talent, $project);
                $completedAt = now()->subDays($daysAgo)->setTime(10, 0);
                $this->markCompleted($completion, $completedAt);
                $this->rating($completion, $talent, $project->user, 'provider_aspects', $providerScores, $providerReview, $completedAt->copy()->addDays(2));
                Rating::where('project_work_completion_id', $completion->id)->update(['published_at' => $completedAt->copy()->addDays(config('rating.window_days'))]);
            }

            // Selesai kemarin, belum ada yang menilai → muncul di "Perlu Dinilai" untuk kedua pihak.
            $fresh = $this->engagement($talentB, $p3);
            if (! $fresh->ratings()->exists()) {
                $this->markCompleted($fresh, now()->subDay()->setTime(16, 0));
            }

            // Masih berjalan → rating harus ditolak; Talenta D tetap tanpa rating (empty state).
            $this->engagement($talentD, $p3);
        });

        $this->command?->info('Data demo rating siap. Akun (password dari DEMO_ACCOUNT_PASSWORD, default demo12345):');
        $notes = [
            'penyedia.a' => 'rating ±4,6 (5 penilaian) → boleh Sponsored Project',
            'penyedia.b' => 'rating ±4,3 (3 penilaian) → boleh Sponsored Project',
            'penyedia.c' => 'rating ±3,8 (3 penilaian) → Sponsored Project dikunci',
            'talenta.a'  => 'rating ±4,7', 'talenta.b' => 'rating ±4,4', 'talenta.c' => 'rating ±4,8', 'talenta.d' => 'belum ada penilaian',
        ];
        foreach ($notes as $local => $note) {
            $this->command?->line("  - {$local}@" . self::DOMAIN . "  ({$note})");
        }
    }

    // ── Helper ────────────────────────────────────────────────────────────────

    private function provider(string $local, string $name, string $industry, string $city, string $password): User
    {
        $user = $this->account($local, $name, 'penyedia_proyek', $password);
        OrganizationProfile::updateOrCreate(['user_id' => $user->id], [
            'organization_name' => $name,
            'industry_type'     => $industry,
            'city'              => $city,
            'description'       => 'Akun demonstrasi TANDANG. Data ini dibuat oleh DemoProjectRatingsSeeder untuk keperluan pengujian.',
        ]);

        return $user;
    }

    private function talent(string $local, string $name, string $category, string $institution, string $major, string $password): User
    {
        $user = $this->account($local, $name, 'talenta_muda', $password);
        TalentProfile::updateOrCreate(['user_id' => $user->id], [
            'category'            => $category,
            'institution'         => $institution,
            'major'               => $major,
            'city'                => 'Bandung',
            'bio'                 => 'Akun demonstrasi TANDANG (data uji).',
            'skills'              => ['Komunikasi', 'Desain', 'Media Sosial'],
            'verification_status' => 'verified',
            'eligibility_status'  => 'eligible',
            'verified_at'         => now()->subMonths(2),
        ]);

        return $user;
    }

    private function account(string $local, string $name, string $role, string $password): User
    {
        $user = User::firstOrNew(['email' => "{$local}@" . self::DOMAIN]);
        $user->forceFill([
            'name'              => $name,
            'role'              => $role,
            'password'          => Hash::make($password),
            'is_active'         => true,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        return $user;
    }

    private function project(User $owner, string $title, string $category, string $compensation, int $needed, string $workType): Project
    {
        $project = Project::firstOrNew(['user_id' => $owner->id, 'title' => $title]);
        $project->fill([
            'provider_name'    => $owner->name,
            'category'         => $category,
            'description'      => "Proyek demonstrasi untuk {$owner->name}. " . 'Digunakan untuk menguji alur lamaran, penyelesaian kerja, dan rating dua arah.',
            'responsibilities' => 'Menyelesaikan tugas sesuai brief, berkoordinasi rutin, dan menyerahkan hasil tepat waktu.',
            'location'         => $workType === 'Remote' ? 'Remote' : 'Bandung',
            'work_type'        => $workType,
            'work_method'      => strtolower($workType),
            'compensation'     => $compensation,
            'skills'           => ['Komunikasi', $category],
            'quota'            => $needed,
            'talents_needed'   => $needed,
            'status'           => Project::STATUS_PUBLISHED,
        ])->save();

        return $project;
    }

    /** Lamaran diterima → catatan pengerjaan otomatis dibuat oleh hook ProjectApplication. */
    private function engagement(User $talent, Project $project): ProjectWorkCompletion
    {
        Application::updateOrCreate(
            ['project_id' => $project->id, 'user_id' => $talent->id],
            ['message' => 'Saya tertarik bergabung (data demo).', 'status' => 'Diterima']
        );

        $application = ProjectApplication::updateOrCreate(
            ['project_id' => $project->id, 'user_id' => $talent->id],
            ['status' => ProjectApplication::STATUS_DITERIMA, 'cover_letter' => 'Saya tertarik bergabung (data demo).', 'reviewed_at' => now()]
        );

        return app(WorkCompletionService::class)->ensureFor($application)
            ?? ProjectWorkCompletion::where('project_application_id', $application->id)->firstOrFail();
    }

    private function markCompleted(ProjectWorkCompletion $c, \Carbon\CarbonInterface $completedAt): void
    {
        $c->forceFill([
            'status'             => ProjectWorkCompletion::STATUS_COMPLETED,
            'requested_by'       => $c->provider_id,
            'requested_at'       => $completedAt->copy()->subDay(),
            'confirmed_by'       => $c->talent_id,
            'confirmed_at'       => $completedAt,
            'completed_at'       => $completedAt,
            'rating_deadline_at' => $completedAt->copy()->addDays(config('rating.window_days')),
        ])->save();
    }

    /** @param array<int, int|null> $scores urutan sesuai config('rating.<group>') */
    private function rating(ProjectWorkCompletion $c, User $rater, User $ratee, string $group, array $scores, string $review, \Carbon\CarbonInterface $at): Rating
    {
        $aspects = config("rating.{$group}");
        $keyed = array_combine(array_keys($aspects), $scores);

        // N/A pembayaran untuk proyek tanpa kompensasi finansial (aturan yang sama dengan aplikasi).
        if ($group === 'provider_aspects' && ! RatingService::hasFinancialCompensation($c->project?->compensation)) {
            $keyed['payment'] = null;
        }

        $final = app(RatingCalculator::class)->weightedScore($aspects, $keyed);
        $raterIsProvider = $group === 'talent_aspects';

        $rating = Rating::updateOrCreate(
            ['project_work_completion_id' => $c->id, 'rater_id' => $rater->id],
            [
                'project_id'  => $c->project_id,
                'ratee_id'    => $ratee->id,
                'rater_role'  => $raterIsProvider ? Rating::ROLE_PENYEDIA : Rating::ROLE_TALENTA,
                'ratee_role'  => $raterIsProvider ? Rating::ROLE_TALENTA : Rating::ROLE_PENYEDIA,
                'scores'      => $keyed,
                'final_score' => $final,
                'review'      => $review,
            ]
        );
        $rating->forceFill(['created_at' => $at, 'updated_at' => $at])->save();

        return $rating;
    }
}
