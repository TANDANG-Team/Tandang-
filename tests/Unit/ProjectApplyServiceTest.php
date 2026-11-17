<?php

namespace Tests\Unit;

use App\Exceptions\ApplyNotAllowedException;
use App\Models\Application;
use App\Models\Project;
use App\Models\ProjectApplication;
use App\Services\ProjectApplyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesApplyFixtures;
use Tests\TestCase;

class ProjectApplyServiceTest extends TestCase
{
    use CreatesApplyFixtures, RefreshDatabase;

    private ProjectApplyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProjectApplyService();
    }

    private function applyAll(Project $project, array $talents): void
    {
        foreach ($talents as $talent) {
            $this->service->apply($project->id, $talent);
        }
    }

    private function assertApplyRejected(Project $project, $talent, string $reason): void
    {
        try {
            $this->service->apply($project->id, $talent);
            $this->fail("Apply seharusnya ditolak dengan alasan {$reason}.");
        } catch (ApplyNotAllowedException $e) {
            $this->assertSame($reason, $e->reason());
            $this->assertSame(ProjectApplyService::httpStatusFor($reason), $e->httpStatus());
        }
    }

    public function test_max_applicants_is_twice_talents_needed(): void
    {
        $provider = $this->makeProvider();

        $this->assertSame(2, ProjectApplyService::maxApplicants($this->makeProject($provider, 1)));
        $this->assertSame(6, ProjectApplyService::maxApplicants($this->makeProject($provider, 3)));
    }

    public function test_http_status_per_reason(): void
    {
        $this->assertSame(409, ProjectApplyService::httpStatusFor(ProjectApplyService::REASON_QUOTA_FULL));
        $this->assertSame(409, ProjectApplyService::httpStatusFor(ProjectApplyService::REASON_REGISTRATION_CLOSED));
        $this->assertSame(409, ProjectApplyService::httpStatusFor(ProjectApplyService::REASON_ALREADY_APPLIED));
        $this->assertSame(403, ProjectApplyService::httpStatusFor(ProjectApplyService::REASON_REJECTED));
    }

    public function test_apply_succeeds_up_to_exactly_2n_then_next_is_quota_full(): void
    {
        $project = $this->makeProject($this->makeProvider(), 3);
        $talents = $this->makeTalents(7);

        $this->applyAll($project, array_slice($talents, 0, 6));

        $status = $this->service->getApplyStatus($project, $talents[6]);
        $this->assertFalse($status['can_apply']);
        $this->assertSame(ProjectApplyService::REASON_QUOTA_FULL, $status['reason']);
        $this->assertSame(6, $status['active_count']);
        $this->assertSame(6, $status['max_applicants']);
        $this->assertSame(0, $status['remaining_slots']);

        $this->assertApplyRejected($project, $talents[6], ProjectApplyService::REASON_QUOTA_FULL);
        $this->assertSame(6, $project->applications()->count());
    }

    public function test_project_needing_one_talent_allows_two_applicants(): void
    {
        $project = $this->makeProject($this->makeProvider(), 1);
        [$a, $b, $c] = $this->makeTalents(3);

        $this->applyAll($project, [$a, $b]);

        $this->assertApplyRejected($project, $c, ProjectApplyService::REASON_QUOTA_FULL);
    }

    public function test_rejection_frees_slots_and_is_not_counted(): void
    {
        $project = $this->makeProject($this->makeProvider(), 3);
        $talents = $this->makeTalents(8);
        $this->applyAll($project, array_slice($talents, 0, 6));

        $this->applicationOf($project, $talents[0])->update(['status' => ProjectApplication::STATUS_DITOLAK]);
        $this->applicationOf($project, $talents[1])->update(['status' => ProjectApplication::STATUS_DITOLAK]);

        $status = $this->service->getApplyStatus($project, $talents[6]);
        $this->assertTrue($status['can_apply']);
        $this->assertSame(4, $status['active_count']);
        $this->assertSame(2, $status['remaining_slots']);

        $this->applyAll($project, [$talents[6], $talents[7]]);
        $this->assertSame(0, $this->service->getApplyStatus($project)['remaining_slots']);
    }

    public function test_review_and_accepted_count_as_active(): void
    {
        $project = $this->makeProject($this->makeProvider(), 2);
        $talents = $this->makeTalents(4);
        $this->applyAll($project, array_slice($talents, 0, 3));

        $this->applicationOf($project, $talents[0])->update(['status' => ProjectApplication::STATUS_DALAM_REVIEW]);
        $this->applicationOf($project, $talents[1])->update(['status' => ProjectApplication::STATUS_DITERIMA]);

        $status = $this->service->getApplyStatus($project, $talents[3]);
        $this->assertSame(3, $status['active_count']);
        $this->assertSame(1, $status['accepted_count']);
        $this->assertTrue($status['can_apply']);
    }

    public function test_rejected_talent_cannot_reapply_even_when_slots_are_open(): void
    {
        $project = $this->makeProject($this->makeProvider(), 2);
        [$rejected, $other] = $this->makeTalents(2);

        $this->service->apply($project->id, $rejected);
        $this->applicationOf($project, $rejected)->update(['status' => ProjectApplication::STATUS_DITOLAK]);

        $this->assertSame(ProjectApplyService::REASON_REJECTED, $this->service->getApplyStatus($project, $rejected)['reason']);
        $this->assertApplyRejected($project, $rejected, ProjectApplyService::REASON_REJECTED);
        $this->assertSame(ProjectApplication::STATUS_DITOLAK, $this->applicationOf($project, $rejected)->status);

        $this->service->apply($project->id, $other);
        $this->assertNotNull($this->applicationOf($project, $other));
    }

    /**
     * Regresi: data lama/seeder bisa punya baris project_applications tanpa baris applications.
     * Implementasi lama memakai updateOrCreate sehingga status "ditolak" ter-reset ke "menunggu".
     */
    public function test_rejected_provider_side_row_without_talent_row_still_blocks_reapply(): void
    {
        $project = $this->makeProject($this->makeProvider(), 2);
        $talent = $this->makeTalent();

        ProjectApplication::create([
            'project_id' => $project->id,
            'user_id' => $talent->id,
            'status' => ProjectApplication::STATUS_DITOLAK,
        ]);
        $this->assertFalse(Application::where('user_id', $talent->id)->exists());

        $this->assertApplyRejected($project, $talent, ProjectApplyService::REASON_REJECTED);
        $this->assertSame(ProjectApplication::STATUS_DITOLAK, $this->applicationOf($project, $talent)->status);
    }

    public function test_same_talent_cannot_apply_twice(): void
    {
        $project = $this->makeProject($this->makeProvider(), 2);
        $talent = $this->makeTalent();

        $this->service->apply($project->id, $talent);
        $this->assertApplyRejected($project, $talent, ProjectApplyService::REASON_ALREADY_APPLIED);

        $this->assertSame(1, $project->applications()->count());
        $this->assertSame(1, Application::where('project_id', $project->id)->count());
    }

    public function test_registration_closes_when_accepted_reaches_talents_needed(): void
    {
        $project = $this->makeProject($this->makeProvider(), 2);
        $talents = $this->makeTalents(4);
        $this->applyAll($project, array_slice($talents, 0, 3));

        $this->applicationOf($project, $talents[0])->update(['status' => ProjectApplication::STATUS_DITERIMA]);
        $this->assertTrue($this->service->getApplyStatus($project, $talents[3])['can_apply']);

        $this->applicationOf($project, $talents[1])->update(['status' => ProjectApplication::STATUS_DITERIMA]);

        $status = $this->service->getApplyStatus($project, $talents[3]);
        $this->assertFalse($status['can_apply']);
        $this->assertSame(ProjectApplyService::REASON_REGISTRATION_CLOSED, $status['reason']);
        $this->assertSame(1, $status['remaining_slots'], 'Slot kuota boleh tersisa, tapi pendaftaran tetap ditutup.');
        $this->assertApplyRejected($project, $talents[3], ProjectApplyService::REASON_REGISTRATION_CLOSED);
        $this->assertFalse(Project::openForTalent()->whereKey($project->id)->exists());
    }

    public function test_unpublished_project_is_registration_closed(): void
    {
        $provider = $this->makeProvider();
        $talent = $this->makeTalent();

        foreach ([Project::STATUS_DRAFT, Project::STATUS_CLOSED, Project::STATUS_ARCHIVED] as $status) {
            $project = $this->makeProject($provider, 2, ['status' => $status]);
            $this->assertApplyRejected($project, $talent, ProjectApplyService::REASON_REGISTRATION_CLOSED);
        }
    }

    public function test_changing_talents_needed_recalculates_quota_without_deleting_applications(): void
    {
        $project = $this->makeProject($this->makeProvider(), 3);
        $talents = $this->makeTalents(6);
        $this->applyAll($project, array_slice($talents, 0, 5));

        $project->update(['talents_needed' => 1, 'quota' => 1]);

        $status = $this->service->getApplyStatus($project->fresh(), $talents[5]);
        $this->assertSame(ProjectApplyService::REASON_QUOTA_FULL, $status['reason']);
        $this->assertSame(5, $status['active_count']);
        $this->assertSame(2, $status['max_applicants']);
        $this->assertSame(0, $status['remaining_slots']);
        $this->assertSame(5, $project->applications()->count());

        $project->update(['talents_needed' => 4, 'quota' => 4]);
        $status = $this->service->getApplyStatus($project->fresh(), $talents[5]);
        $this->assertTrue($status['can_apply']);
        $this->assertSame(3, $status['remaining_slots']);
    }

    public function test_bulk_statuses_match_single_status(): void
    {
        $provider = $this->makeProvider();
        $talent = $this->makeTalent();
        $full = $this->makeProject($provider, 1);
        $open = $this->makeProject($provider, 2);
        $applied = $this->makeProject($provider, 2);
        $this->applyAll($full, $this->makeTalents(2));
        $this->service->apply($applied->id, $talent);

        $projects = Project::withApplyCounts()->whereKey([$full->id, $open->id, $applied->id])->get();
        $bulk = $this->service->getApplyStatuses($projects, $talent);

        foreach ([$full, $open, $applied] as $project) {
            $this->assertSame($this->service->getApplyStatus($project->fresh(), $talent), $bulk[$project->id]);
        }
        $this->assertSame(ProjectApplyService::REASON_QUOTA_FULL, $bulk[$full->id]['reason']);
        $this->assertTrue($bulk[$open->id]['can_apply']);
        $this->assertSame(ProjectApplyService::REASON_ALREADY_APPLIED, $bulk[$applied->id]['reason']);
    }
}
