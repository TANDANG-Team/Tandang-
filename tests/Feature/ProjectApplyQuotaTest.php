<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesApplyFixtures;
use Tests\TestCase;

/**
 * Integrasi HTTP aturan kuota Apply Proyek: endpoint lamar (form & JSON), endpoint status,
 * endpoint terima/tolak penyedia, dan tampilan tombol Apply.
 */
class ProjectApplyQuotaTest extends TestCase
{
    use CreatesApplyFixtures, RefreshDatabase;

    private function applyJson(User $talent, Project $project)
    {
        return $this->actingAs($talent)->postJson("/talent/proyek/{$project->id}/lamar", ['message' => 'Halo']);
    }

    private function decide(User $provider, Project $project, User $talent, string $status)
    {
        $application = $this->applicationOf($project, $talent);

        return $this->actingAs($provider)->patch("/penyedia/pelamar/{$application->id}/status", ['status' => $status]);
    }

    public function test_apply_up_to_2n_succeeds_and_next_returns_409_quota_full(): void
    {
        $project = $this->makeProject($this->makeProvider(), 3);
        $talents = $this->makeTalents(7);

        foreach (array_slice($talents, 0, 6) as $i => $talent) {
            $this->applyJson($talent, $project)
                ->assertCreated()
                ->assertJsonPath('apply_status.active_count', $i + 1)
                ->assertJsonPath('apply_status.max_applicants', 6);
        }

        $this->applyJson($talents[6], $project)
            ->assertStatus(409)
            ->assertJsonPath('reason', 'quota_full')
            ->assertJsonPath('apply_status.remaining_slots', 0);

        $this->assertNull($this->applicationOf($project, $talents[6]));
        $this->assertDatabaseMissing('applications', ['project_id' => $project->id, 'user_id' => $talents[6]->id]);
    }

    public function test_form_submit_when_quota_full_redirects_back_with_reason(): void
    {
        $project = $this->makeProject($this->makeProvider(), 1);
        [$a, $b, $c] = $this->makeTalents(3);
        $this->applyJson($a, $project)->assertCreated();
        $this->applyJson($b, $project)->assertCreated();

        $this->actingAs($c)
            ->from('/talent/proyek')
            ->post("/talent/proyek/{$project->id}/lamar", ['message' => 'Halo'])
            ->assertRedirect('/talent/proyek')
            ->assertSessionHas('warning', fn ($msg) => str_contains($msg, 'Kuota pelamar proyek ini sedang penuh (2/2)'));

        $this->assertNull($this->applicationOf($project, $c));
    }

    public function test_reject_by_provider_opens_slot_for_new_applicant(): void
    {
        $provider = $this->makeProvider();
        $project = $this->makeProject($provider, 3);
        $talents = $this->makeTalents(8);
        foreach (array_slice($talents, 0, 6) as $talent) {
            $this->applyJson($talent, $project)->assertCreated();
        }
        $this->applyJson($talents[6], $project)->assertStatus(409);

        $this->decide($provider, $project, $talents[0], 'ditolak')->assertSessionHas('info');
        $this->decide($provider, $project, $talents[1], 'ditolak')->assertSessionHas('info');

        $this->actingAs($talents[6])->getJson("/talent/proyek/{$project->id}/status-lamaran")
            ->assertOk()
            ->assertJson(['can_apply' => true, 'active_count' => 4, 'remaining_slots' => 2]);

        $this->applyJson($talents[6], $project)->assertCreated();
        $this->applyJson($talents[7], $project)->assertCreated();
    }

    public function test_all_rejected_frees_every_slot(): void
    {
        $provider = $this->makeProvider();
        $project = $this->makeProject($provider, 2);
        $talents = $this->makeTalents(5);
        foreach (array_slice($talents, 0, 4) as $talent) {
            $this->applyJson($talent, $project)->assertCreated();
        }
        $this->actingAs($talents[4])->getJson("/talent/proyek/{$project->id}/status-lamaran")
            ->assertJson(['can_apply' => false, 'reason' => 'quota_full']);

        foreach (array_slice($talents, 0, 4) as $talent) {
            $this->decide($provider, $project, $talent, 'ditolak');
        }

        $this->actingAs($talents[4])->getJson("/talent/proyek/{$project->id}/status-lamaran")
            ->assertJson(['can_apply' => true, 'active_count' => 0, 'remaining_slots' => 4]);
        $this->applyJson($talents[4], $project)->assertCreated();
    }

    public function test_rejected_talent_gets_403_while_other_talent_can_apply(): void
    {
        $provider = $this->makeProvider();
        $project = $this->makeProject($provider, 2);
        [$rejected, $other] = $this->makeTalents(2);

        $this->applyJson($rejected, $project)->assertCreated();
        $this->decide($provider, $project, $rejected, 'ditolak');

        $this->applyJson($rejected, $project)
            ->assertForbidden()
            ->assertJsonPath('reason', 'rejected');
        $this->assertSame('ditolak', $this->applicationOf($project, $rejected)->status);

        $this->actingAs($rejected)->getJson("/talent/proyek/{$project->id}/status-lamaran")
            ->assertJson(['can_apply' => false, 'reason' => 'rejected', 'application_status' => 'ditolak']);

        $this->applyJson($other, $project)->assertCreated();
    }

