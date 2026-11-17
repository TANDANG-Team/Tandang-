<?php

namespace Tests\Feature;

use App\Exceptions\PromotionException;
use App\Models\Application;
use App\Models\Project;
use App\Models\ProjectApplication;
use App\Models\ProjectPromotion;
use App\Models\PromotionAttribution;
use App\Models\PromotionEvent;
use App\Models\PromotionPackage;
use App\Models\PromotionTransaction;
use App\Models\TalentProfile;
use App\Models\User;
use App\Services\PromotionService;
use App\Services\WorkstyleMatchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SponsoredProjectTest extends TestCase
{
    use RefreshDatabase, \Tests\Concerns\BuildsProviderReputation;

    private User $admin;
    private User $penyedia;
    private User $talenta;
    private Project $project;
    private PromotionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin    = User::factory()->create(['role' => 'admin']);
        $this->penyedia = User::factory()->create(['role' => 'penyedia_proyek']);
        $this->talenta  = User::factory()->create(['role' => 'talenta_muda']);

        $this->project = Project::factory()->create([
            'user_id'        => $this->penyedia->id,
            'title'          => 'Desain Menu Kafe Sponsor',
            'category'       => 'Desain Grafis',
            'talents_needed' => 3,
            'quota'          => 3,
        ]);

        PromotionPackage::factory()->create(); // Rp15.000 / 96 jam
        $this->giveProviderRatings($this->penyedia, [4.6, 4.5, 4.7]); // syarat Sponsored Project terpenuhi
        $this->service = app(PromotionService::class);
    }

    private function activate(?Project $project = null): ProjectPromotion
    {
        $project ??= $this->project;
        $promotion = $this->service->checkout($project, User::find($project->user_id));

        return $this->service->simulatePaid($promotion->transaction, User::find($project->user_id));
    }

    private function verifiedTalent(?User $user = null): User
    {
        $user ??= $this->talenta;
        TalentProfile::updateOrCreate(['user_id' => $user->id], [
            'category' => 'Mahasiswa Aktif', 'institution' => 'UI', 'major' => 'Ilkom', 'city' => 'Depok',
            'verification_status' => 'verified', 'eligibility_status' => 'eligible',
        ]);

        return $user;
    }

    // ── Akses & kepemilikan ─────────────────────────────────────────────────

    public function test_role_access_to_promotion_pages(): void
    {
        $this->get(route('penyedia.promotions.index'))->assertRedirect(route('login'));
        $this->actingAs($this->talenta)->get(route('penyedia.promotions.index'))->assertForbidden();
        $this->actingAs($this->talenta)->post(route('penyedia.promotions.store', $this->project), ['agree' => 1])->assertForbidden();
        $this->actingAs($this->penyedia)->get(route('admin.promotions.index'))->assertRedirect(route('login'));

        $this->actingAs($this->penyedia)->get(route('penyedia.promotions.index'))->assertOk()->assertSee('Promosi Proyek')->assertSee('Rp15.000');
        $this->actingAs($this->admin)->get(route('admin.promotions.index'))->assertOk()->assertSee('Manajemen Promosi');

        // Non-admin ber-email @tandang.id lolos middleware lama tapi ditolak Policy.
        $staff = User::factory()->create(['role' => 'penyedia_proyek', 'email' => 'mitra@tandang.id']);
        $this->actingAs($staff)->get(route('admin.promotions.index'))->assertForbidden();
    }

    public function test_penyedia_cannot_checkout_or_view_stats_of_other_provider(): void
    {
        $other = User::factory()->create(['role' => 'penyedia_proyek']);
        $this->giveProviderRatings($other, [4.5, 4.5, 4.5]);
        $otherProject = Project::factory()->create(['user_id' => $other->id]);

        $this->actingAs($this->penyedia)->get(route('penyedia.promotions.checkout', $otherProject))->assertForbidden();
        $this->actingAs($this->penyedia)->post(route('penyedia.promotions.store', $otherProject), ['agree' => 1])->assertForbidden();

        $promo = $this->activate($otherProject);
        $this->actingAs($this->penyedia)->get(route('penyedia.promotions.stats', $promo))->assertForbidden();
        $this->actingAs($this->penyedia)->get(route('penyedia.promotions.payment', $promo))->assertForbidden();
        $this->actingAs($this->penyedia)->post(route('penyedia.promotions.simulate.paid', $promo))->assertForbidden();
        $this->assertDatabaseCount('project_promotions', 1);
    }

    public function test_draft_closed_or_full_projects_cannot_be_promoted(): void
    {
        $draft  = Project::factory()->draft()->create(['user_id' => $this->penyedia->id]);
        $closed = Project::factory()->closed()->create(['user_id' => $this->penyedia->id]);
        $full   = Project::factory()->create(['user_id' => $this->penyedia->id, 'talents_needed' => 1, 'quota' => 1]);
        ProjectApplication::create(['project_id' => $full->id, 'user_id' => $this->talenta->id, 'status' => 'diterima']);

        foreach ([$draft, $closed, $full->fresh()] as $p) {
            $this->actingAs($this->penyedia)->post(route('penyedia.promotions.store', $p), ['agree' => 1])->assertSessionHasErrors('error');
        }
        $this->assertDatabaseCount('promotion_transactions', 0);

        $this->actingAs($this->penyedia)->get(route('penyedia.promotions.index'))
            ->assertSee('Masih draf')->assertSee('Proyek sudah ditutup')->assertSee('Kuota Talenta sudah terpenuhi');
    }

    // ── Checkout & pembayaran ───────────────────────────────────────────────

    private function submitProof(ProjectPromotion $promo, array $overrides = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs(User::find($promo->user_id))->post(route('penyedia.promotions.proof.submit', $promo), array_merge([
            'proof'           => \Illuminate\Http\UploadedFile::fake()->create('bukti.pdf', 80, 'application/pdf'),
            'payer_amount'    => 15000,
            'payer_reference' => 'REF' . $promo->id . 'X' . random_int(1000, 9999),
            'payer_name'      => 'Penyedia Uji',
        ], $overrides));
    }

    public function test_checkout_creates_pending_transaction_with_backend_price_and_5_minute_window(): void
    {
        $this->actingAs($this->penyedia)->get(route('penyedia.promotions.checkout', $this->project))
            ->assertOk()->assertSee('Sponsored Project')->assertSee('Rp15.000')->assertSee('bukan jaminan');

        $this->actingAs($this->penyedia)->post(route('penyedia.promotions.store', $this->project), [
            'agree' => 1, 'price' => 1, 'amount' => 1, 'duration_hours' => 9999, 'status' => 'paid',
        ])->assertRedirect();

        $promo = ProjectPromotion::firstOrFail();
        $trx = $promo->transaction;

        $this->assertSame(ProjectPromotion::STATUS_PENDING_PAYMENT, $promo->status);
        $this->assertSame(15000, $promo->price);
        $this->assertSame(96, $promo->duration_hours);
        $this->assertSame(PromotionTransaction::STATUS_PENDING, $trx->status);
        $this->assertSame(15000, $trx->amount);
        $this->assertSame('qris_manual', $trx->payment_method);
        $this->assertFalse($trx->is_simulation);
        $this->assertSame(300, (int) $trx->created_at->diffInSeconds($trx->expires_at));
        $this->assertNull($promo->start_at);
        $this->assertFalse(ProjectPromotion::forTalent()->exists());
    }

    public function test_payment_page_layout_and_unconfigured_qris_warning(): void
    {
        $promo = $this->service->checkout($this->project, $this->penyedia);

        $this->actingAs($this->penyedia)->get(route('penyedia.promotions.payment', $promo))
            ->assertOk()
            ->assertSee('Cara Pembayaran')
            ->assertSee('Pilih menu')
            ->assertSee('Selesaikan pembayaran dalam')
            ->assertSee('QRIS merchant belum dikonfigurasi')
            ->assertSee('Hanya untuk Pengujian')
            ->assertDontSee('Saya Sudah Bayar — Kirim Bukti'); // pembayaran nyata ditutup bila QRIS belum dikonfigurasi
    }

    public function test_payment_page_shows_configured_qris_and_merchant(): void
    {
        $this->configureQris();
        $promo = $this->service->checkout($this->project, $this->penyedia);

        $this->actingAs($this->penyedia)->get(route('penyedia.promotions.payment', $promo))
            ->assertOk()->assertSee('Merchant Uji Sah')->assertSee('Periksa penerima sebelum membayar')->assertSee('Saya Sudah Bayar');
    }

    public function test_refresh_does_not_reset_timer(): void
    {
        $promo = $this->service->checkout($this->project, $this->penyedia);
        $this->travel(120)->seconds();

        $response = $this->actingAs($this->penyedia)->get(route('penyedia.promotions.payment', $promo))->assertOk();
        $this->assertSame(180, $response->viewData('remainingSeconds'));
        $response->assertSee('03:00');

        $this->travel(60)->seconds();
        $this->assertSame(120, $this->actingAs($this->penyedia)->get(route('penyedia.promotions.payment', $promo))->viewData('remainingSeconds'));
        $this->assertSame(1, PromotionTransaction::count(), 'Refresh tidak membuat transaksi baru.');
    }

    public function test_simulated_payment_activates_for_exactly_96_hours_and_is_not_revenue(): void
    {
        $this->travelTo(now()->setDateTime(2026, 11, 17, 6, 0, 0)); // 13.00 WIB
        $promo = $this->service->checkout($this->project, $this->penyedia);

        $this->actingAs($this->penyedia)->post(route('penyedia.promotions.simulate.paid', $promo))
            ->assertRedirect(route('penyedia.promotions.payment', $promo));
        $this->actingAs($this->penyedia)->get(route('penyedia.promotions.payment', $promo))
            ->assertOk()->assertSee('Promosi Berhasil Diaktifkan')->assertSee('Lihat Statistik Promosi')->assertSee('Transaksi simulasi');

        $promo->refresh();
        $this->assertSame(PromotionTransaction::STATUS_PAID, $promo->transaction->status);
        $this->assertTrue($promo->transaction->is_simulation);
        $this->assertSame(ProjectPromotion::STATUS_ACTIVE, $promo->status);
        $this->assertSame(96, (int) $promo->start_at->diffInHours($promo->end_at));
        $this->assertSame('21 Nov 2026, 13.00 WIB', \App\Support\Wib::format($promo->end_at));
        $this->assertSame(0, (int) PromotionTransaction::realRevenue()->sum('amount'), 'Simulasi tidak dihitung sebagai pendapatan.');
    }

    public function test_activation_is_idempotent(): void
    {
        $promo = $this->activate();
        $end = $promo->end_at;

        $this->travel(3)->hours();
        $again = $this->service->simulatePaid($promo->transaction, $this->penyedia);

        $this->assertTrue($end->equalTo($again->end_at));
        $this->assertSame(1, $promo->auditLogs()->where('action', 'activated')->count());

        $this->expectException(PromotionException::class);
        $this->service->checkout($this->project, $this->penyedia);
    }

    public function test_simulated_failure_does_not_activate(): void
    {
        $promo = $this->service->checkout($this->project, $this->penyedia);
        $this->actingAs($this->penyedia)->post(route('penyedia.promotions.simulate.failed', $promo))->assertRedirect();

        $promo->refresh();
        $this->assertSame(PromotionTransaction::STATUS_FAILED, $promo->transaction->status);
        $this->assertSame(ProjectPromotion::STATUS_CANCELLED, $promo->status);
        $this->actingAs($this->penyedia)->get(route('penyedia.promotions.payment', $promo))->assertSee('Pembayaran Gagal')->assertSee('Buat Pembayaran Baru');

        $this->expectException(PromotionException::class);
        $this->service->simulatePaid($promo->transaction, $this->penyedia);
    }

    public function test_expired_checkout_cannot_be_activated(): void
    {
        $promo = $this->service->checkout($this->project, $this->penyedia);
        $this->travel(301)->seconds();

        // Manipulasi JS tidak berpengaruh: server memakai waktu server.
        $this->actingAs($this->penyedia)->post(route('penyedia.promotions.simulate.paid', $promo))->assertSessionHasErrors('error');

        $this->assertSame(PromotionTransaction::STATUS_EXPIRED, $promo->transaction->fresh()->status);
        $this->assertSame(ProjectPromotion::STATUS_CANCELLED, $promo->fresh()->status);
        $this->actingAs($this->penyedia)->get(route('penyedia.promotions.payment', $promo))->assertSee('Waktu Pembayaran Habis')->assertSee('Buat Pembayaran Baru');
        $this->assertNull($this->project->fresh()->promotionBlocker(), 'Boleh membuat checkout baru.');
    }

    public function test_payer_paid_at_is_read_as_wib_and_stored_as_utc(): void
    {
        $this->configureQris();
        \Illuminate\Support\Facades\Storage::fake('local');
        $this->travelTo(\Carbon\Carbon::parse('2026-11-17 14:50:00', 'UTC')); // 21.50 WIB

        $promo = $this->service->checkout($this->project, $this->penyedia);

        // Waktu yang benar-benar di depan (22.30 WIB) tetap ditolak.
        $this->submitProof($promo, ['payer_paid_at' => '2026-11-17T22:30'])->assertSessionHasErrors('payer_paid_at');

        // Jam transfer 21.48 WIB (datetime-local, tanpa zona waktu) bukan "masa depan".
        $this->submitProof($promo, ['payer_paid_at' => '2026-11-17T21:48'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('penyedia.promotions.payment', $promo));
        $this->assertSame('2026-11-17 14:48:00', $promo->transaction->fresh()->payer_paid_at->utc()->format('Y-m-d H:i:s'));
    }

    public function test_manual_qris_flow_requires_admin_approval(): void
    {
        $this->configureQris();
        \Illuminate\Support\Facades\Storage::fake('local');
        $promo = $this->service->checkout($this->project, $this->penyedia);

        $this->submitProof($promo, ['payer_reference' => 'QR123456'])->assertRedirect(route('penyedia.promotions.payment', $promo));

        $trx = $promo->transaction->fresh();
        $this->assertSame(PromotionTransaction::STATUS_AWAITING_VERIFICATION, $trx->status);
        \Illuminate\Support\Facades\Storage::disk('local')->assertExists($trx->proof_path);
        $this->assertStringStartsWith('payments/proofs/', $trx->proof_path);
        $this->assertFalse(ProjectPromotion::forTalent()->exists(), 'Bukti saja tidak mengaktifkan promosi.');

        // Timer berhenti setelah konfirmasi: lewat 5 menit tetap Menunggu Verifikasi.
        $this->travel(10)->minutes();
        $this->artisan('promotions:maintain');
        $this->assertSame(PromotionTransaction::STATUS_AWAITING_VERIFICATION, $trx->fresh()->status);
        $this->actingAs($this->penyedia)->get(route('penyedia.promotions.payment', $promo))->assertSee('Menunggu Verifikasi Admin');

        // Akses bukti: pemilik & admin saja.
        $other = User::factory()->create(['role' => 'penyedia_proyek']);
        $this->actingAs($other)->get(route('penyedia.promotions.proof', $promo))->assertForbidden();
        $this->actingAs($this->penyedia)->get(route('penyedia.promotions.proof', $promo))->assertOk();
        $this->actingAs($this->admin)->get(route('admin.payments.proof', $trx))->assertOk();
        $this->actingAs($this->penyedia)->post(route('admin.payments.approve', $trx), ['confirm_received' => 1])->assertRedirect(route('login'));

        // Admin wajib mengonfirmasi dana diterima.
        $this->actingAs($this->admin)->get(route('admin.payments.index'))->assertOk()->assertSee($trx->transaction_number);
        $this->actingAs($this->admin)->post(route('admin.payments.approve', $trx), [])->assertSessionHasErrors('confirm_received');
        $this->actingAs($this->admin)->post(route('admin.payments.approve', $trx), ['confirm_received' => 1, 'note' => 'Mutasi 14.02'])
            ->assertRedirect(route('admin.payments.index'));

        $promo->refresh();
        $trx->refresh();
        $this->assertSame(PromotionTransaction::STATUS_PAID, $trx->status);
        $this->assertFalse($trx->is_simulation);
        $this->assertSame($this->admin->id, $trx->verified_by);
        $this->assertSame(ProjectPromotion::STATUS_ACTIVE, $promo->status);
        $this->assertSame(96, (int) $promo->start_at->diffInHours($promo->end_at));
        $this->assertSame(15000, (int) PromotionTransaction::realRevenue()->sum('amount'));
        $this->assertDatabaseHas('promotion_audit_logs', ['promotion_transaction_id' => $trx->id, 'action' => 'payment_approved', 'user_id' => $this->admin->id]);
        $this->assertDatabaseHas('provider_notifications', ['user_id' => $this->penyedia->id, 'type' => 'promotion']);

        // Persetujuan ulang tidak mengaktifkan dua kali.
        $this->service->approveManual($trx, $this->admin);
        $this->assertSame(1, $promo->auditLogs()->where('action', 'activated')->count());
    }

    public function test_admin_rejection_requires_reason_and_does_not_activate(): void
    {
        $this->configureQris();
        \Illuminate\Support\Facades\Storage::fake('local');
        $promo = $this->service->checkout($this->project, $this->penyedia);
        $this->submitProof($promo);
        $trx = $promo->transaction->fresh();

        $this->actingAs($this->admin)->post(route('admin.payments.reject', $trx), ['reason' => ''])->assertSessionHasErrors('reason');
        $this->actingAs($this->admin)->post(route('admin.payments.reject', $trx), ['reason' => 'Dana tidak ada di mutasi'])->assertRedirect();

        $this->assertSame(PromotionTransaction::STATUS_REJECTED, $trx->fresh()->status);
        $this->assertSame(ProjectPromotion::STATUS_CANCELLED, $promo->fresh()->status);
        $this->assertFalse(ProjectPromotion::forTalent()->exists());
        $this->actingAs($this->penyedia)->get(route('penyedia.promotions.payment', $promo))->assertSee('Pembayaran Ditolak')->assertSee('Dana tidak ada di mutasi');

        $this->expectException(PromotionException::class);
        $this->service->approveManual($trx->fresh(), $this->admin);
    }

    public function test_same_proof_or_reference_cannot_be_reused(): void
    {
        $this->configureQris();
        \Illuminate\Support\Facades\Storage::fake('local');
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('bukti.pdf', '%PDF-1.4 bukti-sama');

        $promo1 = $this->service->checkout($this->project, $this->penyedia);
        $this->submitProof($promo1, ['proof' => $file, 'payer_reference' => 'SAMA-001'])->assertSessionHasNoErrors();

        $p2 = Project::factory()->create(['user_id' => $this->penyedia->id]);
        $promo2 = $this->service->checkout($p2, $this->penyedia);
        $this->submitProof($promo2, ['payer_reference' => 'SAMA-001'])->assertSessionHasErrors('error');
        $this->submitProof($promo2, ['proof' => \Illuminate\Http\UploadedFile::fake()->createWithContent('lagi.pdf', '%PDF-1.4 bukti-sama'), 'payer_reference' => 'BEDA-002'])->assertSessionHasErrors('error');

        $this->assertSame(PromotionTransaction::STATUS_PENDING, $promo2->transaction->fresh()->status);
    }

    public function test_late_confirmation_is_reviewed_not_auto_activated(): void
    {
        $this->configureQris();
        \Illuminate\Support\Facades\Storage::fake('local');
        $promo = $this->service->checkout($this->project, $this->penyedia);
        $this->travel(6)->minutes();

        $this->submitProof($promo)->assertSessionHasNoErrors();
        $trx = $promo->transaction->fresh();

        $this->assertSame(PromotionTransaction::STATUS_EXPIRED, $trx->status);
        $this->assertTrue($trx->late_submission);
        $this->assertTrue($trx->needsReview());
        $this->assertFalse(ProjectPromotion::forTalent()->exists());
        $this->assertSame('Sedang dipromosikan / pembayaran sedang diproses', $this->project->fresh()->promotionBlocker());

        // Admin boleh menyetujui bila dana memang diterima.
        $this->service->approveManual($trx, $this->admin, 'Dana masuk 14.07');
        $this->assertTrue(ProjectPromotion::forTalent()->whereKey($promo->id)->exists());
    }

    public function test_proof_validation(): void
    {
        $this->configureQris();
        \Illuminate\Support\Facades\Storage::fake('local');
        $promo = $this->service->checkout($this->project, $this->penyedia);

        $this->submitProof($promo, ['proof' => \Illuminate\Http\UploadedFile::fake()->create('virus.exe', 10)])->assertSessionHasErrors('proof');
        $this->submitProof($promo, ['proof' => \Illuminate\Http\UploadedFile::fake()->create('besar.pdf', 3000, 'application/pdf')])->assertSessionHasErrors('proof');
        $this->assertSame(PromotionTransaction::STATUS_PENDING, $promo->transaction->fresh()->status);
    }

    public function test_simulation_disabled_in_production(): void
    {
        config(['promotion.simulation_enabled' => false]);
        $promo = $this->service->checkout($this->project, $this->penyedia);

        $this->actingAs($this->penyedia)->post(route('penyedia.promotions.simulate.paid', $promo))->assertNotFound();
        $this->actingAs($this->penyedia)->get(route('penyedia.promotions.payment', $promo))->assertOk()->assertDontSee('Simulasikan Pembayaran Berhasil');
        $this->assertSame(PromotionTransaction::STATUS_PENDING, $promo->transaction->fresh()->status);
    }

    public function test_manual_gateway_cannot_auto_confirm(): void
    {
        $promo = $this->service->checkout($this->project, $this->penyedia);

        $this->expectException(PromotionException::class);
        $this->service->markPaid($promo->transaction, $this->admin, 'APAPUN');
    }

    private function configureQris(): void
    {
        $dir = public_path('images/payment');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $file = 'images/payment/qris-test-' . getmypid() . '.png';
        file_put_contents(public_path($file), base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='));
        $this->beforeApplicationDestroyed(fn () => @unlink(public_path($file)));

        config(['promotion.qris.image' => $file, 'promotion.qris.merchant_name' => 'Merchant Uji Sah']);
    }


    // ── Masa tayang & kelayakan ─────────────────────────────────────────────

    public function test_promotion_ends_by_timestamp_and_scheduler(): void
    {
        $promo = $this->activate();
        $this->travel(97)->hours();

        // Terdeteksi walau scheduler belum jalan.
        $this->assertFalse(ProjectPromotion::forTalent()->whereKey($promo->id)->exists());
        $this->assertSame('Berakhir', $promo->fresh()->status_label);

        $this->artisan('promotions:maintain')->assertSuccessful();
        $this->assertSame(ProjectPromotion::STATUS_ENDED, $promo->fresh()->status);

        // Boleh membeli promosi baru setelah berakhir; statistik lama tetap ada.
        $this->assertNull($this->project->fresh()->promotionBlocker());
    }

    public function test_closing_or_filling_project_stops_promotion(): void
    {
        $promo = $this->activate();
        $this->project->update(['status' => 'closed']);

        $promo->refresh();
        $this->assertSame(ProjectPromotion::STATUS_STOPPED, $promo->status);
        $this->assertNotNull($promo->remaining_seconds_at_stop);
        $this->assertGreaterThan(0, $promo->remaining_seconds_at_stop);

        $p2 = Project::factory()->create(['user_id' => $this->penyedia->id, 'talents_needed' => 1, 'quota' => 1]);
        $promo2 = $this->activate($p2);
        ProjectApplication::create(['project_id' => $p2->id, 'user_id' => $this->talenta->id, 'status' => 'diterima']);

        $this->assertSame(ProjectPromotion::STATUS_STOPPED, $promo2->fresh()->status);
        $this->assertFalse(ProjectPromotion::forTalent()->exists());
    }

    public function test_admin_can_stop_with_reason_but_cannot_change_payment(): void
    {
        $promo = $this->activate();

        $this->actingAs($this->admin)->post(route('admin.promotions.stop', $promo), ['stop_reason' => ''])->assertSessionHasErrors('stop_reason');
        $this->actingAs($this->admin)->post(route('admin.promotions.stop', $promo), ['stop_reason' => 'Melanggar kebijakan konten'])
            ->assertRedirect(route('admin.promotions.show', $promo));

        $promo->refresh();
        $this->assertSame(ProjectPromotion::STATUS_STOPPED, $promo->status);
        $this->assertSame(PromotionTransaction::STATUS_PAID, $promo->transaction->status);
        $this->assertTrue($promo->auditLogs()->where('action', 'stopped_admin')->where('user_id', $this->admin->id)->exists());

        $this->actingAs($this->admin)->get(route('admin.promotions.index', ['tab' => 'transaksi']))->assertOk()->assertSee($promo->transaction->transaction_number);
        $this->actingAs($this->admin)->get(route('admin.promotions.show', $promo))->assertOk()->assertSee('Melanggar kebijakan konten');
    }

    // ── Tampilan Cari Proyek ────────────────────────────────────────────────

    public function test_featured_card_shown_once_full_width_and_regular_projects_remain(): void
    {
        $regular = Project::factory()->create(['title' => 'Proyek Reguler Tanpa Promosi']);
        $promo = $this->activate();

        $response = $this->actingAs($this->talenta)->get(route('talent.cari'))->assertOk();

        $response->assertSee('sponsored-feature', false)
            ->assertSee('Dipromosikan')
            ->assertSee('Lihat Detail Proyek')
            ->assertSee('data-promo-id="' . $promo->id . '"', false)
            ->assertSee($regular->title);

        $this->assertSame($this->project->id, $response->viewData('featuredProject')->id);
        // Proyek yang sama tidak muncul dua kali (dikeluarkan dari grid).
        $this->assertFalse($response->viewData('projects')->contains('id', $this->project->id));
        $this->assertTrue($response->viewData('projects')->contains('id', $regular->id));
    }

    public function test_featured_card_also_shown_on_beranda_and_rekomendasi_with_tracking(): void
    {
        $regular = Project::factory()->create(['title' => 'Proyek Reguler Tanpa Promosi']);
        $promo = $this->activate();

        foreach (['talent.beranda', 'talent.rekomendasi'] as $route) {
            $response = $this->actingAs($this->talenta)->get(route($route))->assertOk();

            $response->assertSee('sponsored-feature', false)
                ->assertSee('data-promo-id="' . $promo->id . '"', false)
                ->assertSee('data-placement="banner"', false)
                ->assertSee('/talent/promosi', false)       // skrip tracking impression & klik
                ->assertSee($regular->title);

            $this->assertSame($this->project->id, $response->viewData('featuredProject')->id, $route);
            $list = $route === 'talent.beranda' ? 'topRecommendations' : 'projects';
            $this->assertFalse($response->viewData($list)->contains('id', $this->project->id), "{$route}: tidak tampil dua kali");
        }
    }

    public function test_no_featured_card_on_beranda_without_live_promotion(): void
    {
        Project::factory()->create(['title' => 'Proyek Reguler Tanpa Promosi']);

        $response = $this->actingAs($this->talenta)->get(route('talent.beranda'))->assertOk();
        $this->assertNull($response->viewData('featuredProject'));
        $response->assertDontSee('sponsored-feature"', false);
    }

    public function test_every_project_card_shows_provider_rating_or_unavailable_notice(): void
    {
        $unrated = User::factory()->create(['role' => 'penyedia_proyek']);
        Project::factory()->create(['user_id' => $unrated->id, 'title' => 'Proyek Penyedia Baru']);

        foreach (['talent.beranda', 'talent.rekomendasi', 'talent.cari'] as $route) {
            $this->actingAs($this->talenta)->get(route($route))->assertOk()
                ->assertSee('4,6 / 5')                    // rata-rata 4,6 dari 3 penilaian (proyek tanpa promosi)
                ->assertSee('(3 penilaian)')
                ->assertSee('Rating tidak tersedia');      // penyedia tanpa rating valid, bukan 0,0
        }
    }

    public function test_sponsored_respects_search_filter_and_hides_when_none(): void
    {
        $this->activate();
        Project::factory()->create(['title' => 'Fotografer Event', 'category' => 'Fotografi & Videografi']);

        $response = $this->actingAs($this->talenta)->get(route('talent.cari', ['q' => 'Fotografer']))->assertOk();
        $this->assertNull($response->viewData('featuredProject'));
        $response->assertDontSee('sponsored-feature"', false);
    }

    public function test_sponsorship_does_not_change_match_score(): void
    {
        $profile = $this->verifiedTalent()->talentProfile;
        $matcher = app(WorkstyleMatchingService::class);

        $before = $matcher->calculateMatch($profile, $this->project->fresh());
        $this->activate();
        $after = $matcher->calculateMatch($profile, $this->project->fresh());

        $this->assertSame($before['score'], $after['score']);
    }

    // ── Tracking & atribusi ─────────────────────────────────────────────────

    public function test_impressions_are_deduplicated_per_page_load(): void
    {
        $promo = $this->activate();
        $token = str_repeat('a', 24);

        $this->actingAs($this->talenta)->postJson(route('talent.promo.impression', $promo), ['page_token' => $token, 'placement' => 'banner'])->assertJson(['recorded' => true]);
        $this->actingAs($this->talenta)->postJson(route('talent.promo.impression', $promo), ['page_token' => $token, 'placement' => 'banner'])->assertJson(['recorded' => false]);
        $this->actingAs($this->talenta)->postJson(route('talent.promo.impression', $promo), ['page_token' => str_repeat('b', 24)])->assertJson(['recorded' => true]);

        // Pemilik proyek & penyedia tidak dihitung; token invalid ditolak.
        $this->actingAs($this->penyedia)->postJson(route('talent.promo.impression', $promo), ['page_token' => $token])->assertJson(['recorded' => false]);
        $this->actingAs($this->talenta)->postJson(route('talent.promo.impression', $promo), ['page_token' => 'x'])->assertStatus(422);

        $this->assertSame(2, PromotionEvent::where('type', 'impression')->count());
        $this->assertFalse(PromotionEvent::where('visitor_hash', (string) $this->talenta->id)->exists(), 'Identitas harus disamarkan.');
    }

    public function test_clicks_deduplicated_and_application_attributed_within_window(): void
    {
        $promo = $this->activate();
        $talent = $this->verifiedTalent();

        $this->actingAs($talent)->postJson(route('talent.promo.click', $promo), ['placement' => 'banner'])->assertJson(['recorded' => true]);
        $this->actingAs($talent)->postJson(route('talent.promo.click', $promo), ['placement' => 'banner'])->assertJson(['recorded' => false]);
        $this->assertSame(1, PromotionEvent::where('type', 'click')->count());

        $this->actingAs($talent)->post(route('talent.lamar', $this->project->id), ['message' => 'Halo'])->assertRedirect(route('talent.lamaran'));

        $application = ProjectApplication::where('user_id', $talent->id)->firstOrFail();
        $this->assertDatabaseHas('promotion_attributions', ['project_promotion_id' => $promo->id, 'project_application_id' => $application->id]);

        $stats = app(\App\Services\PromotionStatsService::class)->forPromotion($promo);
        $this->assertSame(1, $stats['clicks']);
        $this->assertSame(1, $stats['applications']);
        $this->assertSame(0.0, $stats['ctr']); // impressions 0 → CTR 0%, tanpa error
    }

    public function test_regular_application_is_not_attributed(): void
    {
        $this->activate();
        $talent = $this->verifiedTalent();

        $this->actingAs($talent)->post(route('talent.lamar', $this->project->id), ['message' => 'Halo']);
        $this->assertSame(0, PromotionAttribution::count());
        $this->assertSame(1, Application::count(), 'Alur apply existing tetap berjalan.');
    }

    public function test_click_outside_attribution_window_is_not_attributed(): void
    {
        $promo = $this->activate();
        $talent = $this->verifiedTalent();

        $this->actingAs($talent)->postJson(route('talent.promo.click', $promo));
        $this->travel(25)->hours();
        $this->actingAs($talent)->post(route('talent.lamar', $this->project->id), ['message' => 'Halo']);

        $this->assertSame(0, PromotionAttribution::count());
    }

    public function test_stats_dashboard_shows_real_daily_data(): void
    {
        $promo = $this->activate();
        $this->actingAs($this->talenta)->postJson(route('talent.promo.impression', $promo), ['page_token' => str_repeat('c', 24)]);

        $response = $this->actingAs($this->penyedia)->get(route('penyedia.promotions.stats', $promo))->assertOk();
        $response->assertSee('Performa Promosi Harian')->assertSee('Hari 1')->assertSee('Hari 4')->assertSee('Belum berjalan');

        $days = app(\App\Services\PromotionStatsService::class)->daily($promo);
        $this->assertCount(4, $days);
        $this->assertSame(1, $days[1]['impressions']);
        $this->assertSame('running', $days[1]['state']);
        $this->assertSame('upcoming', $days[2]['state']);

        $this->actingAs($this->penyedia)->get(route('penyedia.promotions.history'))->assertOk()->assertSee('Lihat Statistik');
    }

}
