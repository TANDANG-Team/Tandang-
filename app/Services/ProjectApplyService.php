<?php

namespace App\Services;

use App\Exceptions\ApplyNotAllowedException;
use App\Exceptions\CancelNotAllowedException;
use App\Models\Application;
use App\Models\Project;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Satu-satunya sumber aturan "boleh melamar atau tidak" untuk sebuah proyek.
 *
 * Aturan:
 *  - Batas pelamar aktif = 2 × talents_needed. Aktif = ProjectApplication::ACTIVE_STATUSES
 *    (menunggu, dalam_review, diterima). Lamaran ditolak tidak memakan slot.
 *  - Pendaftaran tertutup jika proyek tidak berstatus dipublikasikan, atau jumlah diterima
 *    sudah mencapai talents_needed (sama dengan scope Project::openForTalent()).
 *  - Satu talenta hanya punya satu lamaran per proyek (dijaga unique index di DB),
 *    sehingga talenta yang pernah ditolak atau membatalkan tidak bisa melamar ulang.
 *  - Talenta boleh membatalkan lamaran yang masih menunggu/dalam review, maksimal
 *    CANCEL_LIMIT kali per CANCEL_WINDOW_DAYS hari (jendela bergulir). Lamaran yang dibatalkan
 *    tidak dihitung ke kuota, jadi slotnya terbuka untuk talenta lain.
 *
 * Semua angka dihitung dinamis dari tabel project_applications, tanpa counter tersimpan.
 */
class ProjectApplyService
{
    public const APPLICANT_MULTIPLIER = 2;

    public const REASON_QUOTA_FULL = 'quota_full';
    public const REASON_REGISTRATION_CLOSED = 'registration_closed';
    public const REASON_ALREADY_APPLIED = 'already_applied';
    public const REASON_REJECTED = 'rejected';
    public const REASON_CANCELLED = 'cancelled';

    public const CANCEL_LIMIT = 3;
    public const CANCEL_WINDOW_DAYS = 7;

    public const CANCEL_REASON_NOT_FOUND = 'not_found';
    public const CANCEL_REASON_NOT_CANCELLABLE = 'not_cancellable';
    public const CANCEL_REASON_LIMIT_REACHED = 'cancel_limit_reached';

    private const HTTP_STATUS = [
        self::REASON_QUOTA_FULL => 409,
        self::REASON_REGISTRATION_CLOSED => 409,
        self::REASON_ALREADY_APPLIED => 409,
        self::REASON_REJECTED => 403,
        self::REASON_CANCELLED => 403,
    ];

    /** Label pendek untuk badge/tombol di UI. */
    public const REASON_LABELS = [
        self::REASON_QUOTA_FULL => 'Kuota pelamar penuh',
        self::REASON_REGISTRATION_CLOSED => 'Pendaftaran ditutup',
        self::REASON_ALREADY_APPLIED => 'Sudah melamar',
        self::REASON_REJECTED => 'Lamaran ditolak',
        self::REASON_CANCELLED => 'Lamaran dibatalkan',
    ];

    public static function httpStatusFor(?string $reason): int
    {
        return self::HTTP_STATUS[$reason] ?? 422;
    }

    public static function talentsNeeded(Project $project): int
    {
        return max(1, (int) $project->talents_needed);
    }

    public static function maxApplicants(Project $project): int
    {
        return self::APPLICANT_MULTIPLIER * self::talentsNeeded($project);
    }

    /**
     * Status pendaftaran sebuah proyek, opsional dari sudut pandang talenta tertentu.
     *
     * @return array{can_apply: bool, reason: ?string, reason_label: ?string, message: ?string, active_count: int,
     *               max_applicants: int, remaining_slots: int, accepted_count: int,
     *               talents_needed: int, application_status: ?string}
     */
    public function getApplyStatus(Project $project, ?User $talenta = null): array
    {
        $ownStatus = $talenta
            ? $this->ownApplicationStatuses($talenta, [$project->id])[$project->id] ?? null
            : null;

        return $this->evaluate(
            $project,
            $this->activeCount($project),
            $project->acceptedCount(),
            $ownStatus
        );
    }

    /**
     * Versi massal untuk halaman daftar. Panggil pada proyek yang dimuat dengan
     * Project::withApplyCounts() agar hanya ada satu query tambahan (lamaran milik talenta).
     *
     * @param  iterable<Project>  $projects
     * @return array<int, array> keyed by project id
     */
    public function getApplyStatuses(iterable $projects, User $talenta): array
    {
        $projects = collect($projects);
        $ownStatuses = $this->ownApplicationStatuses($talenta, $projects->pluck('id')->all());

        return $projects->mapWithKeys(fn (Project $project) => [
            $project->id => $this->evaluate(
                $project,
                $this->activeCount($project),
                $project->acceptedCount(),
                $ownStatuses[$project->id] ?? null
            ),
        ])->all();
    }

