@extends('layouts.admin')

@section('title', 'Manajemen Promosi')
@section('header', 'Sponsored Project')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/promotion.css') }}?v=2">
@endpush

@section('content')
<div class="page-head">
    <h1>Manajemen Promosi</h1>
    <p>Pantau transaksi dan penayangan Sponsored Project. Admin dapat menghentikan promosi yang melanggar kebijakan; status pembayaran tidak dapat diubah manual.</p>
</div>

<div class="pr-kpis" style="margin-bottom:22px;">
    <div class="pr-kpi accent"><div class="pr-kpi-label">Promosi aktif</div><div class="pr-kpi-value">{{ $summary['live'] }}</div><div class="pr-kpi-sub">sedang tayang</div></div>
    <div class="pr-kpi"><div class="pr-kpi-label">Pendapatan bersih</div><div class="pr-kpi-value">Rp{{ number_format($summary["revenue"], 0, ",", ".") }}</div><div class="pr-kpi-sub">{{ $summary["paid_count"] }} pembayaran nyata disetujui · {{ $summary["simulated"] }} simulasi tidak dihitung</div></div>
    <div class="pr-kpi"><div class="pr-kpi-label">Impressions</div><div class="pr-kpi-value">{{ number_format($summary['impressions'], 0, ',', '.') }}</div><div class="pr-kpi-sub">seluruh promosi</div></div>
    <div class="pr-kpi"><div class="pr-kpi-label">Klik · CTR</div><div class="pr-kpi-value">{{ number_format($summary['clicks'], 0, ',', '.') }}</div><div class="pr-kpi-sub">CTR {{ number_format($summary['ctr'], 1, ',', '.') }}%</div></div>
    <a href="{{ route('admin.payments.index') }}" class="pr-kpi" style="text-decoration:none;"><div class="pr-kpi-label">Perlu verifikasi</div><div class="pr-kpi-value">{{ $summary["pending"] }}</div><div class="pr-kpi-sub">buka Verifikasi Pembayaran &rarr;</div></a>
</div>

<form method="GET" action="{{ route('admin.promotions.index') }}" class="management-toolbar">
    <input type="hidden" name="tab" value="{{ $tab }}">
    <div class="filter-pills">
        <a href="{{ route('admin.promotions.index', ['tab' => 'promosi']) }}" class="{{ $tab === 'promosi' ? 'active' : '' }}">Promosi</a>
        <a href="{{ route('admin.promotions.index', ['tab' => 'transaksi']) }}" class="{{ $tab === 'transaksi' ? 'active' : '' }}">Transaksi</a>
    </div>
    <div style="display:flex; gap:10px; flex-wrap:wrap; flex:1; justify-content:flex-end;">
        <select name="status" onchange="this.form.submit()" aria-label="Filter status">
            <option value="">Semua status</option>
            @if($tab === 'promosi')
                @foreach(['active' => 'Aktif (tayang)', 'pending_payment' => 'Menunggu Pembayaran', 'ended' => 'Berakhir', 'stopped' => 'Dihentikan', 'cancelled' => 'Dibatalkan'] as $k => $l)
                    <option value="{{ $k }}" {{ $status === $k ? 'selected' : '' }}>{{ $l }}</option>
                @endforeach
            @else
                @foreach(['pending' => 'Pending', 'paid' => 'Paid', 'failed' => 'Failed', 'expired' => 'Expired', 'refunded' => 'Refunded'] as $k => $l)
                    <option value="{{ $k }}" {{ $status === $k ? 'selected' : '' }}>{{ $l }}</option>
                @endforeach
            @endif
        </select>
        <div class="searchbox" style="min-width:240px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            <input type="text" name="search" value="{{ $search }}" placeholder="{{ $tab === 'promosi' ? 'Cari proyek / penyedia…' : 'Cari no. transaksi / penyedia…' }}" aria-label="Cari">
        </div>
    </div>
</form>

