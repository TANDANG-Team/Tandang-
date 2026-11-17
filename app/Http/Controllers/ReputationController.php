<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectApplication;
use App\Models\Rating;
use App\Models\User;
use App\Services\Rating\RatingService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Profil reputasi publik: Penyedia dilihat Talenta, Talenta dilihat Penyedia. */
class ReputationController extends Controller
{
    public function __construct(private RatingService $ratings) {}

    /** Talenta melihat profil & reputasi Penyedia. */
    public function provider(Request $request, User $user): View
    {
        abort_unless($user->isPenyedia(), 404);

        $openProjects = Project::where('user_id', $user->id)->openForTalent()->latest()->take(6)->get();

        return view('talent.provider', [
            'provider'     => $user->load('organizationProfile'),
            'summary'      => $this->ratings->summaryFor($user, Rating::ROLE_PENYEDIA),
            'openProjects' => $openProjects,
        ]);
    }

    /** Penyedia melihat reputasi Talenta — hanya Talenta yang pernah melamar proyeknya. */
    public function talent(Request $request, User $user): View
    {
        abort_unless($user->isTalenta(), 404);

        $applied = ProjectApplication::where('user_id', $user->id)
            ->whereHas('project', fn ($q) => $q->withTrashed()->where('user_id', $request->user()->id))
            ->exists();
        abort_unless($applied, 403, 'Reputasi Talenta hanya dapat dilihat oleh Penyedia yang menerima lamarannya.');

        return view('penyedia.talent-reputation', [
            'talent'  => $user->load('talentProfile'),
            'summary' => $this->ratings->summaryFor($user, Rating::ROLE_TALENTA),
        ]);
    }
}