    /**
     * Buat lamaran baru secara atomik.
     *
     * Baris proyek dikunci (SELECT ... FOR UPDATE) sehingga apply ke proyek yang sama
     * diproses berurutan: pemohon kedua di slot terakhir baru membaca jumlah lamaran setelah
     * transaksi pertama commit, lalu ditolak dengan alasan kuota penuh.
     *
     * @throws ApplyNotAllowedException
     */
    public function apply(int $projectId, User $talenta, array $attributes = []): ProjectApplication
    {
        return DB::transaction(function () use ($projectId, $talenta, $attributes) {
            $project = Project::whereKey($projectId)->lockForUpdate()->firstOrFail();

            $status = $this->getApplyStatus($project, $talenta);
            if (! $status['can_apply']) {
                throw new ApplyNotAllowedException($status);
            }

            $message = $attributes['message'] ?? null;

            try {
                Application::create([
                    'user_id' => $talenta->id,
                    'project_id' => $project->id,
                    'message' => $message,
                    'status' => 'Menunggu',
                ]);

                $application = ProjectApplication::create([
                    'project_id' => $project->id,
                    'user_id' => $talenta->id,
                    'status' => ProjectApplication::STATUS_MENUNGGU,
                    'cover_letter' => $message,
                    'match_score' => $attributes['match_score'] ?? null,
                    'talent_snapshot' => $attributes['talent_snapshot'] ?? null,
                ]);
            } catch (UniqueConstraintViolationException) {
                // Jaring pengaman level DB (mis. data lama tanpa baris project_applications).
                throw new ApplyNotAllowedException(
                    $this->evaluate($project, $status['active_count'], $status['accepted_count'], ProjectApplication::STATUS_MENUNGGU)
                );
            }

            // Kolom tampilan lama (total lamaran sepanjang waktu); BUKAN dasar perhitungan kuota.
            $project->increment('applicants_count');

            return $application;
        }, 3);
    }

    /**
     * Kuota pembatalan talenta dalam jendela bergulir CANCEL_WINDOW_DAYS hari.
     *
     * @return array{limit: int, used: int, remaining: int, window_days: int, next_available_at: ?string}
     */
    public function getCancelStatus(User $talenta): array
    {
        $recent = ProjectApplication::where('user_id', $talenta->id)
            ->where('cancelled_at', '>', now()->subDays(self::CANCEL_WINDOW_DAYS))
            ->orderBy('cancelled_at')
            ->pluck('cancelled_at')
            ->values();

        $used = $recent->count();
        $remaining = max(0, self::CANCEL_LIMIT - $used);

        // Saat batas habis, slot berikutnya terbuka ketika pembatalan yang membuat jumlahnya
        // tetap >= limit sudah keluar dari jendela 7 hari.
        $nextAvailableAt = $remaining === 0
            ? Carbon::parse($recent[$used - self::CANCEL_LIMIT])->addDays(self::CANCEL_WINDOW_DAYS)
            : null;

        return [
            'limit' => self::CANCEL_LIMIT,
            'used' => $used,
            'remaining' => $remaining,
            'window_days' => self::CANCEL_WINDOW_DAYS,
            'next_available_at' => $nextAvailableAt?->toIso8601String(),
        ];
    }

    /**
     * Batalkan lamaran milik talenta sendiri.
     *
     * Baris proyek dikunci (urutan sama dengan apply/terima/tolak) agar pembatalan tidak
     * bertabrakan dengan keputusan penyedia. Setelah itu baris user dikunci supaya beberapa
     * pembatalan bersamaan tidak bisa melewati batas mingguan.
     *
     * @throws CancelNotAllowedException
     */
    public function cancel(int $projectId, User $talenta): ProjectApplication
    {
        return DB::transaction(function () use ($projectId, $talenta) {
            Project::withTrashed()->whereKey($projectId)->lockForUpdate()->first();
            User::whereKey($talenta->id)->lockForUpdate()->first();

            $application = ProjectApplication::where('project_id', $projectId)
                ->where('user_id', $talenta->id)
                ->first();

            if (! $application) {
                throw new CancelNotAllowedException(self::CANCEL_REASON_NOT_FOUND, 'Lamaran tidak ditemukan.', 404);
            }

            if (! in_array($application->status, ProjectApplication::CANCELLABLE_STATUSES, true)) {
                $message = match ($application->status) {
                    ProjectApplication::STATUS_DIBATALKAN => 'Lamaran ini sudah dibatalkan sebelumnya.',
                    ProjectApplication::STATUS_DITERIMA => 'Lamaran yang sudah diterima tidak dapat dibatalkan dari sini. Silakan hubungi penyedia proyek.',
                    ProjectApplication::STATUS_DITOLAK => 'Lamaran yang sudah ditolak tidak dapat dibatalkan.',
                    default => 'Lamaran ini tidak dapat dibatalkan.',
                };
                throw new CancelNotAllowedException(self::CANCEL_REASON_NOT_CANCELLABLE, $message, 409);
            }

            $cancelStatus = $this->getCancelStatus($talenta);
            if ($cancelStatus['remaining'] === 0) {
                $nextAt = Carbon::parse($cancelStatus['next_available_at'])->format('d/m/Y H:i');
                throw new CancelNotAllowedException(
                    self::CANCEL_REASON_LIMIT_REACHED,
                    'Anda sudah membatalkan ' . self::CANCEL_LIMIT . ' lamaran dalam ' . self::CANCEL_WINDOW_DAYS
                        . " hari terakhir. Pembatalan berikutnya bisa dilakukan mulai {$nextAt}.",
                    429,
                    $cancelStatus
                );
            }

            $application->update([
                'status' => ProjectApplication::STATUS_DIBATALKAN,
                'cancelled_at' => now(),
            ]);

            return $application;
        }, 3);
    }

