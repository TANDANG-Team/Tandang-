<?php

namespace Tests\Feature;

use App\Exceptions\RatingException;
use App\Models\Project;
use App\Models\ProjectApplication;
use App\Models\ProjectWorkCompletion;
use App\Models\Rating;
use App\Models\RatingReport;
use App\Models\User;
use App\Services\Rating\RatingService;
use App\Services\Rating\WorkCompletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RatingTest extends TestCase
{
    use RefreshDatabase, \Tests\Concerns\BuildsProviderReputation;

    private User $admin;
    private User $penyedia;
    private User $talenta;
    private Project $project;
    private ProjectApplication $application;
    private WorkCompletionService $work;
    private RatingService $ratings;

    private array $talentScores = ['quality' => 5, 'timeliness' => 4, 'communication' => 5, 'responsibility' => 4, 'teamwork' => 5];
    private array $providerScores = ['accuracy' => 5, 'payment' => 4, 'professionalism' => 5, 'clarity' => 4, 'environment' => 4];

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin    = User::factory()->create(['role' => 'admin']);
        $this->penyedia = User::factory()->create(['role' => 'penyedia_proyek']);
        $this->talenta  = User::factory()->create(['role' => 'talenta_muda']);
        $this->project  = Project::factory()->create(['user_id' => $this->penyedia->id, 'compensation' => 'Rp500.000 / proyek']);

        // Lamaran diterima → keterlibatan proyek dibuat otomatis (status lamaran tetap "diterima").
        $this->application = ProjectApplication::create([
            'project_id' => $this->project->id, 'user_id' => $this->talenta->id, 'status' => 'diterima',
        ]);

        $this->work = app(WorkCompletionService::class);
        $this->ratings = app(RatingService::class);
    }

    private function completion(): ProjectWorkCompletion
    {
        return ProjectWorkCompletion::where('project_application_id', $this->application->id)->firstOrFail();
    }

    private function complete(): ProjectWorkCompletion
    {
        $this->work->request($this->completion(), $this->penyedia);

        return $this->work->confirm($this->completion(), $this->talenta);
    }

    public function test_accepting_application_creates_separate_work_status(): void
    {
        $c = $this->completion();

        $this->assertSame(ProjectWorkCompletion::STATUS_IN_PROGRESS, $c->status);
        $this->assertSame('diterima', $this->application->fresh()->status, 'Status lamaran tidak diubah.');
    }

    public function test_cannot_rate_before_work_completed(): void
    {
        $this->expectException(RatingException::class);
        $this->ratings->submit($this->completion(), $this->penyedia, $this->talentScores, 'Bagus');
    }

    public function test_cannot_rate_while_waiting_confirmation_or_disputed(): void
    {
        $this->work->request($this->completion(), $this->penyedia);
        $this->actingAs($this->penyedia)->get(route('penyedia.engagements.rate', $this->completion()))->assertRedirect();

        $this->work->dispute($this->completion(), $this->talenta, 'Pekerjaan revisi belum diserahkan');
        $this->assertSame(ProjectWorkCompletion::STATUS_DISPUTED, $this->completion()->status);

        $this->expectException(RatingException::class);
        $this->ratings->submit($this->completion(), $this->talenta, $this->providerScores, null);
    }

    public function test_requester_cannot_confirm_own_completion(): void
    {
        $this->work->request($this->completion(), $this->penyedia);

        $this->expectException(RatingException::class);
        $this->work->confirm($this->completion(), $this->penyedia);
    }

    public function test_only_involved_parties_can_rate(): void
    {
        $this->complete();
        $outsider = User::factory()->create(['role' => 'penyedia_proyek']);
        $applicantOnly = User::factory()->create(['role' => 'talenta_muda']);
        ProjectApplication::create(['project_id' => $this->project->id, 'user_id' => $applicantOnly->id, 'status' => 'menunggu']);

        $this->actingAs($outsider)->post(route('penyedia.engagements.rate.store', $this->completion()), ['scores' => $this->talentScores])->assertForbidden();
        $this->actingAs($applicantOnly)->post(route('talent.riwayat.rate.store', $this->completion()), ['scores' => $this->providerScores])->assertForbidden();
        $this->assertSame(0, Rating::count());
    }

    public function test_two_way_rating_with_hidden_until_both_rate(): void
    {
        $this->complete();

        $this->actingAs($this->penyedia)
            ->post(route('penyedia.engagements.rate.store', $this->completion()), ['scores' => $this->talentScores, 'review' => 'Kerja rapi dan tepat waktu.'])
            ->assertRedirect(route('penyedia.engagements.index'));

        $first = Rating::firstOrFail();
        $this->assertEquals(4.6, (float) $first->final_score);
        $this->assertNull($first->published_at);
        $this->assertSame(0, Rating::published()->count(), 'Belum terlihat sebelum pihak lain menilai.');
        $this->assertNull($this->ratings->summaryFor($this->talenta, Rating::ROLE_TALENTA)['average']);

        $this->actingAs($this->talenta)
            ->post(route('talent.riwayat.rate.store', $this->completion()), ['scores' => $this->providerScores, 'review' => 'Instruksi jelas.'])
            ->assertRedirect(route('talent.riwayat'));

        $this->assertSame(2, Rating::published()->count());
        $summary = $this->ratings->summaryFor($this->talenta, Rating::ROLE_TALENTA);
        $this->assertSame(4.6, $summary['average']);
        $this->assertSame(1, $summary['count']);
        $this->assertSame(1, $summary['completed']);
    }

    public function test_duplicate_rating_rejected(): void
    {
        $this->complete();
        $this->ratings->submit($this->completion(), $this->penyedia, $this->talentScores, null);

        $this->actingAs($this->penyedia)
            ->post(route('penyedia.engagements.rate.store', $this->completion()), ['scores' => $this->talentScores])
            ->assertSessionHasErrors('error');
        $this->assertSame(1, Rating::count());
    }

    public function test_rating_window_closes_after_7_days_and_publishes(): void
    {
        $this->complete();
        $this->ratings->submit($this->completion(), $this->penyedia, $this->talentScores, null);

        $this->travel(8)->days();

        // Dipublikasikan berdasarkan timestamp walau scheduler belum jalan.
        $this->assertSame(1, Rating::published()->count());
        $this->assertSame(RatingService::STATE_WINDOW_CLOSED, $this->ratings->stateFor($this->completion(), $this->talenta));

        try {
            $this->ratings->submit($this->completion(), $this->talenta, $this->providerScores, null);
            $this->fail('Rating setelah 7 hari harus ditolak.');
        } catch (RatingException) {
        }

        $this->artisan('ratings:maintain')->assertSuccessful();
        $this->assertNotNull(Rating::first()->published_at);
    }

    public function test_unpaid_project_marks_payment_aspect_not_applicable(): void
    {
        $this->project->update(['compensation' => 'Tanpa bayaran (sertifikat & portofolio)']);
        $this->complete();

        $aspects = $this->ratings->aspectsFor($this->completion(), $this->talenta);
        $this->assertTrue($aspects['payment']['not_applicable']);

        // Nilai pembayaran dari form diabaikan (N/A), bobot dinormalisasi.
        $rating = $this->ratings->submit($this->completion(), $this->talenta, ['payment' => 1] + $this->providerScores, null);
        $this->assertNull($rating->scores['payment']);
        // (5×30 + 5×20 + 4×15 + 4×10) / 75 = 350 / 75 = 4.67
        $this->assertEquals(4.67, (float) $rating->final_score);
    }

    public function test_auto_confirm_after_no_response(): void
    {
        $this->work->request($this->completion(), $this->penyedia);
        $this->travel(8)->days();

        $this->artisan('ratings:maintain')->assertSuccessful();
        $this->assertSame(ProjectWorkCompletion::STATUS_COMPLETED, $this->completion()->status);
    }

    public function test_admin_resolves_dispute(): void
    {
        $this->work->request($this->completion(), $this->penyedia);
        $this->work->dispute($this->completion(), $this->talenta, 'Belum menerima brief revisi akhir');

        $this->actingAs($this->penyedia)->post(route('admin.ratings.disputes.resolve', $this->completion()), ['decision' => 'completed', 'resolution_note' => 'x'])->assertRedirect(route('login'));

        $this->actingAs($this->admin)->post(route('admin.ratings.disputes.resolve', $this->completion()), [
            'decision' => 'completed', 'resolution_note' => 'Bukti serah terima lengkap.',
        ])->assertRedirect();

        $c = $this->completion();
        $this->assertSame(ProjectWorkCompletion::STATUS_COMPLETED, $c->status);
        $this->assertNotNull($c->rating_deadline_at);
        $this->assertSame($this->admin->id, $c->resolved_by);
    }

    public function test_admin_hides_review_without_changing_score_and_logs(): void
    {
        $this->complete();
        $rating = $this->ratings->submit($this->completion(), $this->penyedia, $this->talentScores, 'Ulasan berisi kata kasar');
        $this->ratings->submit($this->completion(), $this->talenta, $this->providerScores, null);

        $this->actingAs($this->talenta)->post(route('talent.ratings.report', $rating), ['type' => 'violation', 'reason' => 'Mengandung kata tidak pantas'])->assertSessionHasNoErrors();
        $this->assertSame(1, RatingReport::where('status', 'open')->count());

        $this->actingAs($this->admin)->post(route('admin.ratings.hide', $rating), ['reason' => 'Melanggar pedoman bahasa'])->assertRedirect();

        $rating->refresh();
        $this->assertTrue($rating->review_hidden);
        $this->assertEquals(4.6, (float) $rating->final_score);
        $this->assertNull($rating->visible_review);
        $this->assertSame(4.6, $this->ratings->summaryFor($this->talenta, Rating::ROLE_TALENTA)['average']);
        $this->assertDatabaseHas('rating_audit_logs', ['rating_id' => $rating->id, 'action' => 'review_hidden', 'user_id' => $this->admin->id]);

        $this->actingAs($this->admin)->get(route('admin.ratings.index'))->assertOk()->assertSee('Moderasi Rating');
        $this->actingAs($this->admin)->get(route('admin.ratings.index', ['tab' => 'log']))->assertOk()->assertSee('Ulasan disembunyikan');
        $this->actingAs($this->talenta)->get(route('admin.ratings.index'))->assertRedirect(route('login'));
    }

    public function test_reputation_pages_show_empty_state_not_zero(): void
    {
        $this->actingAs($this->talenta)->get(route('talent.profil'))->assertOk()->assertSee('Belum Ada Penilaian')->assertDontSee('0.0');
        $this->actingAs($this->talenta)->get(route('talent.providers.show', $this->penyedia))->assertOk()->assertSee('Belum Ada Penilaian');
        $this->actingAs($this->penyedia)->get(route('penyedia.talents.show', $this->talenta))->assertOk()->assertSee('Reputasi Talenta');

        $stranger = User::factory()->create(['role' => 'talenta_muda']);
        $this->actingAs($this->penyedia)->get(route('penyedia.talents.show', $stranger))->assertForbidden();
    }

    public function test_engagement_pages_render_with_actions(): void
    {
        $this->actingAs($this->penyedia)->get(route('penyedia.engagements.index'))->assertOk()->assertSee('Ajukan Selesai');
        $this->work->request($this->completion(), $this->penyedia);
        $this->actingAs($this->talenta)->get(route('talent.riwayat'))->assertOk()->assertSee('Konfirmasi Selesai')->assertSee('Ajukan sengketa');

        $this->actingAs($this->talenta)->post(route('talent.riwayat.confirm', $this->completion()))->assertSessionHasNoErrors();
        $this->actingAs($this->talenta)->get(route('talent.riwayat'))->assertSee('Beri Rating');
        $this->actingAs($this->talenta)->get(route('talent.riwayat.rate', $this->completion()))->assertOk()->assertSee('Ketepatan pembayaran/kompensasi');
    }

    public function test_buying_promotion_does_not_affect_rating(): void
    {
        $this->complete();
        $this->ratings->submit($this->completion(), $this->penyedia, $this->talentScores, null);
        $this->ratings->submit($this->completion(), $this->talenta, $this->providerScores, null);
        $before = $this->ratings->summaryFor($this->penyedia, Rating::ROLE_PENYEDIA);

        \App\Models\PromotionPackage::factory()->create();
        $this->giveProviderRatings($this->penyedia, [4.5, 4.5]); // syarat promosi: min 3 penilaian
        $p2 = Project::factory()->create(['user_id' => $this->penyedia->id]);
        $promo = app(\App\Services\PromotionService::class)->checkout($p2, $this->penyedia);
        app(\App\Services\PromotionService::class)->simulatePaid($promo->transaction, $this->penyedia);

        $this->assertSame($before['average'], $this->ratings->summaryFor($this->penyedia, Rating::ROLE_PENYEDIA)['average']);
    }
}
