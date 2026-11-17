<?php

namespace App\Services\Rating;

use App\Exceptions\RatingException;
use App\Models\ProjectApplication;
use App\Models\ProjectWorkCompletion;
use App\Models\RatingAuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Alur penyelesaian pekerjaan per Talenta:
 *   Sedang Berlangsung → (salah satu pihak mengajukan) Menunggu Konfirmasi
 *   → pihak lain konfirmasi = Selesai | pihak lain menolak = Disengketakan → Admin memutuskan.
 * Tidak ada pihak yang bisa menetapkan "Selesai" sepihak; bila pihak lain diam
 * melewati batas konfirmasi, scheduler menyelesaikannya otomatis (tercatat di audit log).
 */
class WorkCompletionService
{
    public function ensureFor(ProjectApplication $application): ?ProjectWorkCompletion
    {
        $project = $application->project()->withTrashed()->first();
        if (! $project || ! $project->user_id || $application->status !== ProjectApplication::STATUS_DITERIMA) {
            return null;
        }

        return ProjectWorkCompletion::firstOrCreate(
            ['project_application_id' => $application->id],
            [
                'project_id'  => $project->id,
                'talent_id'   => $application->user_id,
                'provider_id' => $project->user_id,
                'status'      => ProjectWorkCompletion::STATUS_IN_PROGRESS,
            ]
        );
    }

    /** Lengkapi catatan untuk lamaran diterima yang dibuat sebelum fitur ini ada. */
    public function backfillForUser(User $user): void
    {
        ProjectApplication::where('status', ProjectApplication::STATUS_DITERIMA)
            ->whereDoesntHave('workCompletion')
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('project', fn ($p) => $p->withTrashed()->where('user_id', $user->id));
            })
            ->get()
            ->each(fn (ProjectApplication $a) => $this->ensureFor($a));
    }

    public function request(ProjectWorkCompletion $completion, User $user): ProjectWorkCompletion
    {
        return $this->transition($completion, $user, function (ProjectWorkCompletion $c) use ($user) {
            if ($c->status !== ProjectWorkCompletion::STATUS_IN_PROGRESS) {
                throw new RatingException('Pengajuan selesai hanya bisa dibuat saat pekerjaan sedang berlangsung.');
            }
            $c->update([
                'status'       => ProjectWorkCompletion::STATUS_PENDING_CONFIRMATION,
                'requested_by' => $user->id,
                'requested_at' => now(),
            ]);
            RatingAuditLog::record('completion_requested', null, $user, null, $c);
        });
    }

    public function confirm(ProjectWorkCompletion $completion, User $user): ProjectWorkCompletion
    {
        return $this->transition($completion, $user, function (ProjectWorkCompletion $c) use ($user) {
            if ($c->status !== ProjectWorkCompletion::STATUS_PENDING_CONFIRMATION) {
                throw new RatingException('Tidak ada pengajuan selesai yang perlu dikonfirmasi.');
            }
            if ((int) $c->requested_by === (int) $user->id) {
                throw new RatingException('Konfirmasi harus dilakukan oleh pihak lain, bukan pengaju.');
            }
            $this->complete($c, $user);
            RatingAuditLog::record('completion_confirmed', null, $user, null, $c);
        });
    }

    public function dispute(ProjectWorkCompletion $completion, User $user, string $reason): ProjectWorkCompletion
    {
        return $this->transition($completion, $user, function (ProjectWorkCompletion $c) use ($user, $reason) {
            if ($c->status !== ProjectWorkCompletion::STATUS_PENDING_CONFIRMATION) {
                throw new RatingException('Sengketa hanya bisa diajukan atas pengajuan selesai yang menunggu konfirmasi.');
            }
            if ((int) $c->requested_by === (int) $user->id) {
                throw new RatingException('Pengaju tidak bisa menyengketakan pengajuannya sendiri.');
            }
            $c->update([
                'status'         => ProjectWorkCompletion::STATUS_DISPUTED,
                'disputed_by'    => $user->id,
                'disputed_at'    => now(),
                'dispute_reason' => $reason,
            ]);
            RatingAuditLog::record('completion_disputed', $reason, $user, null, $c);
        });
    }

    /** Admin memutuskan sengketa: selesai (rating dibuka) atau dilanjutkan (kembali berlangsung). */
    public function resolveDispute(ProjectWorkCompletion $completion, User $admin, bool $markCompleted, string $note): ProjectWorkCompletion
    {
        return DB::transaction(function () use ($completion, $admin, $markCompleted, $note) {
            $c = ProjectWorkCompletion::whereKey($completion->id)->lockForUpdate()->firstOrFail();
            if ($c->status !== ProjectWorkCompletion::STATUS_DISPUTED) {
                throw new RatingException('Keterlibatan ini tidak sedang disengketakan.');
            }

            $c->fill(['resolved_by' => $admin->id, 'resolved_at' => now(), 'resolution_note' => $note]);

            if ($markCompleted) {
                $this->complete($c, $admin);
            } else {
                $c->fill(['status' => ProjectWorkCompletion::STATUS_IN_PROGRESS, 'requested_by' => null, 'requested_at' => null])->save();
            }

            RatingAuditLog::record($markCompleted ? 'dispute_completed' : 'dispute_reopened', $note, $admin, null, $c);

            return $c->fresh();
        });
    }

    /** Scheduler: pengajuan yang tidak direspons melewati batas → selesai otomatis. */
    public function autoConfirmStale(): int
    {
        $count = 0;
        ProjectWorkCompletion::where('status', ProjectWorkCompletion::STATUS_PENDING_CONFIRMATION)
            ->where('requested_at', '<=', now()->subDays(config('rating.auto_confirm_days')))
            ->get()
            ->each(function (ProjectWorkCompletion $c) use (&$count) {
                DB::transaction(function () use ($c, &$count) {
                    $locked = ProjectWorkCompletion::whereKey($c->id)->lockForUpdate()->first();
                    if ($locked && $locked->status === ProjectWorkCompletion::STATUS_PENDING_CONFIRMATION) {
                        $this->complete($locked, null);
                        RatingAuditLog::record('completion_auto', 'Tidak ada respons dalam ' . config('rating.auto_confirm_days') . ' hari.', null, null, $locked);
                        $count++;
                    }
                });
            });

        return $count;
    }

    private function complete(ProjectWorkCompletion $c, ?User $by): void
    {
        $c->fill([
            'status'             => ProjectWorkCompletion::STATUS_COMPLETED,
            'confirmed_by'       => $by?->id,
            'confirmed_at'       => now(),
            'completed_at'       => now(),
            'rating_deadline_at' => now()->addDays(config('rating.window_days')),
        ])->save();
    }

    private function transition(ProjectWorkCompletion $completion, User $user, callable $apply): ProjectWorkCompletion
    {
        return DB::transaction(function () use ($completion, $user, $apply) {
            $c = ProjectWorkCompletion::whereKey($completion->id)->lockForUpdate()->firstOrFail();
            if (! $c->isParticipant($user)) {
                throw new RatingException('Anda tidak terlibat dalam pengerjaan proyek ini.');
            }
            $apply($c);

            return $c->fresh();
        });
    }
}
