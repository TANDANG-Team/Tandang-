<?php

namespace App\Services;

use App\Exceptions\PromotionException;
use App\Models\Project;
use App\Models\ProjectPromotion;
use App\Models\Rating;
use App\Models\User;
use App\Services\Rating\RatingService;

/**
 * Satu-satunya sumber aturan kelayakan Sponsored Project.
 *
 * Syarat Penyedia: rating usaha ≥ 4,0 (nilai ASLI tanpa pembulatan) DAN ≥ 3 penilaian valid
 * dari Talenta (terpublikasi, tidak dibatalkan karena pelanggaran, dari keterlibatan Selesai).
 * Syarat proyek: milik Penyedia, dipublikasikan, masih menerima lamaran, kuota belum penuh,
 * dan tidak sedang dipromosikan / dalam proses pembayaran.
 *
 * Dipanggil saat membuka checkout, membuat transaksi, dan mengaktifkan promosi.
 */
class PromotionEligibilityService
{
    public function __construct(private RatingService $ratings) {}

    public static function minRating(): float
    {
        return (float) config('promotion.eligibility.min_rating', 4.0);
    }

    public static function minReviews(): int
    {
        return (int) config('promotion.eligibility.min_reviews', 3);
    }

    /**
     * Status kelayakan Penyedia (tanpa melihat proyek tertentu).
     *
     * @return array{eligible: bool, is_provider: bool, average: ?float, raw_average: ?float, count: int,
     *               min_rating: float, min_reviews: int, meets_rating: bool, meets_count: bool,
     *               missing: list<string>, message: ?string, rating_progress: int, count_progress: int}
     */
    public function standing(?User $user): array
    {
        $minRating  = self::minRating();
        $minReviews = self::minReviews();
        $isProvider = $user !== null && $user->isPenyedia();

        $stats = $isProvider ? ($this->ratings->averagesFor([$user->id], Rating::ROLE_PENYEDIA)[$user->id] ?? null) : null;
        $raw   = $stats['raw_average'] ?? null;
        $count = $stats['count'] ?? 0;

        // Perbandingan memakai nilai asli; epsilon hanya untuk galat floating point (bukan pembulatan tampilan).
        $meetsRating = $raw !== null && $raw + 1e-9 >= $minRating;
        $meetsCount  = $count >= $minReviews;

        $missing = [];
        if (! $isProvider) {
            $missing[] = 'Hanya akun Penyedia yang dapat menggunakan Sponsored Project.';
        } else {
            if (! $meetsCount) {
                $missing[] = 'Butuh ' . ($minReviews - $count) . ' penilaian valid lagi dari Talenta (saat ini ' . $count . ' dari minimal ' . $minReviews . ').';
            }
            if ($raw !== null && ! $meetsRating) {
                $missing[] = 'Rating usaha ' . self::format($raw) . '/5 masih di bawah minimal ' . self::format($minRating) . '/5.';
            }
        }

        return [
            'eligible'        => $isProvider && $meetsRating && $meetsCount,
            'is_provider'     => $isProvider,
            'average'         => $raw !== null ? round($raw, 1) : null,
            'raw_average'     => $raw,
            'count'           => $count,
            'min_rating'      => $minRating,
            'min_reviews'     => $minReviews,
            'meets_rating'    => $meetsRating,
            'meets_count'     => $meetsCount,
            'missing'         => $missing,
            'message'         => $this->message($isProvider, $raw, $count, $meetsRating, $meetsCount),
            'rating_progress' => $raw !== null ? (int) min(100, round($raw / $minRating * 100)) : 0,
            'count_progress'  => (int) min(100, round($count / max(1, $minReviews) * 100)),
        ];
    }

    /** Alasan Penyedia tidak memenuhi syarat (null = memenuhi). */
    public function providerBlocker(?User $user): ?string
    {
        $standing = $this->standing($user);

        return $standing['eligible'] ? null : $standing['message'];
    }

    /**
     * Alasan proyek ini tidak boleh dipromosikan oleh $user saat checkout (null = boleh).
     * Urutan: identitas → kepemilikan → syarat rating → syarat proyek.
     */
    public function blockerFor(?User $user, Project $project): ?string
    {
        if (! $user || ! $user->isPenyedia()) {
            return 'Hanya akun Penyedia yang dapat menggunakan Sponsored Project.';
        }
        if ((int) $project->user_id !== (int) $user->id) {
            return 'Proyek tidak dimiliki oleh akun ini.';
        }
        if ($reason = $this->providerBlocker($user)) {
            return $reason;
        }

        return $project->promotionBlocker();
    }

    /** @throws PromotionException */
    public function assertCanCheckout(?User $user, Project $project): void
    {
        if ($reason = $this->blockerFor($user, $project)) {
            throw new PromotionException($reason);
        }
    }

    /**
     * Pengecekan saat AKTIVASI. Promosi yang sedang diproses tidak dianggap memblokir dirinya sendiri.
     *
     * @return array{type: 'provider'|'project', reason: string}|null
     */
    public function activationBlocker(ProjectPromotion $promotion, ?Project $project): ?array
    {
        if (! $project || $project->trashed() || ! $project->isPublished() || $project->isRegistrationOver() || $project->isQuotaFilled()) {
            return ['type' => 'project', 'reason' => 'Proyek tidak lagi memenuhi syarat penayangan (ditutup/penuh/lewat batas pendaftaran/dihapus).'];
        }
        if ((int) $project->user_id !== (int) $promotion->user_id) {
            return ['type' => 'project', 'reason' => 'Proyek tidak dimiliki oleh pembeli promosi.'];
        }
        if (ProjectPromotion::where('project_id', $project->id)->whereKeyNot($promotion->id)->live()->exists()) {
            return ['type' => 'project', 'reason' => 'Proyek sudah memiliki promosi aktif lain.'];
        }
        if ($reason = $this->providerBlocker($promotion->user)) {
            return ['type' => 'provider', 'reason' => $reason];
        }

        return null;
    }

    public static function format(float $value): string
    {
        return number_format($value, 1, ',', '');
    }

    private function message(bool $isProvider, ?float $raw, int $count, bool $meetsRating, bool $meetsCount): ?string
    {
        if (! $isProvider) {
            return 'Hanya akun Penyedia yang dapat menggunakan Sponsored Project.';
        }
        if ($meetsRating && $meetsCount) {
            return null;
        }
        if ($raw === null) {
            return 'Fitur promosi belum tersedia. Selesaikan proyek dan kumpulkan minimal ' . self::minReviews() . ' penilaian dari Talenta untuk membuka Sponsored Project.';
        }
        if (! $meetsRating) {
            // Tampilan 1 desimal; 3,96 tampil "4,0" tetapi tetap belum memenuhi — jelaskan nilai aslinya.
            $shown = self::format($raw);
            $exact = round($raw, 1) >= self::minRating() ? ' (nilai tepatnya ' . number_format($raw, 2, ',', '') . ')' : '';

            return "Rating usaha Anda saat ini {$shown}/5{$exact}. Untuk menggunakan Sponsored Project, tingkatkan rating usaha hingga minimal "
                . self::format(self::minRating()) . ' melalui pengalaman kerja yang baik bersama Talenta.';
        }

        return 'Rating usaha Anda ' . self::format($raw) . '/5 sudah memenuhi, tetapi baru ' . $count . ' penilaian valid. Kumpulkan minimal '
            . self::minReviews() . ' penilaian dari Talenta melalui proyek yang selesai untuk membuka Sponsored Project.';
    }
}
