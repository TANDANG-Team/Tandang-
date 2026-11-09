<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Display a listing of users with filters and search.
     */
    public function index(Request $request)
    {
        $roleFilter = $request->query('role', 'Semua');
        $statusFilter = $request->query('status', 'Semua');
        $searchQuery = $request->query('q', '');

        $query = User::with('talentProfile');

        // Search query
        if (!empty($searchQuery)) {
            $query->where(function ($q) use ($searchQuery) {
                $q->where('name', 'like', "%{$searchQuery}%")
                  ->orWhere('email', 'like', "%{$searchQuery}%");
            });
        }

        // Role filter
        if ($roleFilter !== 'Semua') {
            $query->where('role', $roleFilter);
        }

        // Status filter
        if ($statusFilter === 'Aktif') {
            $query->where('is_active', true);
        } elseif ($statusFilter === 'Dinonaktifkan') {
            $query->where('is_active', false);
        }

        $users = $query->orderByDesc('id')->get();

        $counts = [
            'total' => User::count(),
            'talent' => User::where('role', 'talenta_muda')->count(),
            'provider' => User::where('role', 'penyedia_proyek')->count(),
            'admin' => User::where('role', 'admin')->count(),
            'active' => User::where('is_active', true)->count(),
            'inactive' => User::where('is_active', false)->count(),
        ];

        return view('admin.pengguna', [
            'users' => $users,
            'counts' => $counts,
            'roleFilter' => $roleFilter,
            'statusFilter' => $statusFilter,
            'searchQuery' => $searchQuery,
        ]);
    }

    /**
     * Update user information (name and email).
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.unique' => 'Email ini sudah digunakan oleh akun lain.',
        ]);

        $user->update($validated);

        return redirect()->back()->with('success', "Informasi akun {$user->name} berhasil diperbarui.");
    }

    /**
     * Toggle active/inactive state of a user.
     */
    public function toggleActive($id)
    {
        $user = User::findOrFail($id);

        // Security check: cannot deactivate currently logged-in admin
        if ($user->id === Auth::id()) {
            return redirect()->back()->with('warning', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        $user->is_active = !$user->is_active;
        $user->save();

        $statusText = $user->is_active ? 'diaktifkan kembali' : 'dinonaktifkan';
        return redirect()->back()->with('success', "Akun {$user->name} berhasil {$statusText}.");
    }
}

