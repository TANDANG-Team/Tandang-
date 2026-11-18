<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Project;
use App\Models\ProjectApplication;
use App\Models\ProviderNotification;
use App\Models\User;
use App\Services\ProjectApplyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesApplyFixtures;
use Tests\TestCase;

/**
 * Pembatalan lamaran oleh talenta: maks. 3 kali per 7 hari (bergulir), slot kuota terbuka,
 * dan talenta yang membatalkan tidak bisa mengajukan lagi ke proyek yang sama.
 */
class ProjectApplicationCancelTest extends TestCase
{
    use CreatesApplyFixtures, RefreshDatabase;

    private function apply(User $talent, Project $project)
    {
        return $this->actingAs($talent)->postJson("/talent/proyek/{$project->id}/lamar", ['message' => 'Halo']);
    }

    private function cancel(User $talent, Project $project)
    {
        return $this->actingAs($talent)->patchJson("/talent/proyek/{$project->id}/batal");
    }

    /** Buat $count proyek yang sudah dilamar talenta (status menunggu). */
    private function appliedProjects(User $talent, int $count, ?User $provider = null): array
    {
        $provider ??= $this->makeProvider();
        $projects = [];
        for ($i = 0; $i < $count; $i++) {
            $projects[] = $project = $this->makeProject($provider, 2);
            $this->apply($talent, $project)->assertCreated();
        }

        return $projects;
    }

    public function test_cancel_frees_slot_syncs_status_and_notifies_provider(): void
    {
        $provider = $this->makeProvider();
        $project = $this->makeProject($provider, 1);
        [$a, $b, $c] = $this->makeTalents(3);
        $this->apply($a, $project)->assertCreated();
        $this->apply($b, $project)->assertCreated();
        $this->apply($c, $project)->assertStatus(409)->assertJsonPath('reason', 'quota_full');

        $this->cancel($a, $project)
            ->assertOk()
            ->assertJsonPath('cancel_status.used', 1)
            ->assertJsonPath('cancel_status.remaining', 2);

        $application = $this->applicationOf($project, $a);
        $this->assertSame('dibatalkan', $application->status);
        $this->assertNotNull($application->cancelled_at);
        $this->assertSame('Dibatalkan', Application::where('project_id', $project->id)->where('user_id', $a->id)->value('status'));
        $this->assertTrue(ProviderNotification::where('user_id', $provider->id)->where('type', 'applicant_cancelled')->exists());

        // Slot terbuka untuk talenta lain.
        $this->apply($c, $project)->assertCreated();
    }

    public function test_cancelled_talent_cannot_reapply_but_others_can(): void
    {
        $project = $this->makeProject($this->makeProvider(), 2);
        [$canceller, $other] = $this->makeTalents(2);
        $this->apply($canceller, $project)->assertCreated();
        $this->cancel($canceller, $project)->assertOk();

        $this->apply($canceller, $project)
            ->assertForbidden()
            ->assertJsonPath('reason', ProjectApplyService::REASON_CANCELLED);
        $this->assertSame('dibatalkan', $this->applicationOf($project, $canceller)->status);

        $this->actingAs($canceller)->getJson("/talent/proyek/{$project->id}/status-lamaran")
            ->assertJson(['can_apply' => false, 'reason' => 'cancelled', 'reason_label' => 'Lamaran dibatalkan']);

        $this->apply($other, $project)->assertCreated();
    }

    public function test_in_review_application_can_be_cancelled(): void
    {
        $provider = $this->makeProvider();
        $project = $this->makeProject($provider, 2);
        $talent = $this->makeTalent();
        $this->apply($talent, $project)->assertCreated();
        $application = $this->applicationOf($project, $talent);
        $this->actingAs($provider)->patch("/penyedia/pelamar/{$application->id}/status", ['status' => 'dalam_review']);

        $this->cancel($talent, $project)->assertOk();
        $this->assertSame('dibatalkan', $application->fresh()->status);
    }

    public function test_fourth_cancellation_within_seven_days_is_rejected_with_429(): void
    {
        $talent = $this->makeTalent();
        $projects = $this->appliedProjects($talent, 4);

        foreach (array_slice($projects, 0, 3) as $i => $project) {
            $this->travel(1)->hours();
            $this->cancel($talent, $project)->assertOk()->assertJsonPath('cancel_status.remaining', 2 - $i);
        }

        $this->cancel($talent, $projects[3])
            ->assertStatus(429)
            ->assertJsonPath('reason', ProjectApplyService::CANCEL_REASON_LIMIT_REACHED)
            ->assertJsonPath('cancel_status.remaining', 0);
        $this->assertSame('menunggu', $this->applicationOf($projects[3], $talent)->status);

        // Form biasa: kembali dengan pesan alasan.
        $this->actingAs($talent)->from('/talent/lamaran')
            ->patch("/talent/proyek/{$projects[3]->id}/batal")
            ->assertRedirect('/talent/lamaran')
            ->assertSessionHas('warning', fn ($msg) => str_contains($msg, 'Anda sudah membatalkan 3 lamaran dalam 7 hari terakhir'));
    }

