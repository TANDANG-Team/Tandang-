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
            ->withCount('applications')
            ->latest()
            ->take(5)
            ->get();

        return view('penyedia.dashboard', [
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
