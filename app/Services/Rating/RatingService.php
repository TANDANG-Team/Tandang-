<?php

namespace App\Services\Rating;

use App\Exceptions\RatingException;
use App\Models\ProjectWorkCompletion;
use App\Models\Rating;
use App\Models\RatingAuditLog;
use App\Models\RatingReport;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RatingService
{
    public const STATE_NOT_RATED      = 'not_rated';       // Belum Dinilai
    public const STATE_WAITING_OTHER  = 'waiting_other';   // Menunggu Rating Pihak Lain
    public const STATE_PUBLISHED      = 'published';       // Rating Dipublikasikan
    public const STATE_WINDOW_CLOSED  = 'window_closed';   // Periode Rating Berakhir
    public const STATE_NOT_AVAILABLE  = 'not_available';   // pekerjaan belum selesai

    public function __construct(private RatingCalculator $calculator) {}

    /** Aspek yang dipakai pemberi rating, beserta penanda N/A untuk proyek tanpa kompensasi. */
    public function aspectsFor(ProjectWorkCompletion $completion, User $rater): array
    {
        if ((int) $rater->id === (int) $completion->provider_id) {
            return config('rating.talent_aspects');
        }

        $aspects = config('rating.provider_aspects');
        $aspects['payment']['not_applicable'] = ! self::hasFinancialCompensation($completion->project?->compensation);

        return $aspects;
    }

    /** Proyek dianggap tanpa kompensasi finansial bila teksnya menyatakan demikian. */
    public static function hasFinancialCompensation(?string $compensation): bool
    {
        $text = strtolower(trim((string) $compensation));
        if ($text === '') {
            return true; // "Sesuai kesepakatan" → tetap bisa dinilai
        }

        foreach (['tanpa bayaran', 'tidak dibayar', 'unpaid', 'sukarela', 'volunteer', 'relawan', 'tanpa kompensasi', 'gratis', 'non-finansial', 'sertifikat saja'] as $marker) {
            if (str_contains($text, $marker)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Kirim rating (final). Pemberi harus pihak yang terlibat, pekerjaan Selesai,
     * dalam jendela 7 hari, dan belum pernah menilai keterlibatan ini.
     *
     * @param array<string, int|null> $scores
     */
    public function submit(ProjectWorkCompletion $completion, User $rater, array $scores, ?string $review): Rating
    {
        return DB::transaction(function () use ($completion, $rater, $scores, $review) {
            $c = ProjectWorkCompletion::whereKey($completion->id)->lockForUpdate()->firstOrFail();

            if (! $c->isParticipant($rater)) {
                throw new RatingException('Hanya pihak yang terlibat langsung yang dapat memberi penilaian.');
            }
            if (! $c->isCompleted()) {
                throw new RatingException('Penilaian hanya bisa diberikan setelah pekerjaan dinyatakan selesai.');
            }
            if (! $c->isRatingWindowOpen()) {
                throw new RatingException('Periode penilaian (7 hari sejak selesai) sudah berakhir.');
            }

            $isProvider = (int) $rater->id === (int) $c->provider_id;
            $rateeId    = $isProvider ? $c->talent_id : $c->provider_id;

            if ((int) $rateeId === (int) $rater->id) {
                throw new RatingException('Tidak bisa menilai diri sendiri.');
            }
            if (Rating::where('project_work_completion_id', $c->id)->where('rater_id', $rater->id)->exists()) {
                throw new RatingException('Anda sudah memberikan penilaian untuk keterlibatan ini.');
            }

            $aspects = $this->aspectsFor($c, $rater);
            $clean = [];
            foreach ($aspects as $key => $aspect) {
                $clean[$key] = ! empty($aspect['not_applicable']) ? null : ($scores[$key] ?? null);
            }

            try {
                $final = $this->calculator->weightedScore($aspects, $clean);
            } catch (InvalidArgumentException $e) {
                throw new RatingException($e->getMessage());
            }

            try {
                $rating = Rating::create([
                    'project_work_completion_id' => $c->id,
                    'project_id'                 => $c->project_id,
                    'rater_id'                   => $rater->id,
                    'ratee_id'                   => $rateeId,
                    'rater_role'                 => $isProvider ? Rating::ROLE_PENYEDIA : Rating::ROLE_TALENTA,
                    'ratee_role'                 => $isProvider ? Rating::ROLE_TALENTA : Rating::ROLE_PENYEDIA,
                    'scores'                     => $clean,
                    'final_score'                => $final,
                    'review'                     => $review !== null && trim($review) !== '' ? trim($review) : null,
                ]);
            } catch (QueryException $e) {
                // Unique constraint (race condition klik ganda)
                throw new RatingException('Anda sudah memberikan penilaian untuk keterlibatan ini.');
            }

            RatingAuditLog::record('rating_submitted', "Nilai akhir {$final}", $rater, $rating);

            // Kedua pihak sudah menilai → publikasikan bersamaan.
            if (Rating::where('project_work_completion_id', $c->id)->count() >= 2) {
                $this->publishFor($c);
            }

            return $rating->fresh();
        });
    }

    /** Scheduler: publikasikan rating yang periode penilaiannya sudah berakhir. */
    public function publishExpiredWindows(): int
    {
        $count = 0;
        ProjectWorkCompletion::where('status', ProjectWorkCompletion::STATUS_COMPLETED)
            ->where('rating_deadline_at', '<=', now())
            ->whereHas('ratings', fn ($q) => $q->whereNull('published_at'))
            ->get()
            ->each(function (ProjectWorkCompletion $c) use (&$count) {
                $count += $this->publishFor($c);
            });

        return $count;
    }

    private function publishFor(ProjectWorkCompletion $c): int
    {
        $ratings = Rating::where('project_work_completion_id', $c->id)->whereNull('published_at')->get();
        foreach ($ratings as $rating) {
            $rating->update(['published_at' => now()]);
            RatingAuditLog::record('rating_published', null, null, $rating);
        }

        return $ratings->count();
    }

    /** Status rating dari sudut pandang satu pihak (untuk tombol & badge). */
    public function stateFor(ProjectWorkCompletion $c, User $user): string
    {
        if (! $c->isCompleted()) {
            return self::STATE_NOT_AVAILABLE;
        }

        $ratings = $c->relationLoaded('ratings') ? $c->ratings : $c->ratings()->get();
        $mine    = $ratings->firstWhere('rater_id', $user->id);
        $theirs  = $ratings->first(fn ($r) => (int) $r->rater_id !== (int) $user->id);
        $windowOpen = $c->isRatingWindowOpen();

        return match (true) {
            $mine && ($theirs || ! $windowOpen) => self::STATE_PUBLISHED,
            $mine                                => self::STATE_WAITING_OTHER,
            ! $windowOpen                        => self::STATE_WINDOW_CLOSED,
            default                              => self::STATE_NOT_RATED,
        };
    }

    public static function stateLabel(string $state): string
    {
        return match ($state) {
            self::STATE_NOT_RATED     => 'Belum Dinilai',
            self::STATE_WAITING_OTHER => 'Menunggu Rating Pihak Lain',
            self::STATE_PUBLISHED     => 'Rating Dipublikasikan',
            self::STATE_WINDOW_CLOSED => 'Periode Rating Berakhir',
            default                   => 'Belum bisa dinilai',
        };
    }

    /**
     * Ringkasan reputasi publik. Rata-rata dari rating terpublikasi per proyek yang dinilai
     * (bukan dari jumlah lamaran). Membeli promosi tidak memengaruhi angka ini.
     *
     * @return array{average: ?float, label: string, count: int, completed: int, aspects: array, reviews: Collection}
     */
    public function summaryFor(User $user, string $role): array
    {
        // Hanya penilaian yang DITERIMA akun ini dari pihak lawan (bukan yang ia berikan).
        $ratings = $this->receivedQuery($role)
            ->where('ratee_id', $user->id)
            ->with(['rater:id,name', 'project:id,title'])
            ->latest('published_at')
            ->get();

        $aspectConfig = $role === Rating::ROLE_TALENTA ? config('rating.talent_aspects') : config('rating.provider_aspects');
        $aspects = [];
        foreach ($aspectConfig as $key => $aspect) {
            $values = $ratings->pluck("scores.{$key}")->filter(fn ($v) => $v !== null);
            $aspects[$key] = [
                'label'   => $aspect['label'],
                'weight'  => $aspect['weight'],
                'average' => $values->isNotEmpty() ? round($values->avg(), 1) : null,
                'count'   => $values->count(),
            ];
        }

        $raw     = $ratings->isNotEmpty() ? (float) $ratings->avg('final_score') : null;
        $average = $raw !== null ? round($raw, 1) : null;

        $completed = ProjectWorkCompletion::where($role === Rating::ROLE_TALENTA ? 'talent_id' : 'provider_id', $user->id)
            ->where('status', ProjectWorkCompletion::STATUS_COMPLETED)
            ->count();

        return [
            'average'     => $average,       // untuk tampilan (1 desimal)
            'raw_average' => $raw,           // tanpa pembulatan — dipakai untuk aturan bisnis
            'label'       => RatingCalculator::label($average),
            'count'     => $ratings->count(),
            'completed' => $completed,
            'aspects'   => $aspects,
            'reviews'   => $ratings->filter(fn ($r) => $r->visible_review !== null)->take(10)->values(),
        ];
    }

    /** Query dasar rating valid yang diterima suatu peran dari pihak lawannya. */
    public function receivedQuery(string $role)
    {
        $raterRole = $role === Rating::ROLE_TALENTA ? Rating::ROLE_PENYEDIA : Rating::ROLE_TALENTA;

        return Rating::valid()->where('ratee_role', $role)->where('rater_role', $raterRole);
    }

    /**
     * Rata-rata & jumlah penilaian untuk banyak akun sekaligus (satu query agregat, tanpa N+1).
     *
     * @param iterable<int> $userIds
     * @return array<int, array{average: float, raw_average: float, count: int}>
     */
    public function averagesFor(iterable $userIds, string $role): array
    {
        $ids = collect($userIds)->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return [];
        }

        return $this->receivedQuery($role)
            ->whereIn('ratee_id', $ids)
            ->selectRaw('ratee_id, AVG(final_score) as avg_score, COUNT(*) as total')
            ->groupBy('ratee_id')
            ->get()
            ->mapWithKeys(fn ($row) => [(int) $row->ratee_id => [
                'average'     => round((float) $row->avg_score, 1),
                'raw_average' => (float) $row->avg_score,
                'count'       => (int) $row->total,
            ]])
            ->all();
    }

    // ── Moderasi ────────────────────────────────────────────────────────────

    /** Batalkan rating karena pelanggaran: tidak dihitung ke reputasi, data tetap disimpan untuk audit. */
    public function voidRating(Rating $rating, User $admin, string $reason): Rating
    {
        if ($rating->isVoided()) {
            throw new RatingException('Rating ini sudah dibatalkan.');
        }
        $rating->update(['voided_at' => now(), 'voided_by' => $admin->id, 'void_reason' => $reason]);
        RatingAuditLog::record('rating_voided', $reason, $admin, $rating);

        return $rating->fresh();
    }

    public function unvoidRating(Rating $rating, User $admin, string $reason): Rating
    {
        if (! $rating->isVoided()) {
            throw new RatingException('Rating ini tidak sedang dibatalkan.');
        }
        $rating->update(['voided_at' => null, 'voided_by' => null, 'void_reason' => null]);
        RatingAuditLog::record('rating_unvoided', $reason, $admin, $rating);

        return $rating->fresh();
    }

    public function report(Rating $rating, User $reporter, string $type, string $reason): RatingReport
    {
        $isRatee = (int) $reporter->id === (int) $rating->ratee_id;
        $isRater = (int) $reporter->id === (int) $rating->rater_id;

        if ($type === RatingReport::TYPE_CORRECTION && ! $isRater) {
            throw new RatingException('Permohonan koreksi hanya bisa diajukan oleh pemberi rating.');
        }
        if ($type === RatingReport::TYPE_VIOLATION && ! $isRatee) {
            throw new RatingException('Laporan pelanggaran hanya bisa diajukan oleh pihak yang dinilai.');
        }
        if (RatingReport::where('rating_id', $rating->id)->where('reporter_id', $reporter->id)->where('type', $type)->where('status', 'open')->exists()) {
            throw new RatingException('Laporan Anda untuk ulasan ini masih diproses Admin.');
        }

        $report = RatingReport::updateOrCreate(
            ['rating_id' => $rating->id, 'reporter_id' => $reporter->id, 'type' => $type],
            ['reason' => $reason, 'status' => 'open', 'handled_by' => null, 'handled_at' => null, 'admin_note' => null]
        );
        RatingAuditLog::record('report_created', $report->type_label . ': ' . $reason, $reporter, $rating);

        return $report;
    }

    /** Menyembunyikan ULASAN (teks). Nilai bintang tidak diubah agar tidak memanipulasi reputasi. */
    public function hideReview(Rating $rating, User $admin, string $reason): Rating
    {
        if ($rating->review_hidden) {
            throw new RatingException('Ulasan ini sudah disembunyikan.');
        }
        $rating->update(['review_hidden' => true, 'hidden_reason' => $reason, 'hidden_by' => $admin->id, 'hidden_at' => now()]);
        RatingAuditLog::record('review_hidden', $reason, $admin, $rating);

        return $rating->fresh();
    }

    public function restoreReview(Rating $rating, User $admin, string $reason): Rating
    {
        if (! $rating->review_hidden) {
            throw new RatingException('Ulasan ini tidak sedang disembunyikan.');
        }
        $rating->update(['review_hidden' => false, 'hidden_reason' => null, 'hidden_by' => null, 'hidden_at' => null]);
        RatingAuditLog::record('review_restored', $reason, $admin, $rating);

        return $rating->fresh();
    }

    public function handleReport(RatingReport $report, User $admin, bool $resolved, string $note): RatingReport
    {
        if ($report->status !== 'open') {
            throw new RatingException('Laporan ini sudah ditangani.');
        }
        $report->update(['status' => $resolved ? 'resolved' : 'dismissed', 'handled_by' => $admin->id, 'handled_at' => now(), 'admin_note' => $note]);
        RatingAuditLog::record($resolved ? 'report_resolved' : 'report_dismissed', $note, $admin, $report->rating);

        return $report->fresh();
    }
}
