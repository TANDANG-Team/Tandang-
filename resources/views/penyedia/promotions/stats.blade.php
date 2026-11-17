@extends('layouts.penyedia')

@section('title', 'Statistik Promosi')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/promotion.css') }}?v=2">
@endpush

@section('content')
@php
    $project = $promotion->project;
    $hasData = $stats['impressions'] > 0 || $stats['clicks'] > 0 || $stats['applications'] > 0;
    $stateLabel = ['done' => 'Selesai', 'running' => 'Berjalan', 'upcoming' => 'Belum berjalan', 'stopped' => 'Dihentikan'];
@endphp

<div class="page-header">
    <div class="page-title-box">
        <h1>Statistik Promosi</h1>
        <p>{{ $project?->title ?? 'Proyek dihapus' }} · <span class="tdg-pill {{ $promotion->status_tone }}">{{ $promotion->status_label }}</span></p>
    </div>
    <div style="display:flex; gap:8px; flex-wrap:wrap;">
        <a href="{{ route('penyedia.promotions.history') }}" class="btn-secondary-action">Riwayat Promosi</a>
        @if($canRepeat)
            <a href="{{ route('penyedia.promotions.checkout', $project) }}" class="btn-primary-action">Promosikan Lagi</a>
        @endif
    </div>
</div>

