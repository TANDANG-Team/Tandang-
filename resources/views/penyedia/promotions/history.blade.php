@extends('layouts.penyedia')

@section('title', 'Riwayat Promosi')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/promotion.css') }}?v=2">
@endpush

@section('content')
<div class="page-header">
    <div class="page-title-box">
        <h1>Riwayat Promosi</h1>
        <p>Semua pembelian Sponsored Project beserta performanya. Statistik tetap tersimpan setelah promosi berakhir.</p>
    </div>
    <a href="{{ route('penyedia.promotions.index') }}" class="btn-primary-action">Promosikan Proyek</a>
</div>

<section class="pr-card" style="overflow:hidden;">
    @if($promotions->isEmpty())
        <div class="pr-empty">
            <strong>Belum ada riwayat promosi</strong>
            Promosi yang kamu beli akan tercatat di sini.
        </div>
    @else
        <div class="pr-table-wrap">
            <table class="pr-table">
                <thead>
                    <tr>
                        <th>Proyek</th>
                        <th>Dibeli</th>
                        <th>Aktif – Berakhir</th>
                        <th class="num">Harga</th>
                        <th>Pembayaran</th>
                        <th>Penayangan</th>
                        <th class="num">Impr.</th>
                        <th class="num">Klik</th>
                        <th class="num">Lamaran</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($promotions as $promo)
                        @php $repeatable = $promo->project && ! $promo->isLive() && ! $promo->isPendingPayment() && $promo->project->promotionBlocker() === null; @endphp
                        <tr>
                            <td>
                                <div class="pr-proj-cell">
                                    <img src="{{ $promo->project?->cover_url }}" alt="">
                                    <div style="min-width:0;"><b>{{ $promo->project?->title ?? 'Proyek dihapus' }}</b></div>
                                </div>
                            </td>
                            <td style="white-space:nowrap;">{{ \App\Support\Wib::format($promo->created_at) }}</td>
                            <td style="white-space:nowrap; font-size:12.5px;">
                                @if($promo->start_at)
                                    {{ \App\Support\Wib::format($promo->start_at) }}<br><span class="muted">s/d {{ \App\Support\Wib::format($promo->stopped_at ?? $promo->end_at) }}</span>
                                @else
                                    <span class="muted">—</span>
                                @endif
                            </td>
                            <td class="num">{{ $promo->formatted_price }}</td>
                            <td><span class="tdg-pill {{ $promo->transaction?->status_tone }}">{{ $promo->transaction?->status_label }}</span></td>
                            <td><span class="tdg-pill {{ $promo->status_tone }}">{{ $promo->status_label }}</span></td>
                            <td class="num">{{ number_format($promo->impressions_total, 0, ',', '.') }}</td>
                            <td class="num">{{ number_format($promo->clicks_total, 0, ',', '.') }}</td>
                            <td class="num">{{ $promo->applications_total }}</td>
                            <td style="white-space:nowrap; text-align:right;">
                                @if($promo->isPendingPayment() && $promo->transaction?->isPending() && ! $promo->transaction->isPaymentWindowOver())
                                    <a href="{{ route('penyedia.promotions.payment', $promo) }}" class="tdg-btn amber sm">Bayar</a>
                                @elseif($promo->start_at)
                                    <a href="{{ route('penyedia.promotions.stats', $promo) }}" class="tdg-btn ghost sm">Lihat Statistik</a>
                                @endif
                                @if($repeatable)
                                    <a href="{{ route('penyedia.promotions.checkout', $promo->project) }}" class="tdg-btn primary sm">Promosikan Lagi</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($promotions->hasPages())
            <div style="padding:14px 18px; border-top:1px solid #eef2f6;">{{ $promotions->links() }}</div>
        @endif
    @endif
</section>
@endsection
