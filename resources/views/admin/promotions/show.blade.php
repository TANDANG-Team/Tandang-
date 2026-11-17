@extends('layouts.admin')

@section('title', 'Detail Promosi')
@section('header', 'Sponsored Project')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/promotion.css') }}?v=2">
@endpush

@section('content')
@php $trx = $promotion->transaction; @endphp
<div class="page-head" style="display:flex; justify-content:space-between; gap:16px; flex-wrap:wrap; align-items:flex-end;">
    <div>
        <h1>{{ $promotion->project?->title ?? 'Proyek dihapus' }}</h1>
        <p>{{ $promotion->user?->name }} · <span class="tdg-pill {{ $promotion->status_tone }}">{{ $promotion->status_label }}</span></p>
    </div>
    <a href="{{ route('admin.promotions.index') }}" class="btn btn-secondary" style="text-decoration:none;">&larr; Kembali</a>
</div>

<div class="pr-kpis" style="margin-bottom:20px;">
    <div class="pr-kpi"><div class="pr-kpi-label">Impressions</div><div class="pr-kpi-value">{{ number_format($stats['impressions'], 0, ',', '.') }}</div></div>
    <div class="pr-kpi"><div class="pr-kpi-label">Klik</div><div class="pr-kpi-value">{{ number_format($stats['clicks'], 0, ',', '.') }}</div></div>
    <div class="pr-kpi"><div class="pr-kpi-label">CTR</div><div class="pr-kpi-value">{{ number_format($stats['ctr'], 1, ',', '.') }}%</div></div>
    <div class="pr-kpi"><div class="pr-kpi-label">Lamaran dari promosi</div><div class="pr-kpi-value">{{ $stats['applications'] }}</div></div>
    <div class="pr-kpi accent"><div class="pr-kpi-label">Sisa waktu</div><div class="pr-kpi-value" style="font-size:20px;">{{ $promotion->isLive() ? $promotion->remaining_label : '—' }}</div></div>
</div>

<div class="pr-checkout">
    <div class="pr-wrap">
        <section class="pr-card">
            <div class="pr-card-head"><div><h2>Transaksi</h2><p>Hanya-baca. Status pembayaran berasal dari gateway/simulasi, bukan input Admin.</p></div></div>
            <div class="pr-card-body">
                <dl class="pr-lines">
                    <div><dt>No. transaksi</dt><dd style="font-family:monospace;">{{ $trx?->transaction_number }}</dd></div>
                    <div><dt>Nominal</dt><dd>{{ $trx?->formatted_amount }}</dd></div>
                    <div><dt>Status</dt><dd><span class="tdg-pill {{ $trx?->status_tone }}">{{ $trx?->status_label }}</span></dd></div>
                    <div><dt>Metode</dt><dd>{{ $trx?->payment_method_label }}</dd></div>
                    <div><dt>Referensi</dt><dd style="font-family:monospace; font-size:12px;">{{ $trx?->payment_reference ?? '—' }}</dd></div>
                    <div><dt>Dibuat</dt><dd>{{ \App\Support\Wib::format($trx?->created_at) }}</dd></div>
                    <div><dt>Dibayar</dt><dd>{{ \App\Support\Wib::format($trx?->paid_at) }}</dd></div>
                    @if($trx?->failure_reason)<div><dt>Keterangan</dt><dd style="max-width:260px;">{{ $trx->failure_reason }}</dd></div>@endif
                </dl>
            </div>
        </section>
        <section class="pr-card">
            <div class="pr-card-head"><div><h2>Penayangan</h2></div></div>
            <div class="pr-card-body">
                <dl class="pr-lines">
                    <div><dt>Paket (snapshot)</dt><dd>{{ $promotion->package_name }} · {{ $promotion->formatted_price }} · {{ $promotion->duration_hours }} jam</dd></div>
                    <div><dt>Aktif</dt><dd>{{ \App\Support\Wib::format($promotion->start_at) }}</dd></div>
                    <div><dt>Berakhir</dt><dd>{{ \App\Support\Wib::format($promotion->end_at) }}</dd></div>
                    @if($promotion->stopped_at)
                        <div><dt>Dihentikan</dt><dd>{{ \App\Support\Wib::format($promotion->stopped_at) }} oleh {{ $promotion->stopper?->name ?? 'Sistem' }}</dd></div>
                        <div><dt>Alasan</dt><dd style="max-width:260px;">{{ $promotion->stop_reason }}</dd></div>
                        <div><dt>Sisa waktu saat dihentikan</dt>@php $rs = $promotion->remaining_seconds_at_stop; @endphp
                        <dd>{{ $rs !== null ? intdiv($rs, 3600) . ' jam ' . intdiv($rs % 3600, 60) . ' menit' : '—' }}</dd></div>
                    @endif
                </dl>
            </div>
        </section>
    </div>

    <div class="pr-wrap">
        <section class="pr-card">
            <div class="pr-card-head"><div><h2>Tindakan Admin</h2></div></div>
            <div class="pr-card-body">
                @if($promotion->isLive())
                    <form method="POST" action="{{ route('admin.promotions.stop', $promotion) }}" style="display:grid; gap:10px;">
                        @csrf
                        <label for="stop_reason" style="font-size:12.5px; font-weight:700; color:var(--navy);">Alasan penghentian (dicatat di audit log)</label>
                        <textarea id="stop_reason" name="stop_reason" required minlength="5" maxlength="500" rows="3" style="border:1px solid var(--line); border-radius:10px; padding:10px 12px; font:inherit; font-size:13px;" placeholder="Mis. konten proyek melanggar ketentuan.">{{ old('stop_reason') }}</textarea>
                        <button type="submit" class="btn btn-danger">Hentikan Promosi</button>
                        <small style="color:var(--muted); font-size:11.5px;">Penghentian tidak mengubah status pembayaran dan tidak otomatis mengembalikan dana.</small>
                    </form>
                @else
                    <p style="font-size:13px; color:var(--muted); margin:0;">Tidak ada tindakan untuk status "{{ $promotion->status_label }}".</p>
                @endif
            </div>
        </section>
        <section class="pr-card">
            <div class="pr-card-head"><div><h2>Audit log</h2></div></div>
            <div class="pr-card-body">
                <ul class="pr-timeline">
                    @forelse($promotion->auditLogs as $log)
                        <li class="{{ in_array($log->action, ['payment_failed', 'stopped_admin', 'stopped_system', 'cancelled']) ? 'bad' : (in_array($log->action, ['ended', 'payment_expired']) ? 'muted' : '') }}">
                            <div><strong>{{ $log->action_label }}</strong>@if($log->details) — {{ $log->details }}@endif<small>{{ \App\Support\Wib::format($log->created_at) }} · {{ $log->user?->name ?? 'Sistem' }}</small></div>
                        </li>
                    @empty
                        <li class="muted"><div>Belum ada catatan.</div></li>
                    @endforelse
                </ul>
            </div>
        </section>
    </div>
</div>
@endsection
