<?php

namespace Tests\Feature;

use App\Exceptions\PromotionException;
use App\Models\Project;
use App\Models\ProjectPromotion;
use App\Models\PromotionPackage;
use App\Models\PromotionTransaction;
use App\Models\Rating;
use App\Models\User;
use App\Services\PromotionEligibilityService;
use App\Services\PromotionService;
use App\Services\Rating\RatingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Concerns\BuildsProviderReputation;
use Tests\TestCase;

class PromotionEligibilityTest extends TestCase
{
    use RefreshDatabase, BuildsProviderReputation;

    private User $penyedia;
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        PromotionPackage::factory()->create();
        $this->penyedia = User::factory()->create(['role' => 'penyedia_proyek']);
        $this->project  = Project::factory()->create(['user_id' => $this->penyedia->id, 'talents_needed' => 3, 'quota' => 3]);
    }

    private function eligibility(): PromotionEligibilityService
    {
        return app(PromotionEligibilityService::class);
    }

    private function attemptCheckout(): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->penyedia)->post(route('penyedia.promotions.store', $this->project), ['agree' => 1]);
    }

    // ── Ambang rating & jumlah penilaian ────────────────────────────────────

    public function test_rating_45_with_3_reviews_can_promote(): void
    {
        $this->giveProviderRatings($this->penyedia, [4.5, 4.5, 4.5]);

        $this->actingAs($this->penyedia)->get(route('penyedia.promotions.checkout', $this->project))->assertOk();
        $promotion = ProjectPromotion::first();
        $this->assertNull($promotion);

        $this->attemptCheckout()->assertRedirect();
        $promotion = ProjectPromotion::firstOrFail();
        $this->assertSame(15000, $promotion->transaction->amount);
        $this->assertSame(PromotionTransaction::STATUS_PENDING, $promotion->transaction->status);
    }

    public function test_rating_exactly_40_with_3_reviews_can_promote(): void
    {
        $this->giveProviderRatings($this->penyedia, [4.0, 4.0, 4.0]);

        $this->assertTrue($this->eligibility()->standing($this->penyedia)['eligible']);
        $this->attemptCheckout()->assertSessionHasNoErrors();
        $this->assertDatabaseCount('promotion_transactions', 1);
    }

    public function test_rating_39_with_5_reviews_is_rejected(): void
    {
        $this->giveProviderRatings($this->penyedia, [3.9, 3.9, 3.9, 3.9, 3.9]);

        $this->actingAs($this->penyedia)->get(route('penyedia.promotions.checkout', $this->project))
            ->assertRedirect(route('penyedia.promotions.index'))
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'Rating usaha Anda saat ini 3,9/5') && str_contains($m, 'minimal 4,0'));
        $this->attemptCheckout()->assertSessionHasErrors('error');
        $this->assertDatabaseCount('promotion_transactions', 0);
    }

    public function test_rating_48_with_only_1_review_is_rejected(): void
    {
        $this->giveProviderRatings($this->penyedia, [4.8]);

        $standing = $this->eligibility()->standing($this->penyedia);
        $this->assertFalse($standing['eligible']);
        $this->assertTrue($standing['meets_rating']);
        $this->assertFalse($standing['meets_count']);
        $this->attemptCheckout()->assertSessionHasErrors('error');
        $this->assertDatabaseCount('promotion_transactions', 0);
    }

    public function test_provider_without_rating_is_rejected_and_sees_requirements(): void
    {
        $this->attemptCheckout()->assertSessionHasErrors('error');
        $this->assertDatabaseCount('promotion_transactions', 0);

        // Menu tetap tampil, tombol nonaktif, alasan & card kelayakan jelas.
        $this->actingAs($this->penyedia)->get(route('penyedia.promotions.index'))->assertOk()
            ->assertSee('Kelayakan Promosi Anda')
            ->assertSee('Belum Ada Penilaian')
            ->assertSee('Fitur promosi belum tersedia. Selesaikan proyek dan kumpulkan minimal 3 penilaian dari Talenta untuk membuka Sponsored Project.')
            ->assertSee('Lihat Penilaian Saya')
            ->assertSee('disabled', false)
            ->assertDontSee(route('penyedia.promotions.checkout', $this->project), false);
    }

    public function test_raw_average_is_used_not_rounded_display(): void
    {
        // (4,00 + 3,94 + 3,94) / 3 = 3,96 → tampil 4,0 tetapi belum memenuhi syarat.
        $this->giveProviderRatings($this->penyedia, [4.00, 3.94, 3.94]);

        $standing = $this->eligibility()->standing($this->penyedia);
        $this->assertSame(4.0, $standing['average']);
        $this->assertEqualsWithDelta(3.96, $standing['raw_average'], 0.0001);
        $this->assertFalse($standing['eligible']);
        $this->assertStringContainsString('nilai tepatnya 3,96', $standing['message']);
        $this->attemptCheckout()->assertSessionHasErrors('error');
    }

    public function test_eligible_page_shows_card_and_active_button(): void
    {
        $this->giveProviderRatings($this->penyedia, [4.6, 4.6, 4.6, 4.6, 4.6]);

        $this->actingAs($this->penyedia)->get(route('penyedia.promotions.index'))->assertOk()
            ->assertSee('Kelayakan Promosi Anda')->assertSee('4,6')->assertSee('Memenuhi Syarat')
            ->assertSee('Minimal Rating: 4,0/5')->assertSee('Minimal Penilaian: 3')
            ->assertSee(route('penyedia.promotions.checkout', $this->project), false);
    }

    // ── Hanya penilaian valid yang dihitung ─────────────────────────────────

    public function test_voided_and_unpublished_ratings_do_not_count(): void
    {
        $ratings = $this->giveProviderRatings($this->penyedia, [4.5, 4.5, 4.5]);
        $this->assertTrue($this->eligibility()->standing($this->penyedia)['eligible']);

        // Dibatalkan Admin karena pelanggaran → tidak dihitung.
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post(route('admin.ratings.void', $ratings[0]), ['reason' => 'Ulasan terbukti palsu'])->assertSessionHasNoErrors();
        $this->assertNotNull($ratings[0]->fresh()->voided_at);
        $this->assertSame(2, $this->eligibility()->standing($this->penyedia)['count']);
        $this->assertFalse($this->eligibility()->standing($this->penyedia)['eligible']);

        // Belum terpublikasi (periode penilaian masih berjalan, pihak lain belum menilai) → tidak dihitung.
        $ratings[1]->update(['published_at' => null]);
        $ratings[1]->completion->update(['rating_deadline_at' => now()->addDays(3)]);
        $this->assertSame(1, $this->eligibility()->standing($this->penyedia)['count']);

        // Dicabut pembatalannya → dihitung kembali.
        $this->actingAs($admin)->post(route('admin.ratings.unvoid', $ratings[0]), ['reason' => 'Banding diterima'])->assertSessionHasNoErrors();
        $this->assertSame(2, $this->eligibility()->standing($this->penyedia)['count']);
    }

    public function test_ratings_given_by_provider_do_not_count_as_received(): void
    {
        $this->giveProviderRatings($this->penyedia, [4.5, 4.5]);
        // Penilaian yang DIBERIKAN Penyedia ke Talenta tidak menambah reputasi Penyedia.
        $given = Rating::first()->replicate()->fill([
            'rater_id' => $this->penyedia->id, 'ratee_id' => Rating::first()->rater_id,
            'rater_role' => Rating::ROLE_PENYEDIA, 'ratee_role' => Rating::ROLE_TALENTA, 'final_score' => 5,
        ]);
        $given->save();

        $this->assertSame(2, app(RatingService::class)->summaryFor($this->penyedia, Rating::ROLE_PENYEDIA)['count']);
        $this->assertFalse($this->eligibility()->standing($this->penyedia)['eligible']);
    }

    // ── Otorisasi & bypass ──────────────────────────────────────────────────

    public function test_talenta_cannot_access_provider_checkout(): void
    {
        $this->giveProviderRatings($this->penyedia, [4.5, 4.5, 4.5]);
        $talenta = User::factory()->create(['role' => 'talenta_muda']);

        $this->actingAs($talenta)->get(route('penyedia.promotions.checkout', $this->project))->assertForbidden();
        $this->actingAs($talenta)->post(route('penyedia.promotions.store', $this->project), ['agree' => 1])->assertForbidden();
        $this->assertDatabaseCount('promotion_transactions', 0);

        $this->expectException(PromotionException::class);
        app(PromotionService::class)->checkout($this->project, $talenta);
    }

    public function test_provider_cannot_promote_another_providers_project(): void
    {
        $this->giveProviderRatings($this->penyedia, [4.5, 4.5, 4.5]);
        $other = User::factory()->create(['role' => 'penyedia_proyek']);
        $this->giveProviderRatings($other, [4.9, 4.9, 4.9]);
        $foreign = Project::factory()->create(['user_id' => $other->id]);

        $this->actingAs($this->penyedia)->get(route('penyedia.promotions.checkout', $foreign))->assertForbidden();
        $this->actingAs($this->penyedia)->post(route('penyedia.promotions.store', $foreign), ['agree' => 1])->assertForbidden();
        $this->assertSame('Proyek tidak dimiliki oleh akun ini.', $this->eligibility()->blockerFor($this->penyedia, $foreign));

        $this->expectException(PromotionException::class);
        app(PromotionService::class)->checkout($foreign, $this->penyedia);
    }

    public function test_checkout_cannot_bypass_backend_validation(): void
    {
        // Tanpa rating: request langsung (tanpa melalui tombol) & pemanggilan service tetap ditolak.
        $this->attemptCheckout()->assertSessionHasErrors('error');
        try {
            app(PromotionService::class)->checkout($this->project, $this->penyedia);
            $this->fail('Checkout seharusnya ditolak.');
        } catch (PromotionException $e) {
            $this->assertStringContainsString('Fitur promosi belum tersedia', $e->getMessage());
        }

        // Memenuhi syarat rating tetapi proyek tidak layak (kuota penuh / lewat batas pendaftaran).
        $this->giveProviderRatings($this->penyedia, [4.5, 4.5, 4.5]);
        $expired = Project::factory()->create(['user_id' => $this->penyedia->id, 'registration_deadline' => now()->subDays(2)->toDateString()]);
        $this->actingAs($this->penyedia)->post(route('penyedia.promotions.store', $expired), ['agree' => 1])->assertSessionHasErrors('error');
        $this->assertSame('Batas pendaftaran sudah lewat', $this->eligibility()->blockerFor($this->penyedia, $expired));

        // Harga tidak bisa dimanipulasi dari form.
        $this->actingAs($this->penyedia)->post(route('penyedia.promotions.store', $this->project), ['agree' => 1, 'amount' => 1, 'price' => 1]);
        $this->assertSame(15000, PromotionTransaction::firstOrFail()->amount);
        $this->assertDatabaseCount('promotion_transactions', 1);
    }

    // ── Rating sesuai akun yang login ───────────────────────────────────────

    public function test_rating_shown_matches_logged_in_account(): void
    {
        $this->giveProviderRatings($this->penyedia, [4.6, 4.6, 4.6]);
        $other = User::factory()->create(['role' => 'penyedia_proyek']);
        $this->giveProviderRatings($other, [3.2, 3.2, 3.2]);

        $this->actingAs($this->penyedia)->get(route('penyedia.dashboard'))->assertOk()->assertSee('4,6')->assertDontSee('3,2');
        $this->actingAs($other)->get(route('penyedia.dashboard'))->assertOk()->assertSee('3,2')->assertDontSee('4,6');
        $this->actingAs($this->penyedia)->get(route('penyedia.profile'))->assertOk()->assertSee('★ 4,6 / 5 (3 penilaian)', false);
        $this->actingAs($other)->get(route('penyedia.promotions.index'))->assertOk()
            ->assertSee('Rating usaha Anda saat ini 3,2/5')->assertSee('Belum Memenuhi Syarat');

        // Talenta melihat rating Penyedia di Cari Proyek.
        $talenta = User::factory()->create(['role' => 'talenta_muda']);
        \App\Models\TalentProfile::create(['user_id' => $talenta->id, 'category' => 'Mahasiswa Aktif', 'institution' => 'UI', 'major' => 'DKV', 'city' => 'Depok', 'verification_status' => 'verified', 'eligibility_status' => 'eligible']);
        $this->actingAs($talenta)->get(route('talent.cari'))->assertOk()->assertSee('4,6 / 5')->assertSee('(3 penilaian)');
    }

    public function test_applicant_list_shows_rating_received_by_talent(): void
    {
        $talentRatings = $this->giveProviderRatings($this->penyedia, [4.5]); // menghasilkan 1 talenta + riwayat
        $talent = User::find($talentRatings[0]->rater_id);

        // Penyedia lain menilai talenta ini 4,8.
        $rating = $talentRatings[0]->replicate()->fill([
            'rater_id' => $this->penyedia->id, 'ratee_id' => $talent->id,
            'rater_role' => Rating::ROLE_PENYEDIA, 'ratee_role' => Rating::ROLE_TALENTA, 'final_score' => 4.8,
        ]);
        $rating->save();

        \App\Models\ProjectApplication::create(['project_id' => $this->project->id, 'user_id' => $talent->id, 'status' => 'menunggu', 'cover_letter' => 'Halo']);
        $fresh = User::factory()->create(['role' => 'talenta_muda']);
        \App\Models\ProjectApplication::create(['project_id' => $this->project->id, 'user_id' => $fresh->id, 'status' => 'menunggu', 'cover_letter' => 'Halo']);

        $this->actingAs($this->penyedia)->get(route('penyedia.applicants.index'))->assertOk()
            ->assertSee('4,8 / 5')->assertSee('(1 penilaian)')   // rating yang DITERIMA talenta
            ->assertDontSee('4,5 / 5')                             // bukan rating yang ia berikan
            ->assertSee('Belum Ada Penilaian');                    // talenta baru
    }

    // ── Rating berubah di tengah proses ─────────────────────────────────────

    public function test_unpaid_checkout_cannot_continue_when_rating_drops(): void
    {
        $this->giveProviderRatings($this->penyedia, [4.2, 4.2, 4.2]);
        $promotion = app(PromotionService::class)->checkout($this->project, $this->penyedia);

        $this->giveProviderRatings($this->penyedia, [2.0, 2.0]); // rata-rata turun ke 3,32

        $this->actingAs($this->penyedia)->get(route('penyedia.promotions.payment', $promotion))->assertOk()
            ->assertSee('Pembayaran tidak dapat dilanjutkan')
            ->assertDontSee('Simulasikan Pembayaran Berhasil');

        $this->actingAs($this->penyedia)->post(route('penyedia.promotions.simulate.paid', $promotion));
        $this->assertSame(PromotionTransaction::STATUS_FAILED, $promotion->transaction->fresh()->status);
        $this->assertFalse($promotion->fresh()->isLive());
    }

    public function test_paid_transaction_is_flagged_for_admin_when_rating_drops(): void
    {
        config(['promotion.qris.merchant_name' => 'MERCHANT UJI', 'promotion.qris.image' => 'images/payment/test-qris.png']);
        @mkdir(public_path('images/payment'), 0777, true);
        \Illuminate\Support\Facades\Storage::fake('local');
        file_put_contents(public_path('images/payment/test-qris.png'), 'png');

        try {
            $this->giveProviderRatings($this->penyedia, [4.2, 4.2, 4.2]);
            $promotion = app(PromotionService::class)->checkout($this->project, $this->penyedia);
            $trx = $promotion->transaction;

            $this->actingAs($this->penyedia)->post(route('penyedia.promotions.proof.submit', $promotion), [
                'proof' => UploadedFile::fake()->create('bukti.pdf', 50, 'application/pdf'),
                'payer_amount' => 15000, 'payer_reference' => 'REF-ELIG-1',
            ])->assertSessionHasNoErrors();
            $this->assertSame(PromotionTransaction::STATUS_AWAITING_VERIFICATION, $trx->fresh()->status);

            // Rating turun setelah dana dikirim, sebelum Admin memverifikasi.
            $this->giveProviderRatings($this->penyedia, [2.0, 2.0]);
            $admin = User::factory()->create(['role' => 'admin']);

            $this->actingAs($admin)->get(route('admin.payments.show', $trx))->assertOk()->assertSee('Perlu penanganan Admin');
            $this->actingAs($admin)->post(route('admin.payments.approve', $trx), ['confirm_received' => 1])->assertSessionHasErrors('error');

            $trx->refresh();
            $this->assertSame(PromotionTransaction::STATUS_AWAITING_VERIFICATION, $trx->status, 'Dana/hak Penyedia tidak hilang otomatis.');
            $this->assertTrue($trx->needs_admin_attention);
            $this->assertFalse($promotion->fresh()->isLive());

            // Override wajib catatan, lalu promosi aktif sesuai keputusan Admin.
            $this->actingAs($admin)->post(route('admin.payments.approve', $trx), ['confirm_received' => 1, 'override_eligibility' => 1])->assertSessionHasErrors('error');
            $this->actingAs($admin)->post(route('admin.payments.approve', $trx), ['confirm_received' => 1, 'override_eligibility' => 1, 'note' => 'Kebijakan: dana sudah masuk sebelum rating turun'])
                ->assertRedirect(route('admin.payments.index'));
            $this->assertSame(PromotionTransaction::STATUS_PAID, $trx->fresh()->status);
            $this->assertFalse($trx->fresh()->needs_admin_attention);
            $this->assertTrue($promotion->fresh()->isLive());
        } finally {
            @unlink(public_path('images/payment/test-qris.png'));
        }
    }

    public function test_active_promotion_keeps_running_when_rating_drops_but_follows_moderation(): void
    {
        $this->giveProviderRatings($this->penyedia, [4.5, 4.5, 4.5]);
        $service = app(PromotionService::class);
        $promotion = $service->simulatePaid($service->checkout($this->project, $this->penyedia)->transaction, $this->penyedia);
        $this->assertTrue($promotion->isLive());
        $this->assertEqualsWithDelta(96 * 3600, $promotion->start_at->diffInSeconds($promotion->end_at), 1);

        $this->giveProviderRatings($this->penyedia, [1.0, 1.0, 1.0]);
        $this->artisan('promotions:maintain')->assertSuccessful();
        $this->assertTrue($promotion->fresh()->isLive(), 'Promosi berbayar tetap berjalan sampai akhir periode.');

        // Tidak bisa membeli promosi baru selama tidak memenuhi syarat.
        $another = Project::factory()->create(['user_id' => $this->penyedia->id]);
        $this->actingAs($this->penyedia)->post(route('penyedia.promotions.store', $another), ['agree' => 1])->assertSessionHasErrors('error');

        // Berakhir tepat setelah 96 jam.
        $this->travel(97)->hours();
        $this->artisan('promotions:maintain');
        $this->assertSame(ProjectPromotion::STATUS_ENDED, $promotion->fresh()->status);
    }

    public function test_active_promotion_stops_on_moderation_or_ineligible_project(): void
    {
        $this->giveProviderRatings($this->penyedia, [4.5, 4.5, 4.5]);
        $service = app(PromotionService::class);
        $promotion = $service->simulatePaid($service->checkout($this->project, $this->penyedia)->transaction, $this->penyedia);

        $admin = User::factory()->create(['role' => 'admin']);
        $service->stopByAdmin($promotion, $admin, 'Konten proyek melanggar ketentuan');
        $this->assertSame(ProjectPromotion::STATUS_STOPPED, $promotion->fresh()->status);

        $p2 = Project::factory()->create(['user_id' => $this->penyedia->id]);
        $promo2 = $service->simulatePaid($service->checkout($p2, $this->penyedia)->transaction, $this->penyedia);
        $p2->update(['status' => Project::STATUS_CLOSED]);
        $this->artisan('promotions:maintain');
        $this->assertSame(ProjectPromotion::STATUS_STOPPED, $promo2->fresh()->status);
    }
}
