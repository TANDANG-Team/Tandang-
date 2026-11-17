@extends('layouts.penyedia')

@section('title', 'Checkout Promosi')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/promotion.css') }}?v=2">
@endpush

@section('content')
<div class="page-header">
    <div class="page-title-box">
        <h1>Checkout Sponsored Project</h1>
        <p>Periksa pratinjau dan ringkasan sebelum membayar.</p>
    </div>
    <a href="{{ route('penyedia.promotions.index') }}" class="btn-secondary-action">&larr; Promosi Proyek</a>
</div>

@if($blocker)
    <div class="alert-box alert-error" role="alert">
        <span>Proyek ini belum bisa dipromosikan: <strong>{{ $blocker }}</strong>.</span>
        @if($pending)
            <a href="{{ route('penyedia.promotions.payment', $pending) }}" class="tdg-btn amber sm" style="margin-left:auto;">Lanjutkan pembayaran</a>
        @endif
    </div>
@endif

<div class="pr-checkout">
    <div class="pr-wrap">
        <section class="pr-card">
            <div class="pr-card-head">
                <div><h2>Pratinjau di halaman Cari Proyek</h2><p>Begini kartu proyekmu tampil untuk Talenta saat relevan dengan pencarian mereka.</p></div>
            </div>
            <div class="pr-card-body">
                <div class="pr-preview-frame">
                    <div class="pr-preview-label">Pratinjau · Desktop</div>
                    @include('partials.sponsored-feature', ['project' => $project, 'preview' => true])
                </div>
                <p style="font-size:12px; color:#64748b; margin:12px 0 0;">Tip: tambahkan gambar sampul di <a href="{{ route('penyedia.projects.edit', $project) }}" style="color:#0c7566; font-weight:700;">Edit Proyek</a> agar kartu lebih menarik.</p>
            </div>
        </section>

        <section class="pr-card">
            <div class="pr-card-head"><div><h2>Kebijakan promosi</h2></div></div>
            <div class="pr-card-body">
                <ul class="pr-policy">
                    <li>Masa tayang tepat <strong>{{ $package->duration_hours }} jam ({{ $package->duration_label }})</strong> sejak pembayaran diverifikasi Admin. Checkout berlaku 5 menit. Waktu ditampilkan dalam WIB.</li>
                    <li>Kartu Sponsored hanya muncul bila proyek relevan dengan pencarian/filter Talenta. Urutan hasil pencarian tetap mengikuti relevansi.</li>
                    <li>Penayangan berhenti otomatis bila proyek ditutup, kuota Talenta terpenuhi, dihapus, atau diturunkan Admin karena melanggar kebijakan.</li>
                    <li>Sisa waktu saat dihentikan dicatat untuk audit. <strong>Pengembalian dana tidak otomatis</strong> dan belum didukung pada tahap ini.</li>
                    <li>Promosi tidak memengaruhi rating maupun skor kecocokan Talenta, dan tidak menjamin jumlah pelamar.</li>
                </ul>
            </div>
        </section>
    </div>

    <aside class="pr-card pr-summary">
        <div class="pr-card-head"><div><h2>Ringkasan Checkout</h2></div></div>
        <div class="pr-card-body">
            <dl class="pr-lines">
                <div><dt>Nama Paket</dt><dd>{{ $package->name }}</dd></div>
                <div><dt>Proyek</dt><dd style="max-width:180px; overflow-wrap:anywhere;">{{ $project->title }}</dd></div>
                <div><dt>Durasi</dt><dd>{{ $package->duration_label }} ({{ $package->duration_hours }} jam)</dd></div>
                <div><dt>Harga</dt><dd>{{ $package->formatted_price }}</dd></div>
                <div><dt>Jumlah Proyek</dt><dd>1</dd></div>
            </dl>
            <div class="pr-total"><span>Total Pembayaran</span><b>{{ $package->formatted_price }}</b></div>

            <form method="POST" action="{{ route('penyedia.promotions.store', $project) }}" style="margin-top:18px; display:grid; gap:12px;">
                @csrf
                <label style="display:flex; gap:8px; align-items:flex-start; font-size:12.5px; color:#334155; line-height:1.5;">
                    <input type="checkbox" name="agree" value="1" required style="margin-top:3px; accent-color:#0fa48f;">
                    Saya memahami bahwa promosi meningkatkan visibilitas, bukan jaminan pelamar, dan menyetujui kebijakan di samping.
                </label>
                <button type="submit" class="tdg-btn primary block" {{ $blocker ? 'disabled' : '' }}>
                    Bayar {{ $package->formatted_price }} dengan QRIS
                </button>
                <p style="font-size:11.5px; color:#94a3b8; margin:0; text-align:center;">Harga ditetapkan oleh sistem. Selesaikan pembayaran dalam {{ intdiv(config('promotion.payment_window_seconds'), 60) }} menit setelah checkout. Promosi aktif setelah pembayaran diverifikasi.</p>
            </form>
        </div>
    </aside>
</div>
@endsection
