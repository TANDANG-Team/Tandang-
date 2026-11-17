<?php

namespace App\Http\Controllers;

use App\Exceptions\RatingException;
use App\Models\ProjectWorkCompletion;
use App\Models\Rating;
use App\Models\RatingReport;
use App\Services\Rating\RatingService;
use App\Services\Rating\WorkCompletionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Riwayat pengerjaan, konfirmasi selesai, dan penilaian — dipakai Penyedia & Talenta.
 * Role menentukan layout & sisi data; aturan bisnis ada di service.
 */
class EngagementController extends Controller
{
    public function __construct(private WorkCompletionService $work, private RatingService $ratings) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $this->work->backfillForUser($user);

        $isProvider = $user->isPenyedia();

        $engagements = ProjectWorkCompletion::query()
            ->where($isProvider ? 'provider_id' : 'talent_id', $user->id)
            ->whereHas('application', fn ($q) => $q->where('status', 'diterima'))
            ->with(['project', 'talent:id,name', 'provider:id,name', 'ratings'])
            ->latest('updated_at')
            ->get()
            ->each(fn (ProjectWorkCompletion $c) => $c->rating_state = $this->ratings->stateFor($c, $user));

        // Dikelompokkan menurut kelayakan penilaian dari sudut pandang pengguna ini.
        $groups = [
            'perlu'   => $engagements->filter(fn ($c) => $c->rating_state === RatingService::STATE_NOT_RATED)->values(),
            'sudah'   => $engagements->filter(fn ($c) => in_array($c->rating_state, [RatingService::STATE_WAITING_OTHER, RatingService::STATE_PUBLISHED], true))->values(),
            'belum'   => $engagements->filter(fn ($c) => in_array($c->rating_state, [RatingService::STATE_NOT_AVAILABLE, RatingService::STATE_WINDOW_CLOSED], true))->values(),
        ];

        return view($isProvider ? 'penyedia.engagements.index' : 'talent.riwayat', compact('groups', 'engagements'));
    }

    public function requestCompletion(Request $request, ProjectWorkCompletion $completion): RedirectResponse
    {
        Gate::authorize('participate', $completion);

        return $this->run(fn () => $this->work->request($completion, $request->user()),
            'Pengajuan selesai terkirim. Menunggu konfirmasi pihak lain.');
    }

    public function confirm(Request $request, ProjectWorkCompletion $completion): RedirectResponse
    {
        Gate::authorize('participate', $completion);

        return $this->run(fn () => $this->work->confirm($completion, $request->user()),
            'Pekerjaan dinyatakan selesai. Kedua pihak dapat memberi penilaian dalam 7 hari.');
    }

    public function dispute(Request $request, ProjectWorkCompletion $completion): RedirectResponse
    {
        Gate::authorize('participate', $completion);
        $data = $request->validate(['dispute_reason' => ['required', 'string', 'min:10', 'max:1000']], [
            'dispute_reason.required' => 'Jelaskan alasan sengketa.',
            'dispute_reason.min'      => 'Alasan sengketa minimal 10 karakter.',
        ]);

        return $this->run(fn () => $this->work->dispute($completion, $request->user(), $data['dispute_reason']),
            'Sengketa diajukan. Admin akan meninjau dan memutuskan.');
    }

    public function rateForm(Request $request, ProjectWorkCompletion $completion): View|RedirectResponse
    {
        Gate::authorize('participate', $completion);
        $user = $request->user();
        $completion->load(['project', 'talent', 'provider', 'ratings']);

        $state = $this->ratings->stateFor($completion, $user);
        if ($state !== RatingService::STATE_NOT_RATED) {
            return redirect()->route($this->indexRoute($user))->with('warning', RatingService::stateLabel($state) . '.');
        }

        return view($user->isPenyedia() ? 'penyedia.engagements.rate' : 'talent.rate', [
            'completion' => $completion,
            'aspects'    => $this->ratings->aspectsFor($completion, $user),
            'ratee'      => $completion->counterpartOf($user),
        ]);
    }

    public function rateStore(Request $request, ProjectWorkCompletion $completion): RedirectResponse
    {
        Gate::authorize('participate', $completion);
        $user = $request->user();
        $aspects = $this->ratings->aspectsFor($completion, $user);

        $rules = ['review' => ['nullable', 'string', 'max:500']];
        foreach ($aspects as $key => $aspect) {
            $rules["scores.{$key}"] = ! empty($aspect['not_applicable'])
                ? ['nullable']
                : ['required', 'integer', 'between:1,5'];
        }
        $data = $request->validate($rules, [
            'scores.*.required' => 'Semua aspek wajib diberi bintang.',
            'scores.*.between'  => 'Nilai bintang harus 1–5.',
            'review.max'        => 'Ulasan maksimal 500 karakter.',
        ]);

        $scores = array_map(fn ($v) => $v === null ? null : (int) $v, $data['scores'] ?? []);

        try {
            $rating = $this->ratings->submit($completion, $user, $scores, $data['review'] ?? null);
        } catch (RatingException $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }

        $message = $rating->published_at
            ? 'Penilaian terkirim dan sudah dipublikasikan karena kedua pihak telah menilai.'
            : 'Penilaian terkirim. Isinya disembunyikan sampai pihak lain menilai atau periode 7 hari berakhir.';

        return redirect()->route($this->indexRoute($user))->with('success', $message);
    }

    /** Laporan pelanggaran (oleh pihak dinilai) atau permohonan koreksi (oleh pemberi). */
    public function report(Request $request, Rating $rating): RedirectResponse
    {
        Gate::authorize('report', $rating);
        $data = $request->validate([
            'type'   => ['required', Rule::in([RatingReport::TYPE_VIOLATION, RatingReport::TYPE_CORRECTION])],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        try {
            $this->ratings->report($rating, $request->user(), $data['type'], $data['reason']);
        } catch (RatingException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return back()->with('success', 'Laporan terkirim ke Admin untuk ditinjau.');
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

    private function indexRoute($user): string
    {
        return $user->isPenyedia() ? 'penyedia.engagements.index' : 'talent.riwayat';
    }
}
