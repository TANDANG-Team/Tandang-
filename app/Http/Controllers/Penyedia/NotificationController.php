<?php

namespace App\Http\Controllers\Penyedia;

use App\Http\Controllers\Controller;
use App\Models\ProviderNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * Display a listing of notifications for the provider.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $filter = $request->query('filter', 'all');

        $query = ProviderNotification::where('user_id', $user->id);

        if ($filter === 'unread') {
            $query->where('is_read', false);
        }

        $notifications = $query->latest()->paginate(15)->withQueryString();
        $unreadCount = ProviderNotification::where('user_id', $user->id)->where('is_read', false)->count();

        return view('penyedia.notifications.index', [
            'user' => $user,
            'profile' => $user->organizationProfile,
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'filter' => $filter,
        ]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(Request $request, ProviderNotification $notification): RedirectResponse
    {
        if ($notification->user_id !== $request->user()->id) {
            abort(403);
        }

        $notification->markAsRead();

        if ($notification->target_url) {
            return redirect($notification->target_url);
        }

        return redirect()->back()->with('success', 'Notifikasi telah ditandai dibaca.');
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request): RedirectResponse
    {
        $user = $request->user();

        ProviderNotification::where('user_id', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return redirect()->back()->with('success', 'Semua notifikasi telah ditandai dibaca.');
    }
}
