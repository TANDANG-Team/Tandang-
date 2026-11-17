<?php

namespace App\Http\Controllers\Penyedia;

use App\Exceptions\PromotionException;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectPromotion;
use App\Models\PromotionPackage;
use App\Models\PromotionTransaction;
use App\Services\PromotionService;
use App\Services\PromotionStatsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PromotionController extends Controller
{
    public function __construct(
        private PromotionService $promotions,
        private PromotionStatsService $stats,
        private \App\Services\PromotionEligibilityService $eligibility,
    ) {}

    /** Halaman utama "Promosi Proyek": proyek milik Penyedia + kampanye yang sedang tayang. */
    public function index(Request $request): View
    {
        Gate::authorize('create', ProjectPromotion::class);
        $user = $request->user();

        // Bereskan tagihan kedaluwarsa milik user ini agar status di layar akurat.
        $this->promotions->expireStaleTransactions();

        $projects = Project::where('user_id', $user->id)
            ->withCount(['acceptedApplications as accepted_applications_count'])
            ->with(['livePromotion', 'latestPromotion.transaction'])
            ->latest()
            ->get()
            ->map(function (Project $project) {
                $project->blocker = $project->promotionBlocker();
                return $project;
            });

        $live = ProjectPromotion::where('user_id', $user->id)->live()->withStats()->with('project')->get();
        $standing = $this->eligibility->standing($user);

        $summary = [
            'live'        => $live->count(),
            'impressions' => (int) $live->sum('impressions_total'),
            'clicks'      => (int) $live->sum('clicks_total'),
            'eligible'    => $standing['eligible'] ? $projects->whereNull('blocker')->count() : 0,
        ];

        $trx = PromotionTransaction::where('user_id', $user->id)->where('is_simulation', false);
        $promos = ProjectPromotion::where('user_id', $user->id);
        $statusBoard = [
            ['Menunggu Pembayaran', (clone $trx)->where('status', 'pending')->where('expires_at', '>', now())->count(), 'warning'],
            ['Menunggu Verifikasi Admin', (clone $trx)->awaitingReview()->count(), 'info'],
            ['Pembayaran Berhasil', (clone $trx)->where('status', 'paid')->count(), 'success'],
            ['Pembayaran Ditolak', (clone $trx)->where('status', 'rejected')->count(), 'danger'],
            ['Pembayaran Kedaluwarsa', (clone $trx)->where('status', 'expired')->where('late_submission', false)->count(), 'muted'],
            ['Promosi Aktif', (clone $promos)->live()->count(), 'success'],
            ['Promosi Berakhir', (clone $promos)->where(fn ($q) => $q->where('status', 'ended')->orWhere(fn ($a) => $a->where('status', 'active')->where('end_at', '<=', now())))->count(), 'muted'],
        ];

        return view('penyedia.promotions.index', [
            'standing'    => $standing,
            'statusBoard' => $statusBoard,
            'projects' => $projects,
            'live'     => $live,
            'summary'  => $summary,
            'package'  => PromotionPackage::current(),
        ]);
    }

    /** Ringkasan paket + preview kartu Sponsored sebelum checkout. */
    public function checkout(Request $request, Project $project): View|RedirectResponse
    {
        Gate::authorize('promoteProject', [ProjectPromotion::class, $project]);

        // Syarat rating usaha dicek di server — URL checkout langsung tidak bisa melewatinya.
        if ($reason = $this->eligibility->providerBlocker($request->user())) {
            return redirect()->route('penyedia.promotions.index')->with('error', $reason);
        }

        $package = PromotionPackage::current();
        if (! $package) {
            return redirect()->route('penyedia.promotions.index')->with('error', 'Paket promosi belum tersedia. Jalankan PromotionPackageSeeder.');
        }

        $this->promotions->expireStaleTransactions($project->id);

        return view('penyedia.promotions.checkout', [
            'project' => $project->loadCount(['acceptedApplications as accepted_applications_count']),
            'package' => $package,
            'blocker' => $project->promotionBlocker(),
            'pending' => $project->promotions()->where('status', ProjectPromotion::STATUS_PENDING_PAYMENT)->latest()->first(),
        ]);
    }

    /** Buat transaksi Pending. Tidak menerima harga/durasi dari form. */
    public function store(Request $request, Project $project): RedirectResponse
    {
        Gate::authorize('promoteProject', [ProjectPromotion::class, $project]);
        $request->validate(['agree' => ['accepted']], ['agree.accepted' => 'Setujui kebijakan promosi terlebih dahulu.']);

        try {
            $promotion = $this->promotions->checkout($project, $request->user());
        } catch (PromotionException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()->route('penyedia.promotions.payment', $promotion);
    }

    /** Halaman pembayaran QRIS (simulasi development). */
    public function payment(Request $request, ProjectPromotion $promotion): View|RedirectResponse
    {
        Gate::authorize('view', $promotion);
        $promotion->load(['project', 'transaction']);

        if ($promotion->transaction?->isPaymentWindowOver()) {
            $this->promotions->expireStaleTransactions($promotion->project_id);
            $promotion->refresh()->load('transaction');
        }

        // Sudah lunas → tampilkan halaman Promosi Berhasil.
        if ($promotion->transaction?->isPaid()) {
            return view('penyedia.promotions.success', ['promotion' => $promotion, 'transaction' => $promotion->transaction]);
        }

        return view('penyedia.promotions.payment', [
            'promotion'         => $promotion,
            'transaction'       => $promotion->transaction,
            'simulationEnabled' => (bool) config('promotion.simulation_enabled'),
            'qrisReady'         => \App\Services\Payments\ManualQrisGateway::isConfigured(),
            'qris'              => config('promotion.qris'),
            // Sisa waktu dihitung server; JS hanya meneruskan hitung mundur dari angka ini.
            'remainingSeconds'  => $promotion->transaction?->remainingSeconds() ?? 0,
            // Rating turun setelah checkout → pembayaran yang belum dilakukan tidak boleh dilanjutkan.
            'providerBlocker'   => $promotion->transaction?->isPending() ? $this->eligibility->providerBlocker($request->user()) : null,
        ]);
    }

    /** Polling status dari halaman pembayaran. */
    public function status(ProjectPromotion $promotion): JsonResponse
    {
        Gate::authorize('view', $promotion);
        $trx = $promotion->transaction;

        return response()->json([
            'transaction_status' => $trx?->isPaymentWindowOver() ? PromotionTransaction::STATUS_EXPIRED : $trx?->status,
            'remaining_seconds'  => $trx?->remainingSeconds() ?? 0,
            'promotion_status'   => $promotion->effective_status,
        ]);
    }

    /**
     * "Saya Sudah Bayar" + unggah bukti. Hanya mencatat klaim (Menunggu Verifikasi);
     * promosi baru aktif setelah Admin mencocokkan dana yang diterima merchant.
     */
    public function submitProof(Request $request, ProjectPromotion $promotion): RedirectResponse
    {
        Gate::authorize('submitProof', $promotion);

        $data = $request->validate([
            'proof'           => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
            'payer_amount'    => ['required', 'integer', 'min:1', 'max:100000000'],
            'payer_name'      => ['nullable', 'string', 'max:150'],
            'payer_reference' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9\-\/_. ]+$/'],
            'payer_paid_at'   => ['nullable', 'date', 'before_or_equal:now'],
        ], [
            'proof.required'          => 'Unggah bukti pembayaran.',
            'proof.mimes'             => 'Bukti harus berformat JPG, JPEG, PNG, atau PDF.',
            'proof.max'               => 'Ukuran bukti maksimal 2 MB.',
            'payer_amount.required'   => 'Isi nominal yang Anda bayarkan.',
            'payer_reference.regex'   => 'Nomor referensi hanya boleh berisi huruf, angka, dan tanda - / _ .',
            'payer_paid_at.before_or_equal' => 'Waktu pembayaran tidak boleh di masa depan.',
        ]);

        $file = $request->file('proof');
        $hash = hash_file('sha256', $file->getRealPath());
        // Disk "local" = storage/app/private, nama file acak (unik) — tidak bisa diakses publik.
        $path = $file->storeAs('payments/proofs/' . now()->format('Y/m'), \Illuminate\Support\Str::uuid() . '.' . $file->extension(), 'local');

        try {
            $result = $this->promotions->submitProof($promotion->transaction, $request->user(), [
                'payer_name'      => $data['payer_name'] ?? null,
                'payer_reference' => $data['payer_reference'] ?? null,
                'payer_amount'    => (int) $data['payer_amount'],
                'payer_paid_at'   => $data['payer_paid_at'] ?? null,
            ], $path, $hash);
        } catch (PromotionException $e) {
            \Illuminate\Support\Facades\Storage::disk('local')->delete($path);
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }

        return redirect()->route('penyedia.promotions.payment', $promotion)->with('success', $result === 'late'
            ? 'Konfirmasi diterima setelah batas waktu. Promosi tidak aktif otomatis; Admin akan meninjau pembayaran terlambat ini.'
            : 'Bukti pembayaran terkirim. Status: Menunggu Verifikasi Admin. Promosi aktif setelah dana dipastikan masuk.');
    }

    /** Bukti pembayaran milik sendiri (private storage). */
    public function proofFile(ProjectPromotion $promotion)
    {
        Gate::authorize('view', $promotion);
        $path = $promotion->transaction?->proof_path;
        abort_unless($path && \Illuminate\Support\Facades\Storage::disk('local')->exists($path), 404);

        return \Illuminate\Support\Facades\Storage::disk('local')->response($path);
    }

    /** Tombol "Simulasikan Pembayaran Berhasil" — Hanya untuk Pengujian. */
    public function simulatePaid(Request $request, ProjectPromotion $promotion): RedirectResponse
    {
        abort_unless(config('promotion.simulation_enabled'), 404);
        Gate::authorize('view', $promotion);

        try {
            $this->promotions->simulatePaid($promotion->transaction, $request->user());
        } catch (PromotionException $e) {
            return redirect()->route('penyedia.promotions.payment', $promotion)->withErrors(['error' => $e->getMessage()]);
        }

        // Halaman pembayaran menampilkan "Promosi Berhasil" untuk transaksi lunas.
        return redirect()->route('penyedia.promotions.payment', $promotion);
    }

    public function simulateFailed(Request $request, ProjectPromotion $promotion): RedirectResponse
    {
        abort_unless(config('promotion.simulation_enabled'), 404);
        Gate::authorize('pay', $promotion);

        try {
            $this->promotions->markFailed($promotion->transaction, 'Simulasi pembayaran gagal (development).', $request->user());
        } catch (PromotionException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()->route('penyedia.promotions.payment', $promotion)->with('error', 'Pembayaran simulasi ditandai gagal. Promosi tidak diaktifkan.');
    }

    public function cancel(Request $request, ProjectPromotion $promotion): RedirectResponse
    {
        Gate::authorize('cancel', $promotion);

        try {
            $this->promotions->cancel($promotion, $request->user());
        } catch (PromotionException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()->route('penyedia.promotions.index')->with('success', 'Checkout promosi dibatalkan. Tidak ada biaya yang ditagihkan.');
    }

    /** Riwayat seluruh promosi milik Penyedia beserta total statistik. */
    public function history(Request $request): View
    {
        Gate::authorize('create', ProjectPromotion::class);
        $this->promotions->expireStaleTransactions();

        $promotions = ProjectPromotion::where('user_id', $request->user()->id)
            ->with(['project', 'transaction'])
            ->withStats()
            ->latest()
            ->paginate(10);

        return view('penyedia.promotions.history', compact('promotions'));
    }

    /** Dashboard statistik satu kampanye — hanya pemiliknya. */
    public function stats(ProjectPromotion $promotion): View
    {
        Gate::authorize('view', $promotion);
        $promotion->load(['project', 'transaction', 'auditLogs']);

        return view('penyedia.promotions.stats', [
            'promotion' => $promotion,
            'stats'     => $this->stats->forPromotion($promotion),
            'canRepeat' => $promotion->project && $this->eligibility->blockerFor(request()->user(), $promotion->project) === null,
        ]);
    }
}
