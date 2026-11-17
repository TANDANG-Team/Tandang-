<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\PromotionException;
use App\Http\Controllers\Controller;
use App\Models\ProjectPromotion;
use App\Models\PromotionAuditLog;
use App\Models\PromotionTransaction;
use App\Services\PromotionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Verifikasi manual pembayaran QRIS statis. Admin mencocokkan mutasi rekening merchant
 * sebelum menyetujui; screenshot saja tidak cukup.
 */
class PaymentVerificationController extends Controller
{
    public function __construct(private PromotionService $promotions) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', ProjectPromotion::class);
        $this->promotions->expireStaleTransactions();

        $tab = $request->query('tab') === 'riwayat' ? 'riwayat' : 'antrean';

        $query = PromotionTransaction::with(['user:id,name,email', 'promotion.project', 'verifier:id,name'])
            ->where('is_simulation', false)
            ->whereNotNull('submitted_at');

        $transactions = $tab === 'antrean'
            ? $query->awaitingReview()->orderBy('submitted_at')->paginate(15)->withQueryString()
            : $query->whereIn('status', [PromotionTransaction::STATUS_PAID, PromotionTransaction::STATUS_REJECTED, PromotionTransaction::STATUS_REFUNDED])->latest('verified_at')->paginate(15)->withQueryString();

        $counts = [
            'antrean' => PromotionTransaction::where('is_simulation', false)->awaitingReview()->count(),
            'late'    => PromotionTransaction::where('is_simulation', false)->where('status', 'expired')->where('late_submission', true)->whereNull('verified_at')->count(),
        ];

        return view('admin.payments.index', compact('tab', 'transactions', 'counts'));
    }

    public function show(PromotionTransaction $transaction): View
    {
        Gate::authorize('viewAny', ProjectPromotion::class);
        $transaction->load(['user', 'promotion.project', 'verifier']);

        $logs = PromotionAuditLog::with('user:id,name')
            ->where('promotion_transaction_id', $transaction->id)
            ->latest('created_at')->latest('id')->get();

        // Bantu Admin mendeteksi penggunaan ulang referensi/bukti.
        $duplicates = PromotionTransaction::whereKeyNot($transaction->id)
            ->where(function ($q) use ($transaction) {
                $q->when($transaction->proof_hash, fn ($h) => $h->where('proof_hash', $transaction->proof_hash))
                  ->when($transaction->payer_reference, fn ($r) => $r->orWhere('payer_reference', $transaction->payer_reference));
            })
            ->when(! $transaction->proof_hash && ! $transaction->payer_reference, fn ($q) => $q->whereRaw('1 = 0'))
            ->get(['id', 'transaction_number', 'status']);

        return view('admin.payments.show', [
            'transaction' => $transaction,
            'logs'        => $logs,
            'duplicates'  => $duplicates,
            'qris'        => config('promotion.qris'),
            'standing'    => app(\App\Services\PromotionEligibilityService::class)->standing($transaction->user),
        ]);
    }

    public function proof(PromotionTransaction $transaction)
    {
        Gate::authorize('viewAny', ProjectPromotion::class);
        abort_unless($transaction->proof_path && Storage::disk('local')->exists($transaction->proof_path), 404);

        return Storage::disk('local')->response($transaction->proof_path);
    }

    public function approve(Request $request, PromotionTransaction $transaction): RedirectResponse
    {
        Gate::authorize('viewAny', ProjectPromotion::class);
        $data = $request->validate([
            'confirm_received' => ['accepted'],
            'note'             => ['nullable', 'string', 'max:500'],
            'override_eligibility' => ['nullable', 'boolean'],
        ], [
            'confirm_received.accepted' => 'Centang konfirmasi bahwa dana Rp' . number_format($transaction->amount, 0, ',', '.') . ' benar-benar sudah diterima di rekening merchant.',
        ]);

        try {
            $this->promotions->approveManual($transaction, $request->user(), $data['note'] ?? null, (bool) ($data['override_eligibility'] ?? false));
        } catch (PromotionException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()->route('admin.payments.index')->with('success', "Pembayaran {$transaction->transaction_number} disetujui. Promosi aktif 96 jam.");
    }

    public function reject(Request $request, PromotionTransaction $transaction): RedirectResponse
    {
        Gate::authorize('viewAny', ProjectPromotion::class);
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:500']], [
            'reason.required' => 'Alasan penolakan wajib diisi.',
        ]);

        try {
            $this->promotions->rejectManual($transaction, $request->user(), $data['reason']);
        } catch (PromotionException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()->route('admin.payments.index')->with('success', "Pembayaran {$transaction->transaction_number} ditolak.");
    }
}