<div class="pr-wrap">
    @if($promotion->isSuppressed())
        <div class="pr-note">Promosi masih dalam masa tayang, tetapi kartu tidak ditampilkan karena proyek tidak lagi memenuhi syarat (ditutup, penuh, atau dihapus). Penayangan akan dihentikan otomatis.</div>
    @elseif($promotion->isPendingPayment())
        <div class="pr-note">Promosi belum aktif karena pembayaran belum selesai. <a href="{{ route('penyedia.promotions.payment', $promotion) }}" style="color:#8a5300; font-weight:800;">Lanjutkan pembayaran &rarr;</a></div>
    @endif

    {{-- KPI --}}
    <div class="pr-kpis">
        <div class="pr-kpi">
            <div class="pr-kpi-label"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>Impressions</div>
            <div class="pr-kpi-value">{{ number_format($stats['impressions'], 0, ',', '.') }}</div>
            <div class="pr-kpi-sub">kartu ≥50% terlihat ≥1 detik</div>
        </div>
        <div class="pr-kpi">
            <div class="pr-kpi-label"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 9 5 12 1.8-5.2L21 14Z"/></svg>Klik Proyek</div>
            <div class="pr-kpi-value">{{ number_format($stats['clicks'], 0, ',', '.') }}</div>
            <div class="pr-kpi-sub">menuju detail proyek</div>
        </div>
        <div class="pr-kpi">
            <div class="pr-kpi-label"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 5 5 19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg>CTR</div>
            <div class="pr-kpi-value">{{ number_format($stats['ctr'], 1, ',', '.') }}%</div>
            <div class="pr-kpi-sub">klik ÷ impressions × 100</div>
        </div>
        <div class="pr-kpi">
            <div class="pr-kpi-label"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><path d="M14 2v6h6M9 15l2 2 4-4"/></svg>Lamaran dari Promosi</div>
            <div class="pr-kpi-value">{{ $stats['applications'] }}</div>
            <div class="pr-kpi-sub">≤{{ config('promotion.attribution_hours') }} jam setelah klik promosi</div>
        </div>
        <div class="pr-kpi accent">
            <div class="pr-kpi-label"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>Sisa Masa Promosi</div>
            <div class="pr-kpi-value" style="font-size:20px;">{{ $promotion->isLive() ? $promotion->remaining_label : '—' }}</div>
            <div class="pr-kpi-sub">{{ $promotion->end_at ? 'berakhir ' . \App\Support\Wib::format($promotion->end_at) : 'belum aktif' }}</div>
        </div>
    </div>

    {{-- Grafik harian --}}
    <section class="pr-card">
        <div class="pr-card-head">
            <div>
                <h2>Performa Promosi Harian</h2>
                <p>Hari ke-N = 24 jam ke-N sejak aktivasi{{ $promotion->start_at ? ' (' . \App\Support\Wib::format($promotion->start_at) . ')' : '' }}. Arahkan kursor ke batang untuk detail.</p>
            </div>
        </div>
        <div class="pr-card-body">
            @if(! $promotion->start_at)
                <div class="pr-empty"><strong>Belum ada data</strong>Statistik mulai tercatat setelah pembayaran berhasil dan promosi tayang.</div>
            @else
                @unless($hasData)
                    <div class="pr-note teal" style="margin-bottom:16px;">Belum ada event tercatat. Data muncul ketika Talenta melihat atau mengklik kartu promosimu di halaman Cari Proyek.</div>
                @endunless
                <div class="pr-charts">
                    @include('penyedia.promotions._daily-chart', ['days' => $stats['days'], 'key' => 'impressions', 'color' => '#0fa48f', 'title' => 'Impressions', 'subtitle' => 'Jumlah tayangan kartu per hari'])
                    @include('penyedia.promotions._daily-chart', ['days' => $stats['days'], 'key' => 'clicks', 'color' => '#e8930c', 'title' => 'Klik', 'subtitle' => 'Jumlah klik ke detail proyek per hari'])
                </div>
            @endif
        </div>

        {{-- Tabel ringkasan (juga sebagai tampilan aksesibel dari grafik) --}}
        <div class="pr-table-wrap" style="border-top:1px solid #f1f5f9;">
            <table class="pr-table">
                <caption style="position:absolute; width:1px; height:1px; overflow:hidden; clip:rect(0 0 0 0);">Ringkasan performa harian</caption>
                <thead>
                    <tr><th>Hari</th><th>Tanggal</th><th>Status</th><th class="num">Impressions</th><th class="num">Klik</th><th class="num">CTR</th><th class="num">Lamaran</th></tr>
                </thead>
                <tbody>
                    @foreach($stats['days'] as $d)
                        @php $notRun = in_array($d['state'], ['upcoming', 'stopped'], true); @endphp
                        <tr>
                            <td><strong>{{ $d['label'] }}</strong></td>
                            <td>{{ $d['start'] ? \App\Support\Wib::date($d['start']) : '—' }}</td>
                            <td><span class="pr-day-state {{ $d['state'] }}">{{ $stateLabel[$d['state']] }}</span></td>
                            @if($notRun)
                                <td class="num muted">—</td><td class="num muted">—</td><td class="num muted">—</td><td class="num muted">—</td>
                            @else
                                <td class="num">{{ number_format($d['impressions'], 0, ',', '.') }}</td>
                                <td class="num">{{ number_format($d['clicks'], 0, ',', '.') }}</td>
                                <td class="num">{{ number_format(\App\Models\ProjectPromotion::ctr($d['clicks'], $d['impressions']), 1, ',', '.') }}%</td>
                                <td class="num">{{ $d['applications'] }}</td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <div class="pr-checkout" style="grid-template-columns:minmax(0,1fr) minmax(0,1fr);">
        <section class="pr-card">
            <div class="pr-card-head"><div><h2>Detail kampanye</h2></div></div>
            <div class="pr-card-body">
                <dl class="pr-lines">
                    <div><dt>Paket</dt><dd>{{ $promotion->package_name }} · {{ $promotion->duration_hours }} jam</dd></div>
                    <div><dt>Harga (saat pembelian)</dt><dd>{{ $promotion->formatted_price }}</dd></div>
                    <div><dt>No. transaksi</dt><dd style="font-family:monospace; font-size:12px;">{{ $promotion->transaction?->transaction_number }}</dd></div>
                    <div><dt>Status pembayaran</dt><dd><span class="tdg-pill {{ $promotion->transaction?->status_tone }}">{{ $promotion->transaction?->status_label }}</span></dd></div>
                    <div><dt>Dibeli</dt><dd>{{ \App\Support\Wib::format($promotion->created_at) }}</dd></div>
                    <div><dt>Aktif</dt><dd>{{ \App\Support\Wib::format($promotion->start_at) }}</dd></div>
                    <div><dt>Berakhir</dt><dd>{{ \App\Support\Wib::format($promotion->end_at) }}</dd></div>
                    @if($promotion->stopped_at)
                        <div><dt>Dihentikan</dt><dd>{{ \App\Support\Wib::format($promotion->stopped_at) }}</dd></div>
                        <div><dt>Alasan</dt><dd style="max-width:240px;">{{ $promotion->stop_reason }}</dd></div>
                    @endif
                </dl>
            </div>
        </section>
        <section class="pr-card">
            <div class="pr-card-head"><div><h2>Riwayat aktivitas</h2></div></div>
            <div class="pr-card-body">
                <ul class="pr-timeline">
                    @forelse($promotion->auditLogs as $log)
                        <li class="{{ in_array($log->action, ['payment_failed', 'stopped_admin', 'stopped_system', 'cancelled']) ? 'bad' : (in_array($log->action, ['ended', 'payment_expired']) ? 'muted' : '') }}">
                            <div><strong>{{ $log->action_label }}</strong>@if($log->details) — {{ $log->details }}@endif<small>{{ \App\Support\Wib::format($log->created_at) }}</small></div>
                        </li>
                    @empty
                        <li class="muted"><div>Belum ada aktivitas.</div></li>
                    @endforelse
                </ul>
            </div>
        </section>
    </div>
    <p style="font-size:11.5px; color:#94a3b8; margin:0;">Statistik mencerminkan event yang benar-benar tercatat (impression dihitung sekali per muat halaman per Talenta, klik terdeduplikasi {{ config('promotion.click_dedup_seconds') }} detik). Identitas Talenta tidak ditampilkan.</p>
</div>
@endsection