    public function test_limit_is_a_rolling_seven_day_window(): void
    {
        $talent = $this->makeTalent();
        $projects = $this->appliedProjects($talent, 5);

        // Pembatalan pada hari ke-0, ke-2, dan ke-4.
        $this->cancel($talent, $projects[0])->assertOk();
        $this->travel(2)->days();
        $this->cancel($talent, $projects[1])->assertOk();
        $this->travel(2)->days();
        $this->cancel($talent, $projects[2])->assertOk();

        // Hari ke-6: jendela masih memuat 3 pembatalan. Slot berikutnya = hari ke-7 (pembatalan pertama + 7 hari).
        $this->travel(2)->days();
        $expectedNext = now()->subDays(6)->addDays(7);
        $status = app(ProjectApplyService::class)->getCancelStatus($talent);
        $this->assertSame(0, $status['remaining']);
        $this->assertEqualsWithDelta($expectedNext->timestamp, strtotime($status['next_available_at']), 2);
        $this->cancel($talent, $projects[3])->assertStatus(429);

        // Hari ke-7 lewat sedikit: pembatalan hari ke-0 keluar dari jendela → sisa 1.
        $this->travel(1)->days();
        $this->travel(1)->minutes();
        $this->cancel($talent, $projects[3])->assertOk()->assertJsonPath('cancel_status.remaining', 0);
        $this->cancel($talent, $projects[4])->assertStatus(429);
    }

    public function test_only_pending_applications_can_be_cancelled(): void
    {
        $provider = $this->makeProvider();
        $talent = $this->makeTalent();
        [$accepted, $rejected, $cancelled] = $this->appliedProjects($talent, 3, $provider);

        $this->actingAs($provider)->patch('/penyedia/pelamar/' . $this->applicationOf($accepted, $talent)->id . '/status', ['status' => 'diterima']);
        $this->actingAs($provider)->patch('/penyedia/pelamar/' . $this->applicationOf($rejected, $talent)->id . '/status', ['status' => 'ditolak']);
        $this->cancel($talent, $cancelled)->assertOk();

        $this->cancel($talent, $accepted)->assertStatus(409)->assertJsonPath('reason', 'not_cancellable');
        $this->cancel($talent, $rejected)->assertStatus(409)->assertJsonPath('reason', 'not_cancellable');
        $this->cancel($talent, $cancelled)->assertStatus(409)->assertJsonPath('reason', 'not_cancellable');

        // Gagal membatalkan tidak memakai jatah.
        $this->assertSame(2, app(ProjectApplyService::class)->getCancelStatus($talent)['remaining']);
    }

    public function test_cannot_cancel_application_that_does_not_exist_or_belongs_to_someone_else(): void
    {
        $project = $this->makeProject($this->makeProvider(), 2);
        [$owner, $stranger] = $this->makeTalents(2);
        $this->apply($owner, $project)->assertCreated();

        $this->cancel($stranger, $project)->assertNotFound()->assertJsonPath('reason', 'not_found');
        $this->assertSame('menunggu', $this->applicationOf($project, $owner)->status);
    }

    public function test_provider_cannot_process_cancelled_application(): void
    {
        $provider = $this->makeProvider();
        $project = $this->makeProject($provider, 2);
        $talent = $this->makeTalent();
        $this->apply($talent, $project)->assertCreated();
        $this->cancel($talent, $project)->assertOk();
        $application = $this->applicationOf($project, $talent);

        foreach (['diterima', 'ditolak', 'dalam_review'] as $status) {
            $this->actingAs($provider)->patch("/penyedia/pelamar/{$application->id}/status", ['status' => $status])
                ->assertSessionHas('error', fn ($msg) => str_contains($msg, 'sudah membatalkan lamarannya'));
            $this->assertSame('dibatalkan', $application->fresh()->status);
        }

        $this->actingAs($provider)->get('/penyedia/pelamar?status=dibatalkan')
            ->assertOk()
            ->assertSee('Dibatalkan (1)')
            ->assertSee('Dibatalkan oleh talenta');
    }

    public function test_rejected_application_is_final_and_hides_accept_button(): void
    {
        $provider = $this->makeProvider();
        $project = $this->makeProject($provider, 2);
        $talent = $this->makeTalent();
        $this->apply($talent, $project)->assertCreated();
        $application = $this->applicationOf($project, $talent);

        $this->actingAs($provider)->patch("/penyedia/pelamar/{$application->id}/status", ['status' => 'ditolak'])
            ->assertSessionHas('info');
        $this->assertSame('ditolak', $application->fresh()->status);

        foreach (['diterima', 'dalam_review'] as $status) {
            $this->actingAs($provider)->patch("/penyedia/pelamar/{$application->id}/status", ['status' => $status])
                ->assertSessionHas('error', fn ($msg) => str_contains($msg, 'sudah ditolak'));
            $this->assertSame('ditolak', $application->fresh()->status);
        }

        $this->actingAs($provider)->get('/penyedia/pelamar?status=ditolak')
            ->assertOk()
            ->assertSee('Lamaran ditolak')
            ->assertDontSee('Ya, Terima Pelamar')   // tombol Terima tidak dirender
            ->assertDontSee('Ya, Tolak Lamaran');
    }

    public function test_lamaran_page_shows_cancel_button_and_remaining_quota(): void
    {
        $provider = $this->makeProvider();
        $talent = $this->makeTalent();
        [$pending, $accepted] = $this->appliedProjects($talent, 2, $provider);
        $this->actingAs($provider)->patch('/penyedia/pelamar/' . $this->applicationOf($accepted, $talent)->id . '/status', ['status' => 'diterima']);

        $response = $this->actingAs($talent)->get('/talent/lamaran')->assertOk();
        $response->assertSee('Sisa pembatalan lamaran:');
        $response->assertSee('3 dari 3');
        $response->assertSee(route('talent.lamar.batal', $pending->id), false);
        $response->assertDontSee(route('talent.lamar.batal', $accepted->id), false);

        $this->actingAs($talent)->patch("/talent/proyek/{$pending->id}/batal")
            ->assertRedirect('/talent/lamaran')
            ->assertSessionHas('success');

        $this->actingAs($talent)->get('/talent/lamaran?tab=Dibatalkan')
            ->assertOk()
            ->assertSee('2 dari 3')
            ->assertSee($pending->title);
    }
}
