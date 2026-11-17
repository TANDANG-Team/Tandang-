<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\PromotionException;
use App\Http\Controllers\Controller;
use App\Models\ProjectPromotion;
use App\Models\PromotionEvent;
use App\Models\PromotionTransaction;
use App\Services\PromotionService;
use App\Services\PromotionStatsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Monitoring Sponsored Project. Admin dapat melihat & menghentikan promosi,
 * tetapi sengaja TIDAK ada endpoint untuk mengubah status pembayaran.
 */
class PromotionController extends Controller
{
    public function __construct(private PromotionService $promotions, private PromotionStatsService $stats) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', ProjectPromotion::class);
        $this->promotions->expireStaleTransactions();

        $tab    = $request->query('tab') === 'transaksi' ? 'transaksi' : 'promosi';
        $search = trim((string) $request->query('search'));
        $status = (string) $request->query('status');

        $promotions = ProjectPromotion::with(['project', 'user', 'transaction'])
            ->withStats()
            ->when($status !== '' && $tab === 'promosi', function ($q) use ($status) {
                $status === 'active' ? $q->live() : $q->where('status', $status);
            })
            ->when($search !== '', fn ($q) => $q->where(function ($s) use ($search) {
                $s->whereHas('project', fn ($p) => $p->withTrashed()->where('title', 'like', "%{$search}%"))
                  ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            }))
            ->latest()
            ->paginate(15, ['*'], 'promosi_page')
            ->withQueryString();

        $transactions = PromotionTransaction::with(['user', 'promotion.project'])
            ->when($status !== '' && $tab === 'transaksi', fn ($q) => $q->where('status', $status))
            ->when($search !== '', fn ($q) => $q->where(function ($s) use ($search) {
                $s->where('transaction_number', 'like', "%{$search}%")
                  ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
            }))
            ->latest()
            ->paginate(15, ['*'], 'transaksi_page')
            ->withQueryString();

        // Pendapatan hanya dari pembayaran nyata yang disetujui; transaksi simulasi dipisahkan.
        $paid     = (int) PromotionTransaction::realRevenue()->sum('amount');
        $refunded = (int) PromotionTransaction::where('is_simulation', false)->sum('refunded_amount');
        $events   = PromotionEvent::selectRaw('type, count(*) as total')->groupBy('type')->pluck('total', 'type');

        $summary = [
            'live'        => ProjectPromotion::live()->count(),
            'revenue'     => $paid - $refunded,
            'paid_count'  => PromotionTransaction::where('is_simulation', false)->where('status', PromotionTransaction::STATUS_PAID)->count(),
            'simulated'   => PromotionTransaction::where('is_simulation', true)->where('status', PromotionTransaction::STATUS_PAID)->count(),
            'pending'     => PromotionTransaction::awaitingReview()->where('is_simulation', false)->count(),
            'impressions' => (int) ($events[PromotionEvent::TYPE_IMPRESSION] ?? 0),
            'clicks'      => (int) ($events[PromotionEvent::TYPE_CLICK] ?? 0),
        ];
        $summary['ctr'] = ProjectPromotion::ctr($summary['clicks'], $summary['impressions']);

        return view('admin.promotions.index', compact('tab', 'search', 'status', 'promotions', 'transactions', 'summary'));
    }

    public function show(ProjectPromotion $promotion): View
    {
        Gate::authorize('viewAny', ProjectPromotion::class);
        $promotion->load(['project', 'user', 'transaction', 'auditLogs.user', 'stopper']);

        return view('admin.promotions.show', [
            'promotion' => $promotion,
            'stats'     => $this->stats->forPromotion($promotion),
        ]);
    }

    public function stop(Request $request, ProjectPromotion $promotion): RedirectResponse
    {
        Gate::authorize('stop', $promotion);
        $data = $request->validate(['stop_reason' => ['required', 'string', 'min:5', 'max:500']], [
            'stop_reason.required' => 'Alasan penghentian wajib diisi.',
            'stop_reason.min'      => 'Alasan penghentian minimal 5 karakter.',
        ]);

        try {
            $this->promotions->stopByAdmin($promotion, $request->user(), $data['stop_reason']);
        } catch (PromotionException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()->route('admin.promotions.show', $promotion)->with('success', 'Promosi dihentikan dan tidak lagi ditayangkan.');
    }
}