    private function activeCount(Project $project): int
    {
        if (array_key_exists('active_applications_count', $project->getAttributes())) {
            return (int) $project->active_applications_count;
        }

        return $project->applications()->whereIn('status', ProjectApplication::ACTIVE_STATUSES)->count();
    }

    /**
     * Status lamaran milik talenta per proyek. project_applications adalah sumber utama;
     * tabel applications (sisi talenta) ikut dicek agar data lama tidak bisa dipakai melamar ganda.
     *
     * @return array<int, string> project_id => status project_applications
     */
    private function ownApplicationStatuses(User $talenta, array $projectIds): array
    {
        if (empty($projectIds)) {
            return [];
        }

        $statuses = ProjectApplication::where('user_id', $talenta->id)
            ->whereIn('project_id', $projectIds)
            ->pluck('status', 'project_id')
            ->all();

        $legacy = Application::where('user_id', $talenta->id)
            ->whereIn('project_id', $projectIds)
            ->pluck('status', 'project_id');

        foreach ($legacy as $projectId => $label) {
            $statuses[$projectId] ??= match ($label) {
                'Ditolak' => ProjectApplication::STATUS_DITOLAK,
                'Dibatalkan' => ProjectApplication::STATUS_DIBATALKAN,
                default => ProjectApplication::STATUS_MENUNGGU,
            };
        }

        return $statuses;
    }

    private function evaluate(Project $project, int $activeCount, int $acceptedCount, ?string $ownStatus): array
    {
        $talentsNeeded = self::talentsNeeded($project);
        $maxApplicants = self::maxApplicants($project);

        [$reason, $message] = match (true) {
            $ownStatus === ProjectApplication::STATUS_DITOLAK => [
                self::REASON_REJECTED,
                'Lamaran Anda untuk proyek ini sudah ditolak, sehingga Anda tidak dapat melamar ulang ke proyek yang sama.',
            ],
            $ownStatus === ProjectApplication::STATUS_DIBATALKAN => [
                self::REASON_CANCELLED,
                'Anda sudah membatalkan lamaran ke proyek ini, sehingga tidak dapat mengajukan lagi ke proyek yang sama.',
            ],
            $ownStatus !== null => [
                self::REASON_ALREADY_APPLIED,
                'Anda sudah melamar ke proyek ini. Pantau statusnya di menu Lamaran Saya.',
            ],
            ! $project->isPublished() => [
                self::REASON_REGISTRATION_CLOSED,
                'Pendaftaran proyek ini sudah ditutup oleh penyedia.',
            ],
            $acceptedCount >= $talentsNeeded => [
                self::REASON_REGISTRATION_CLOSED,
                'Pendaftaran proyek ini sudah ditutup karena jumlah talenta yang dibutuhkan sudah terpenuhi.',
            ],
            $activeCount >= $maxApplicants => [
                self::REASON_QUOTA_FULL,
                "Kuota pelamar proyek ini sedang penuh ({$activeCount}/{$maxApplicants}). Slot akan terbuka kembali jika ada lamaran yang ditolak.",
            ],
            default => [null, null],
        };

        return [
            'can_apply' => $reason === null,
            'reason' => $reason,
            'reason_label' => self::REASON_LABELS[$reason] ?? null,
            'message' => $message,
            'active_count' => $activeCount,
            'max_applicants' => $maxApplicants,
            'remaining_slots' => max(0, $maxApplicants - $activeCount),
            'accepted_count' => $acceptedCount,
            'talents_needed' => $talentsNeeded,
            'application_status' => $ownStatus,
        ];
    }
}
