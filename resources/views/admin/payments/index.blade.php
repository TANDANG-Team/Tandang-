@extends('layouts.admin')

@section('title', 'Verifikasi Pembayaran')
@section('header', 'Pembayaran QRIS')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/promotion.css') }}?v=3">
@endpush

@section('content')
<div class="page-head">
    <h1>Verifikasi Pembayaran</h1>
    <p>Cocokkan setiap konfirmasi dengan mutasi rekening merchant sebelum menyetujui. Bukti screenshot saja tidak cukup sebagai dasar persetujuan.</p>
</div>

<div class="management-toolbar">
    <div class="filter-pills">
        <a href="{{ route('admin.payments.index') }}" class="{{ $tab === 'antrean' ? 'active' : '' }}">Perlu Diverifikasi <span>{{ $counts['antrean'] }}</span></a>
        <a href="{{ route('admin.payments.index', ['tab' => 'riwayat']) }}" class="{{ $tab === 'riwayat' ? 'active' : '' }}">Riwayat Keputusan</a>
    </div>
    @if($counts['late'] > 0)
        <span class="tdg-pill info">{{ $counts['late'] }} konfirmasi terlambat perlu ditinjau</span>
    @endif
</div>

<div class="table-card">
    @if($transactions->isEmpty())
        <div class="pr-empty">
            <strong>{{ $tab === 'antrean' ? 'Tidak ada pembayaran yang menunggu verifikasi' : 'Belum ada keputusan' }}</strong>
            {{ $tab === 'antrean' ? 'Konfirmasi "Saya Sudah Bayar" dari Penyedia akan muncul di sini.' : 'Pembayaran yang disetujui/ditolak akan tercatat di sini.' }}
        </div>
    @else
        <div class="pr-table-wrap">
            <table class="pr-table">
                <thead>
                    <tr><th>ID Transaksi</th><th>Penyedia</th><th>Proyek</th><th class="num">Tagihan</th><th class="num">Dilaporkan</th><th>Checkout</th><th>Konfirmasi</th><th>Referensi</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    @foreach($transactions as $trx)
                        <tr>
                            <td style="font-family:ui-monospace,monospace; font-size:12px; white-space:nowrap;">{{ $trx->transaction_number }}</td>
                            <td>{{ $trx->user?->name }}<div class="muted" style="font-size:11.5px;">{{ $trx->user?->email }}</div></td>
                            <td>{{ $trx->promotion?->project?->title ?? '—' }}</td>
                            <td class="num">{{ $trx->formatted_amount }}</td>
                            <td class="num" style="{{ (int) $trx->payer_amount !== (int) $trx->amount ? 'color:#dc2626; font-weight:700;' : '' }}">
                                {{ $trx->payer_amount ? 'Rp' . number_format($trx->payer_amount, 0, ',', '.') : '—' }}
                            </td>
                            <td style="white-space:nowrap; font-size:12px;">{{ \App\Support\Wib::format($trx->created_at) }}</td>
                            <td style="white-space:nowrap; font-size:12px;">
                                {{ \App\Support\Wib::format($trx->submitted_at) }}
                                @if($trx->late_submission)<div><span class="tdg-pill warning">Terlambat</span></div>@endif
                                @if($trx->needs_admin_attention)<div><span class="tdg-pill danger" title="{{ $trx->attention_reason }}">Perlu penanganan</span></div>@endif
                            </td>
                            <td style="font-family:ui-monospace,monospace; font-size:11.5px;">{{ $trx->payer_reference ?? '—' }}</td>
                            <td><span class="tdg-pill {{ $trx->status_tone }}">{{ $trx->status_label }}</span>@if($trx->verifier)<div class="muted" style="font-size:11px;">{{ $trx->verifier->name }}</div>@endif</td>
                            <td><a href="{{ route('admin.payments.show', $trx) }}" class="btn btn-secondary" style="padding:6px 12px; font-size:12px; text-decoration:none;">{{ $trx->needsReview() ? 'Periksa' : 'Detail' }}</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($transactions->hasPages())<div style="padding:14px 18px; border-top:1px solid var(--line);">{{ $transactions->links() }}</div>@endif
    @endif
</div>
@endsection
