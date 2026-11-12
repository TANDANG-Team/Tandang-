@extends('talent.layout')

@section('title', 'Lamaran Saya')

@section('content')
<div class="page-head">
    <div>
        <h1>Lamaran Saya</h1>
        <p>Pantau perkembangan setiap proyek yang telah kamu lamar.</p>
    </div>
</div>

<!-- Tabs Status Filter -->
<div class="tabs-nav">
    @foreach(['Semua', 'Menunggu', 'Dalam Review', 'Diterima', 'Ditolak'] as $tab)
        <a href="{{ route('talent.lamaran', ['tab' => $tab]) }}" 
           class="tab-btn {{ $currentTab === $tab ? 'active' : '' }}">
            {{ $tab }}
        </a>
    @endforeach
</div>

@if($applications->count() > 0)
    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Proyek & Penyedia</th>
                    <th>Tanggal Melamar</th>
                    <th>Pesan Pengantar</th>
                    <th>Status</th>
                    <th>Catatan Keputusan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($applications as $app)
                    @php
                        $p = $app->project;
                        $badgeClass = match($app->status) {
                            'Diterima' => 'badge-success',
                            'Ditolak' => 'badge-danger',
                            'Dalam Review' => 'badge-info',
                            default => 'badge-warning'
                        };
                    @endphp
                    <tr>
                        <td>
                            @if($p)
                                <strong>{{ $p->title }}</strong><br>
                                <small style="color: var(--teal); font-weight: 600;">{{ $p->provider_name }}</small> · 
                                <small style="color: var(--muted);">{{ $p->location }} ({{ $p->work_type }})</small>
                            @else
                                <strong style="color: var(--danger);">Proyek telah dihapus</strong>
                            @endif
                        </td>
                        <td>{{ $app->created_at->format('d M Y') }}</td>
                        <td>
                            <span style="font-size: 13px; color: #475569; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                {{ $app->message ?: '-' }}
                            </span>
                        </td>
                        <td>
                            <span class="badge {{ $badgeClass }}">
                                {{ $app->status }}
                            </span>
                        </td>
                        <td>
                            @if($app->decision_reason)
                                <span style="font-size: 12.5px; color: #64748b; font-style: italic;">
                                    "{{ $app->decision_reason }}"
                                </span>
                            @else
                                <span style="color: #cbd5e1;">-</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <div style="background: #fff; padding: 40px; border-radius: 12px; text-align: center; border: 1px dashed var(--line);">
        <p style="color: var(--muted); margin-bottom: 12px;">Belum ada lamaran proyek pada kategori ini.</p>
        <a href="{{ route('talent.cari') }}" class="btn btn-primary btn-sm">Jelajahi Proyek Sekarang</a>
    </div>
@endif
@endsection

