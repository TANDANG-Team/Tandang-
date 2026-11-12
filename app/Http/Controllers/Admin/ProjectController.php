<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    /**
     * Display a listing of projects for moderation.
     */
    public function index(Request $request)
    {
        $statusFilter = $request->query('status', 'Semua');
        $providerFilter = $request->query('provider', 'Semua');
        $searchQuery = $request->query('q', '');

        $query = Project::with('user');

        // Search query
        if (!empty($searchQuery)) {
            $query->where(function ($q) use ($searchQuery) {
                $q->where('title', 'like', "%{$searchQuery}%")
                  ->orWhere('category', 'like', "%{$searchQuery}%")
                  ->orWhere('location', 'like', "%{$searchQuery}%")
                  ->orWhere('provider_name', 'like', "%{$searchQuery}%");
            });
        }

        // Status filter
        if ($statusFilter !== 'Semua') {
            $query->where('status', $statusFilter);
        }

        // Provider filter
        if ($providerFilter !== 'Semua') {
            $query->where('user_id', $providerFilter);
        }

        $projects = $query->orderBy('id', 'asc')->get();

        // Get all providers for filter dropdown
        $providers = User::where('role', 'penyedia_proyek')->orderBy('name')->get();

        $counts = [
            'total' => Project::count(),
            'published' => Project::where('status', 'Dipublikasikan')->count(),
            'draft' => Project::where('status', 'Draf')->count(),
            'closed' => Project::where('status', 'Ditutup')->count(),
            'archived' => Project::where('status', 'Diarsipkan')->count(),
        ];

        return view('admin.proyek', [
            'projects' => $projects,
            'providers' => $providers,
            'counts' => $counts,
            'statusFilter' => $statusFilter,
            'providerFilter' => $providerFilter,
            'searchQuery' => $searchQuery,
        ]);
    }

    /**
     * Update project status (moderation action).
     */
    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:Dipublikasikan,Draf,Ditutup,Diarsipkan',
        ]);

        $project = Project::findOrFail($id);
        $project->update(['status' => $validated['status']]);

        return redirect()->back()->with('success', "Status proyek '{$project->title}' berhasil diubah menjadi '{$project->status}'.");
    }
}

