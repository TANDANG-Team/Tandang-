@extends('layouts.penyedia')

@section('title', 'Notifikasi')

@section('content')
<div class="page-header">
    <div class="page-title-box">
        <h1>Notifikasi</h1>
        <p>Riwayat aktivitas akunmu.</p>
    </div>
    @if($unreadCount > 0)
        <div>
            <form action="{{ route('penyedia.notifications.readAll') }}" method="POST">
                @csrf
                <button type="submit" class="btn-secondary-action">
                    Tandai semua dibaca
                </button>
            </form>
        </div>
    @endif
</div>

<!-- Filter Tabs -->
<div style="display: flex; gap: 8px; margin-bottom: 20px;">
    <a href="{{ route('penyedia.notifications.index', ['filter' => 'all']) }}"
       style="padding: 7px 14px; border-radius: 8px; font-size: 13.5px; font-weight: 600; text-decoration: none; {{ $filter !== 'unread' ? 'background: #0fa48f; color: #ffffff;' : 'background: #f3f4f6; color: #4b5563;' }}">
        Semua
    </a>
    <a href="{{ route('penyedia.notifications.index', ['filter' => 'unread']) }}"
       style="padding: 7px 14px; border-radius: 8px; font-size: 13.5px; font-weight: 600; text-decoration: none; {{ $filter === 'unread' ? 'background: #0fa48f; color: #ffffff;' : 'background: #f3f4f6; color: #4b5563;' }}">
        Belum dibaca ({{ $unreadCount }})
    </a>
</div>

<!-- Notifications List -->
<div style="background: #ffffff; border: 1px solid #e9edf2; border-radius: 14px; overflow: hidden; box-shadow: 0 2px 6px rgba(0,0,0,0.02);">
    @if($notifications->isEmpty())
        <div style="text-align: center; padding: 60px 20px;">
            <svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="#9ca3af" stroke-width="1.5" style="margin-bottom: 12px;">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>
            <h3 style="font-size: 16px; font-weight: 700; color: #374151; margin: 0 0 6px;">Belum ada notifikasi</h3>
            <p style="font-size: 13.5px; color: #6b7280; margin: 0;">Coba ubah filter atau lakukan aktivitas baru.</p>
        </div>
    @else
        <div style="divide-y divide-gray-100;">
            @foreach($notifications as $notif)
                <div style="padding: 18px 22px; display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; border-bottom: 1px solid #f3f4f6; background: {{ $notif->is_read ? '#ffffff' : '#f0fdf4' }};">
                    <div style="display: flex; gap: 14px; align-items: flex-start; flex: 1;">
                        <div style="width: 10px; height: 10px; border-radius: 50%; background: {{ $notif->is_read ? 'transparent' : '#0fa48f' }}; margin-top: 6px; flex-shrink: 0;"></div>
                        <div>
                            <div style="font-size: 14.5px; font-weight: 700; color: #111827; margin-bottom: 4px;">
                                {{ $notif->title }}
                            </div>
                            <div style="font-size: 13.5px; color: #4b5563; line-height: 1.5;">
                                {{ $notif->message }}
                            </div>
                            <div style="font-size: 12px; color: #9ca3af; margin-top: 6px;">
                                {{ $notif->created_at->diffForHumans() }}
                            </div>
                        </div>
                    </div>

                    @if(! $notif->is_read)
                        <form action="{{ route('penyedia.notifications.markAsRead', $notif) }}" method="POST" style="margin: 0;">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn-secondary-action" style="padding: 5px 10px; font-size: 12px; white-space: nowrap;">
                                Tandai Dibaca
                            </button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>

        @if($notifications->hasPages())
            <div style="padding: 16px 20px; border-top: 1px solid #e5e7eb;">
                {{ $notifications->links() }}
            </div>
        @endif
    @endif
</div>
@endsection