<div class="table-card">
    @if($tab === 'promosi')
        @if($promotions->isEmpty())
            <div class="pr-empty"><strong>Belum ada promosi</strong>Promosi yang dibeli Penyedia akan tampil di sini.</div>
        @else
            <div class="pr-table-wrap">
                <table class="pr-table">
                    <thead><tr><th>Proyek</th><th>Penyedia</th><th>Paket</th><th>Pembayaran</th><th>Aktif – Berakhir</th><th>Status</th><th class="num">Impr.</th><th class="num">Klik</th><th class="num">Lamaran</th><th></th></tr></thead>
                    <tbody>
                        @foreach($promotions as $promo)
                            <tr>
                                <td><div class="pr-proj-cell"><img src="{{ $promo->project?->cover_url }}" alt=""><b>{{ $promo->project?->title ?? 'Proyek dihapus' }}</b></div></td>
                                <td><div style="font-weight:600;">{{ $promo->user?->name }}</div><div class="muted" style="font-size:11.5px;">{{ $promo->user?->email }}</div></td>
                                <td style="white-space:nowrap;">{{ $promo->package_name }}<div class="muted" style="font-size:11.5px;">{{ $promo->formatted_price }} · {{ $promo->duration_hours }} jam</div></td>
                                <td><span class="tdg-pill {{ $promo->transaction?->status_tone }}">{{ $promo->transaction?->status_label }}</span></td>
                                <td style="white-space:nowrap; font-size:12px;">{{ $promo->start_at ? \App\Support\Wib::format($promo->start_at) : '—' }}<br><span class="muted">{{ $promo->end_at ? 's/d ' . \App\Support\Wib::format($promo->end_at) : '' }}</span></td>
                                <td><span class="tdg-pill {{ $promo->status_tone }}">{{ $promo->status_label }}</span></td>
                                <td class="num">{{ number_format($promo->impressions_total, 0, ',', '.') }}</td>
                                <td class="num">{{ number_format($promo->clicks_total, 0, ',', '.') }}</td>
                                <td class="num">{{ $promo->applications_total }}</td>
                                <td><a href="{{ route('admin.promotions.show', $promo) }}" class="btn btn-secondary" style="padding:6px 12px; font-size:12px; text-decoration:none;">Detail</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($promotions->hasPages())<div style="padding:14px 18px; border-top:1px solid var(--line);">{{ $promotions->links() }}</div>@endif
        @endif
    @else
        @if($transactions->isEmpty())
            <div class="pr-empty"><strong>Belum ada transaksi</strong>Transaksi checkout Sponsored Project akan tampil di sini.</div>
        @else
            <div class="pr-table-wrap">
                <table class="pr-table">
                    <thead><tr><th>No. Transaksi</th><th>Penyedia</th><th>Proyek</th><th class="num">Nominal</th><th>Metode</th><th>Status</th><th>Dibuat</th><th>Dibayar</th><th>Referensi</th></tr></thead>
                    <tbody>
                        @foreach($transactions as $trx)
                            <tr>
                                <td style="font-family:monospace; font-size:12px; white-space:nowrap;">{{ $trx->transaction_number }}</td>
                                <td>{{ $trx->user?->name }}</td>
                                <td>{{ $trx->promotion?->project?->title ?? '—' }}</td>
                                <td class="num">{{ $trx->formatted_amount }}@if($trx->refunded_amount)<div class="muted" style="font-size:11px;">refund Rp{{ number_format($trx->refunded_amount, 0, ',', '.') }}</div>@endif</td>
                                <td style="font-size:12px;">{{ $trx->payment_method_label }}</td>
                                <td><span class="tdg-pill {{ $trx->status_tone }}">{{ $trx->status_label }}</span>@if($trx->failure_reason)<div class="muted" style="font-size:11px; max-width:200px;">{{ $trx->failure_reason }}</div>@endif</td>
                                <td style="white-space:nowrap; font-size:12px;">{{ \App\Support\Wib::format($trx->created_at) }}</td>
                                <td style="white-space:nowrap; font-size:12px;">{{ \App\Support\Wib::format($trx->paid_at) }}</td>
                                <td style="font-family:monospace; font-size:11.5px;" class="muted">{{ $trx->payment_reference ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($transactions->hasPages())<div style="padding:14px 18px; border-top:1px solid var(--line);">{{ $transactions->links() }}</div>@endif
        @endif
    @endif
</div>
@endsection
