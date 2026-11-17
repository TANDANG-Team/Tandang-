@extends('layouts.penyedia')

@section('title', 'Promosi Berhasil')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/promotion.css') }}?v=3">
@endpush

@section('content')
<div class="pr-wrap" style="max-width:760px; margin:0 auto;">
    <section class="pr-card pr-status-big" style="padding:36px 24px;">
        <div class="icon ok"><svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg></div>
        <h2>Promosi Berhasil Diaktifkan</h2>
        <p>
            <strong>{{ $promotion->project?->title }}</strong> kini tampil sebagai Sponsored Project di halaman Cari Proyek
            sampai <strong>{{ \App\Support\Wib::format($promotion->end_at) }}</strong>.
        </p>
        @if($transaction->is_simulation)
            <span class="tdg-pill warning">Transaksi simulasi · Development Mode · tidak dihitung sebagai pendapatan</span>
        @endif
        <div style="display:flex; gap:10px; flex-wrap:wrap; justify-content:center; margin-top:6px;">
            <a href="{{ route('penyedia.promotions.stats', $promotion) }}" class="tdg-btn primary">Lihat Statistik Promosi</a>
            <a href="{{ route('penyedia.promotions.index') }}" class="tdg-btn ghost">Kembali ke Promosi Proyek</a>
        </div>
    </section>

    <section class="pr-card">
        <div class="pr-card-head"><div><h2>Ringkasan</h2></div></div>
        <div class="pr-card-body">
            <dl class="pay-info">
                <div><dt>No. Transaksi</dt><dd style="font-family:ui-monospace,monospace; font-size:13px;">{{ $transaction->transaction_number }}</dd></div>
                <div><dt>Nominal</dt><dd>{{ $transaction->formatted_amount }}</dd></div>
                <div><dt>Status</dt><dd>{{ $transaction->status_label }}</dd></div>
                <div><dt>Mulai tayang</dt><dd>{{ \App\Support\Wib::format($promotion->start_at) }}</dd></div>
                <div><dt>Berakhir</dt><dd>{{ \App\Support\Wib::format($promotion->end_at) }}</dd></div>
                <div><dt>Durasi</dt><dd>{{ $promotion->duration_hours }} jam (4 hari)</dd></div>
            </dl>
        </div>
    </section>
</div>
@endsection
