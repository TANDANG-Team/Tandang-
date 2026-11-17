<?php

namespace App\Http\Controllers\Penyedia;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectApplication;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $profile = $user->organizationProfile;
        $businessName = $profile?->organization_name ?: $user->name;

        // Statistics strictly queried from user's projects
        $projectQuery = Project::where('user_id', $user->id);
        $totalProjects = (clone $projectQuery)->count();
        $activeProjectsCount = (clone $projectQuery)->where('status', Project::STATUS_PUBLISHED)->count();

        $projectIds = (clone $projectQuery)->pluck('id');

        $totalApplicants = ProjectApplication::whereIn('project_id', $projectIds)->count();
        $pendingReviewCount = ProjectApplication::whereIn('project_id', $projectIds)
            ->whereIn('status', [ProjectApplication::STATUS_MENUNGGU, ProjectApplication::STATUS_DALAM_REVIEW])
            ->count();

        // Recent applicants
        $recentApplicants = ProjectApplication::with(['project', 'user'])
            ->whereIn('project_id', $projectIds)
            ->latest()
            ->take(5)
            ->get();

        // Active published projects
        $activeProjects = (clone $projectQuery)
            ->where('status', Project::STATUS_PUBLISHED)
            ->withCount(['applications', 'acceptedApplications'])
            ->latest()
            ->take(5)
            ->get();

        // Integrasi Sponsored Project & reputasi di dashboard.
        $livePromotions = \App\Models\ProjectPromotion::where('user_id', $user->id)
            ->live()->withStats()->with('project')->latest('start_at')->get();
        $promotableCount = (clone $projectQuery)->get()->filter(fn ($p) => $p->promotionBlocker() === null)->count();
        $reputation = app(\App\Services\Rating\RatingService::class)->summaryFor($user, \App\Models\Rating::ROLE_PENYEDIA);
        $pendingWork = \App\Models\ProjectWorkCompletion::where('provider_id', $user->id)
            ->where('status', \App\Models\ProjectWorkCompletion::STATUS_PENDING_CONFIRMATION)
            ->where('requested_by', '!=', $user->id)->count();

        return view('penyedia.dashboard', [
            'livePromotions' => $livePromotions,
            'promotableCount' => $promotableCount,
            'reputation' => $reputation,
            'pendingWork' => $pendingWork,
            'user' => $user,
            'profile' => $profile,
            'businessName' => $businessName,
            'totalProjects' => $totalProjects,
            'activeProjectsCount' => $activeProjectsCount,
            'totalApplicants' => $totalApplicants,
            'pendingReviewCount' => $pendingReviewCount,
            'recentApplicants' => $recentApplicants,
            'activeProjects' => $activeProjects,
        ]);
    }
}