    public function test_accepting_until_talents_needed_closes_registration(): void
    {
        $provider = $this->makeProvider();
        $project = $this->makeProject($provider, 2);
        $talents = $this->makeTalents(4);
        foreach (array_slice($talents, 0, 3) as $talent) {
            $this->applyJson($talent, $project)->assertCreated();
        }

        $this->decide($provider, $project, $talents[0], 'diterima')->assertSessionHas('success');
        $this->decide($provider, $project, $talents[1], 'diterima')
            ->assertSessionHas('success', fn ($msg) => str_contains($msg, 'pendaftaran otomatis ditutup'));

        $this->applyJson($talents[3], $project)
            ->assertStatus(409)
            ->assertJsonPath('reason', 'registration_closed');
        $this->assertFalse(Project::openForTalent()->whereKey($project->id)->exists());

        // Asumsi default: lamaran yang masih menunggu dibiarkan & tetap bisa diproses penyedia.
        $this->assertSame('menunggu', $this->applicationOf($project, $talents[2])->status);
        $this->decide($provider, $project, $talents[2], 'diterima')->assertSessionHas('error');
        $this->decide($provider, $project, $talents[2], 'ditolak')->assertSessionHas('info');
        $this->assertSame('ditolak', $this->applicationOf($project, $talents[2])->status);
    }

    public function test_removing_accepted_talent_reopens_registration_per_existing_logic(): void
    {
        $provider = $this->makeProvider();
        $project = $this->makeProject($provider, 1);
        [$accepted, $newcomer] = $this->makeTalents(2);
        $this->applyJson($accepted, $project)->assertCreated();
        $this->decide($provider, $project, $accepted, 'diterima');
        $this->applyJson($newcomer, $project)->assertStatus(409)->assertJsonPath('reason', 'registration_closed');

        // Penyedia mengubah status talenta yang sudah diterima menjadi ditolak (dikeluarkan).
        $this->decide($provider, $project, $accepted, 'ditolak');

        $this->applyJson($newcomer, $project)->assertCreated();
        $this->applyJson($accepted, $project)->assertForbidden()->assertJsonPath('reason', 'rejected');
    }

    public function test_same_talent_applying_twice_gets_409_and_single_row(): void
    {
        $project = $this->makeProject($this->makeProvider(), 2);
        $talent = $this->makeTalent();

        $this->applyJson($talent, $project)->assertCreated();
        $this->applyJson($talent, $project)->assertStatus(409)->assertJsonPath('reason', 'already_applied');

        $this->assertSame(1, ProjectApplication::where('project_id', $project->id)->count());
    }

    public function test_unverified_talent_is_still_blocked_before_quota_checks(): void
    {
        $project = $this->makeProject($this->makeProvider(), 2);
        $talent = $this->makeTalent();
        $talent->talentProfile->update(['verification_status' => 'pending']);

        $this->applyJson($talent, $project)->assertForbidden()->assertJsonPath('reason', 'not_verified');
        $this->actingAs($talent)->post("/talent/proyek/{$project->id}/lamar")->assertRedirect('/talent/verifikasi');
    }

    public function test_catalog_shows_slots_and_disabled_reason(): void
    {
        $provider = $this->makeProvider();
        $full = $this->makeProject($provider, 1, ['title' => 'Proyek Penuh']);
        $open = $this->makeProject($provider, 3, ['title' => 'Proyek Terbuka']);
        $viewer = $this->makeTalent();
        foreach ($this->makeTalents(2) as $talent) {
            $this->applyJson($talent, $full)->assertCreated();
        }
        $this->applyJson($viewer, $open)->assertCreated();

        $response = $this->actingAs($viewer)->get('/talent/proyek')->assertOk();
        $response->assertSee('2/2 pelamar');
        $response->assertSee('Kuota pelamar penuh');
        $response->assertSee('1/6 pelamar');
        $response->assertSee('Sudah melamar');
        $response->assertViewHas('applyStatuses', fn ($statuses) => $statuses[$full->id]['reason'] === 'quota_full'
            && $statuses[$open->id]['reason'] === 'already_applied');

        $this->actingAs($viewer)->get('/talent/rekomendasi')->assertOk()->assertSee('2/2 pelamar');
        $this->actingAs($viewer)->get('/talent')->assertOk()->assertSee('Kuota pelamar penuh');
    }

    public function test_provider_project_list_shows_active_slots(): void
    {
        $provider = $this->makeProvider();
        $project = $this->makeProject($provider, 2);
        $talents = $this->makeTalents(3);
        foreach ($talents as $talent) {
            $this->applyJson($talent, $project)->assertCreated();
        }
        $this->decide($provider, $project, $talents[0], 'ditolak');

        $this->actingAs($provider)->get('/penyedia/proyek')->assertOk()->assertSee('2/4 slot aktif');
    }
}
