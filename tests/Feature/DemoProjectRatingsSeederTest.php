<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectWorkCompletion;
use App\Models\Rating;
use App\Models\User;
use App\Services\Rating\RatingService;
use Database\Seeders\DemoProjectRatingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoProjectRatingsSeederTest extends TestCase
{
    use RefreshDatabase;

    private function demo(string $local): User
    {
        return User::where('email', "{$local}@" . DemoProjectRatingsSeeder::DOMAIN)->firstOrFail();
    }

    public function test_seeder_creates_valid_two_way_ratings_and_is_idempotent(): void
    {
        $this->seed(DemoProjectRatingsSeeder::class);
        $counts = [Rating::count(), ProjectWorkCompletion::count(), Project::count(), User::count()];
        $this->seed(DemoProjectRatingsSeeder::class);
        $this->assertSame($counts, [Rating::count(), ProjectWorkCompletion::count(), Project::count(), User::count()], 'Seeder tidak boleh menggandakan data.');

        $service = app(RatingService::class);
        $this->assertSame(4.7, $service->summaryFor($this->demo('talenta.a'), Rating::ROLE_TALENTA)['average']);
        $this->assertSame(4.4, $service->summaryFor($this->demo('talenta.b'), Rating::ROLE_TALENTA)['average']);
        $this->assertSame(4.8, $service->summaryFor($this->demo('talenta.c'), Rating::ROLE_TALENTA)['average']);
        $this->assertNull($service->summaryFor($this->demo('talenta.d'), Rating::ROLE_TALENTA)['average']);
        $this->assertSame(4.6, $service->summaryFor($this->demo('penyedia.a'), Rating::ROLE_PENYEDIA)['average']);
        $this->assertSame(4.3, $service->summaryFor($this->demo('penyedia.b'), Rating::ROLE_PENYEDIA)['average']);
        $this->assertSame(3.8, $service->summaryFor($this->demo('penyedia.c'), Rating::ROLE_PENYEDIA)['average']);

        // Kelayakan Sponsored Project dari data demo.
        $elig = app(\App\Services\PromotionEligibilityService::class);
        $this->assertTrue($elig->standing($this->demo('penyedia.a'))['eligible']);
        $this->assertTrue($elig->standing($this->demo('penyedia.b'))['eligible']);
        $this->assertFalse($elig->standing($this->demo('penyedia.c'))['eligible']);
        $this->assertSame(3, $elig->standing($this->demo('penyedia.c'))['count']);

        // Setiap rating terhubung ke keterlibatan nyata: pemberi & penerima adalah pihak dalam keterlibatan itu,
        // lamaran berstatus diterima, dan pekerjaan Selesai.
        foreach (Rating::with('completion.application')->get() as $r) {
            $c = $r->completion;
            $this->assertSame(ProjectWorkCompletion::STATUS_COMPLETED, $c->status);
            $this->assertSame('diterima', $c->application->status);
            $this->assertEqualsCanonicalizing([$c->talent_id, $c->provider_id], [$r->rater_id, $r->ratee_id]);
            $this->assertNotSame($r->rater_id, $r->ratee_id);
            // Nilai akhir = hasil perhitungan aspek (bukan angka bebas).
            $aspects = config($r->ratee_role === Rating::ROLE_TALENTA ? 'rating.talent_aspects' : 'rating.provider_aspects');
            $this->assertEquals(app(\App\Services\Rating\RatingCalculator::class)->weightedScore($aspects, $r->scores), (float) $r->final_score);
        }

        // Proyek tanpa bayaran → aspek pembayaran N/A.
        $volunteer = Rating::whereHas('project', fn ($q) => $q->where('title', 'like', '%Relawan%'))->where('ratee_role', Rating::ROLE_PENYEDIA)->firstOrFail();
        $this->assertNull($volunteer->scores['payment']);
    }

    public function test_demo_accounts_see_ratings_they_received_not_gave(): void
    {
        $this->seed(DemoProjectRatingsSeeder::class);
        $talentA = $this->demo('talenta.a');
        $providerA = $this->demo('penyedia.a');

        $this->actingAs($talentA)->get(route('talent.beranda'))->assertOk()
            ->assertSee('Rating Saya')->assertSee('4,7')->assertSee('Lihat Semua Penilaian')
            ->assertSee('Desainnya rapi dan konsisten') // ulasan DARI penyedia untuk Alya
            ->assertDontSee('Brief jelas sejak awal');   // ulasan yang Alya BERIKAN ke penyedia

        $this->actingAs($providerA)->get(route('penyedia.dashboard'))->assertOk()
            ->assertSee('Rating Penyedia')->assertSee('4,6')
            ->assertSee('Instruksi tugas di lokasi sangat jelas') // ulasan terbaru DARI Talenta
            ->assertDontSee('Melayani pengunjung booth');       // ulasan yang Penyedia BERIKAN

        $this->actingAs($this->demo('talenta.d'))->get(route('talent.beranda'))->assertOk()->assertSee('Belum Ada Penilaian');
    }

    public function test_engagement_groups_and_rating_eligibility(): void
    {
        $this->seed(DemoProjectRatingsSeeder::class);
        $providerA = $this->demo('penyedia.a');

        $response = $this->actingAs($providerA)->get(route('penyedia.engagements.index'))->assertOk()
            ->assertSee('Perlu Dinilai')->assertSee('Sudah Dinilai')->assertSee('Belum Memenuhi Syarat Penilaian');

        $groups = $response->viewData('groups');
        $this->assertCount(1, $groups['perlu']);   // Bima – Booth Bazar (selesai kemarin)
        $this->assertCount(5, $groups['sudah']);
        $this->assertCount(1, $groups['belum']);   // Dimas – masih berjalan

        $inProgress = $groups['belum']->first();
        $this->actingAs($providerA)->get(route('penyedia.engagements.rate', $inProgress))->assertRedirect();
        $this->actingAs($providerA)->post(route('penyedia.engagements.rate.store', $inProgress), [
            'scores' => ['quality' => 5, 'timeliness' => 5, 'communication' => 5, 'responsibility' => 5, 'teamwork' => 5],
        ])->assertSessionHasErrors('error');

        $toRate = $groups['perlu']->first();
        $this->actingAs($providerA)->get(route('penyedia.engagements.rate', $toRate))->assertOk()->assertSee('Kualitas pekerjaan');
    }

    public function test_seeder_refuses_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        (new DemoProjectRatingsSeeder())->run();
        $this->assertSame(0, User::where('email', 'like', '%@' . DemoProjectRatingsSeeder::DOMAIN)->count());
    }
}
