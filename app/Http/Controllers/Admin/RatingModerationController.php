<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\RatingException;
use App\Http\Controllers\Controller;
use App\Models\ProjectWorkCompletion;
use App\Models\Rating;
use App\Models\RatingAuditLog;
use App\Models\RatingReport;
use App\Services\Rating\RatingService;
use App\Services\Rating\WorkCompletionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class RatingModerationController extends Controller
{
    public function __construct(private RatingService $ratings, private WorkCompletionService $work) {}

    public function index(Request $request): View
    {
        Gate::authorize('moderate', Rating::class);
        $tab = in_array($request->query('tab'), ['laporan', 'sengketa', 'log'], true) ? $request->query('tab') : 'rating';

        $data = ['tab' => $tab];

        $data['counts'] = [
            'rating'   => Rating::count(),
            'laporan'  => RatingReport::where('status', 'open')->count(),
            'sengketa' => ProjectWorkCompletion::where('status', ProjectWorkCompletion::STATUS_DISPUTED)->count(),
        ];

        $data['items'] = match ($tab) {
            'laporan'  => RatingReport::with(['rating.rater:id,name', 'rating.ratee:id,name', 'rating.project:id,title', 'reporter:id,name', 'handler:id,name'])
                ->orderByRaw("CASE WHEN status = 'open' THEN 0 ELSE 1 END")->latest()->paginate(15)->withQueryString(),
            'sengketa' => ProjectWorkCompletion::with(['project:id,title', 'talent:id,name', 'provider:id,name', 'disputer:id,name'])
                ->whereIn('status', [ProjectWorkCompletion::STATUS_DISPUTED])
                ->orWhereNotNull('resolved_at')
                ->orderByRaw("CASE WHEN status = 'disputed' THEN 0 ELSE 1 END")->latest('updated_at')->paginate(15)->withQueryString(),
            'log'      => RatingAuditLog::with(['user:id,name', 'rating.project:id,title', 'completion.project:id,title'])->latest('created_at')->latest('id')->paginate(25)->withQueryString(),
            default    => Rating::with(['rater:id,name', 'ratee:id,name', 'project:id,title', 'completion'])
                ->withCount(['reports as open_reports_count' => fn ($q) => $q->where('status', 'open')])
                ->latest()->paginate(15)->withQueryString(),
        };

        return view('admin.ratings.index', $data);
    }

    public function hide(Request $request, Rating $rating): RedirectResponse
    {
        Gate::authorize('moderate', $rating);
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:500']]);

        return $this->run(fn () => $this->ratings->hideReview($rating, $request->user(), $data['reason']), 'Ulasan disembunyikan. Nilai bintang tetap dihitung.');
    }

    public function restore(Request $request, Rating $rating): RedirectResponse
    {
        Gate::authorize('moderate', $rating);
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:500']]);

        return $this->run(fn () => $this->ratings->restoreReview($rating, $request->user(), $data['reason']), 'Ulasan dipulihkan.');
    }

    /** Batalkan rating karena pelanggaran terbukti: tidak lagi dihitung ke reputasi & syarat promosi. */
    public function void(Request $request, Rating $rating): RedirectResponse
    {
        Gate::authorize('moderate', $rating);
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:500']]);

        return $this->run(fn () => $this->ratings->voidRating($rating, $request->user(), $data['reason']), 'Rating dibatalkan dan tidak lagi dihitung ke reputasi.');
    }

    public function unvoid(Request $request, Rating $rating): RedirectResponse
    {
        Gate::authorize('moderate', $rating);
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:500']]);

        return $this->run(fn () => $this->ratings->unvoidRating($rating, $request->user(), $data['reason']), 'Pembatalan rating dicabut.');
    }

    public function handleReport(Request $request, RatingReport $report): RedirectResponse
    {
        Gate::authorize('moderate', Rating::class);
        $data = $request->validate([
            'decision'   => ['required', 'in:resolved,dismissed'],
            'admin_note' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        return $this->run(fn () => $this->ratings->handleReport($report, $request->user(), $data['decision'] === 'resolved', $data['admin_note']), 'Laporan ditangani.');
    }

    public function resolveDispute(Request $request, ProjectWorkCompletion $completion): RedirectResponse
    {
        Gate::authorize('resolve', $completion);
        $data = $request->validate([
            'decision'        => ['required', 'in:completed,reopen'],
            'resolution_note' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        return $this->run(
            fn () => $this->work->resolveDispute($completion, $request->user(), $data['decision'] === 'completed', $data['resolution_note']),
            $data['decision'] === 'completed' ? 'Sengketa diputuskan: pekerjaan selesai, periode penilaian dibuka.' : 'Sengketa diputuskan: pekerjaan dilanjutkan.'
        );
    }

    private function run(callable $action, string $success): RedirectResponse
    {
        try {
            $action();
        } catch (RatingException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return back()->with('success', $success);
    }
}
