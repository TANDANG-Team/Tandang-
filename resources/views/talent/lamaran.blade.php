@extends('talent.layout')

@section('title', 'Lamaran Saya')

@section('content')
<div class="page-head">
    <div>
        <h1>Lamaran Saya</h1>
        <p>Pantau perkembangan setiap proyek yang telah kamu lamar.</p>
    </div>
</div>

<!-- Kuota pembatalan (maks. 3 per 7 hari) -->
<div class="alert {{ $cancelStatus['remaining'] > 0 ? 'alert-info' : 'alert-warning' }}" style="margin-bottom: 16px; font-size: 13.5px;">
    <span>
        Sisa pembatalan lamaran: <strong>{{ $cancelStatus['remaining'] }} dari {{ $cancelStatus['limit'] }}</strong>
        dalam {{ $cancelStatus['window_days'] }} hari terakhir.
        @if($cancelStatus['next_available_at'])
            Pembatalan berikutnya bisa dilakukan mulai
            <strong>{{ \Illuminate\Support\Carbon::parse($cancelStatus['next_available_at'])->setTimezone(config('app.timezone'))->format('d/m/Y H:i') }}</strong>.
        @endif
        Lamaran yang dibatalkan tidak bisa diajukan lagi ke proyek yang sama.
    </span>
</div>

<!-- Tabs Status Filter -->
<div class="tabs-nav">
    @foreach(['Semua', 'Menunggu', 'Dalam Review', 'Diterima', 'Ditolak', 'Dibatalkan'] as $tab)
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
                    <th>Aksi</th>
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
                            'Dibatalkan' => 'badge-purple',
                            default => 'badge-warning'
                        };
                        $canCancel = in_array($app->project_id, $cancellableProjectIds);
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
                        <td>
                            @if($canCancel)
                                <form method="POST" action="{{ route('talent.lamar.batal', $app->project_id) }}" style="margin: 0;"
                                      data-confirm-title="Batalkan Lamaran?"
                                      data-confirm="Lamaran ini akan dibatalkan dan Anda tidak dapat mengajukan lagi ke proyek yang sama."
                                      data-confirm-notice="Ini memakai 1 dari {{ $cancelStatus['limit'] }} jatah pembatalan per {{ $cancelStatus['window_days'] }} hari. Sisa jatah Anda: {{ $cancelStatus['remaining'] }}."
                                      data-confirm-type="danger"
                                      data-confirm-btn="Ya, Batalkan Lamaran"
                                      data-cancel-btn="Kembali">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-secondary btn-sm"
                                            @disabled($cancelStatus['remaining'] === 0)
                                            title="{{ $cancelStatus['remaining'] === 0 ? 'Batas pembatalan 7 hari terakhir sudah habis.' : 'Batalkan lamaran' }}">
                                        Batalkan
                                    </button>
                                </form>
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

