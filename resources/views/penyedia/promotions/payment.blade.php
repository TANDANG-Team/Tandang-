@extends('layouts.penyedia')

@section('title', 'Pembayaran Promosi')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/promotion.css') }}?v=3">
@endpush

@section('content')
@php
    $trx = $transaction;
    $windowOver = $trx?->isPaymentWindowOver();
    $state = match (true) {
        $trx === null                    => 'missing',
        $trx->isPending() && ! $windowOver => 'pending',
        $trx->isAwaitingVerification()   => 'awaiting',
        $trx->isLateReview()             => 'late_review',
        $trx->status === 'rejected'      => 'rejected',
        $trx->status === 'failed'        => 'failed',
        default                          => 'expired',
    };
    $amount = $trx?->formatted_amount;
    $canRetry = $promotion->project && app(\App\Services\PromotionEligibilityService::class)->blockerFor(auth()->user(), $promotion->project) === null;
    $blocked = $state === 'pending' && ! empty($providerBlocker);
@endphp

<div class="page-header">
    <div class="page-title-box">
        <h1>Pembayaran Sponsored Project</h1>
        <p>{{ $promotion->project?->title }}</p>
    </div>
    <a href="{{ route('penyedia.promotions.index') }}" class="btn-secondary-action">&larr; Promosi Proyek</a>
</div>

<div class="pay-grid">
    {{-- ═════════ KOLOM KIRI (±60%): instruksi & informasi transaksi ═════════ --}}
    <div class="pr-wrap">
        <section class="pr-card">
            <div class="pr-card-head">
                <div><h2>Informasi Transaksi</h2></div>
                <span class="tdg-pill {{ $trx?->status_tone }}">{{ $trx?->status_label }}</span>
            </div>
            <div class="pr-card-body">
                <dl class="pay-info">
                    <div><dt>No. Transaksi</dt><dd style="font-family:ui-monospace,monospace; font-size:13px;">{{ $trx?->transaction_number }}</dd></div>
                    <div><dt>Total Pembayaran</dt><dd>{{ $amount }}</dd></div>
                    <div><dt>Paket</dt><dd>{{ $promotion->package_name }} · 4 hari</dd></div>
                    <div><dt>Checkout dibuat</dt><dd>{{ \App\Support\Wib::format($trx?->created_at) }}</dd></div>
                    <div><dt>Batas pembayaran</dt><dd>{{ \App\Support\Wib::format($trx?->expires_at) }}</dd></div>
                    <div><dt>Metode</dt><dd>{{ $trx?->payment_method_label }}</dd></div>
                </dl>
            </div>
        </section>

        @if($state === 'pending' && ! $blocked)
            <section class="pr-card">
                <div class="pr-card-head"><div><h2>Cara Pembayaran</h2></div></div>
                <div class="pr-card-body">
                    <ol class="pay-steps">
                        <li class="pay-step"><span class="pay-step-num">1</span><div class="pay-step-text">Buka aplikasi mobile banking atau e-wallet yang mendukung QRIS.</div></li>
                        <li class="pay-step"><span class="pay-step-num">2</span><div class="pay-step-text">Pilih menu <strong>Scan QRIS</strong>, lalu pindai kode QR yang ditampilkan.</div></li>
                        <li class="pay-step"><span class="pay-step-num">3</span><div class="pay-step-text">Periksa identitas merchant dan masukkan nominal <strong>{{ $amount }}</strong> apabila diminta.</div></li>
                        <li class="pay-step"><span class="pay-step-num">4</span><div class="pay-step-text">Selesaikan pembayaran dan simpan bukti transaksi.</div></li>
                        <li class="pay-step"><span class="pay-step-num">5</span><div class="pay-step-text">Tunggu verifikasi pembayaran sebelum promosi diaktifkan.</div></li>
                    </ol>
                </div>
            </section>
        @endif

        @if($blocked)
            <section class="pr-card" role="alert" style="border-color:#fde68a; background:#fffbeb;">
                <div class="pr-card-body">
                    <strong style="color:#92400e;">Pembayaran tidak dapat dilanjutkan</strong>
                    <p style="margin:6px 0 0; color:#92400e; font-size:13px; line-height:1.55;">{{ $providerBlocker }}</p>
                    <p style="margin:6px 0 0; color:#92400e; font-size:12.5px;">Jangan melakukan pembayaran. Jika Anda <strong>sudah terlanjur membayar</strong>, kirim bukti di bawah — transaksi akan ditandai untuk ditangani Admin (pengembalian dana atau keputusan lain), tidak diaktifkan otomatis.</p>
                </div>
            </section>
        @endif

        {{-- Konfirmasi "Saya Sudah Bayar" --}}
        @if($state === 'pending' && $qrisReady)
            <section class="pr-card" id="konfirmasi">
                <div class="pr-card-head">
                    <div><h2>Saya Sudah Bayar</h2><p>Kirim bukti sebelum waktu habis. Status menjadi Menunggu Verifikasi — promosi aktif setelah Admin memastikan dana masuk.</p></div>
                </div>
                <div class="pr-card-body">
                    @include('penyedia.promotions._proof-form', ['late' => false])
                </div>
            </section>
        @endif

        @if($state === 'awaiting')
            <section class="pr-card pay-state">
                <div class="icon info"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg></div>
                <div>
                    <h2>Menunggu Verifikasi Admin</h2>
                    <p>Bukti dikirim {{ \App\Support\Wib::format($trx->submitted_at) }}. Admin akan mencocokkan dana yang masuk ke rekening merchant. Status ini <strong>belum berarti pembayaran berhasil</strong>; kamu akan menerima notifikasi setelah ada keputusan.</p>
                    <a href="{{ route('penyedia.promotions.proof', $promotion) }}" target="_blank" rel="noopener" class="tdg-btn ghost sm">Lihat bukti yang dikirim</a>
                </div>
            </section>
        @elseif($state === 'late_review')
            <section class="pr-card pay-state">
                <div class="icon info"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg></div>
                <div>
                    <h2>Konfirmasi Terlambat Sedang Ditinjau</h2>
                    <p>Bukti dikirim setelah batas waktu ({{ \App\Support\Wib::format($trx->submitted_at) }}). Promosi tidak aktif otomatis. Admin akan memeriksa apakah dana benar diterima.</p>
                    <a href="{{ route('penyedia.promotions.proof', $promotion) }}" target="_blank" rel="noopener" class="tdg-btn ghost sm">Lihat bukti yang dikirim</a>
                </div>
            </section>
        @elseif($state === 'expired')
            <section class="pr-card pay-state">
                <div class="icon muted"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg></div>
                <div>
                    <h2>Waktu Pembayaran Habis</h2>
                    <p>Transaksi ini telah kedaluwarsa. Silakan membuat pembayaran baru untuk melanjutkan promosi.</p>
                    @if($canRetry)
                        <a href="{{ route('penyedia.promotions.checkout', $promotion->project) }}" class="tdg-btn primary">Buat Pembayaran Baru</a>
                    @endif
                    @if($qrisReady && ! $trx->is_simulation && ! $trx->submitted_at)
                        <details style="margin-top:16px;">
                            <summary style="cursor:pointer; font-size:13px; font-weight:700; color:#0c7566;">Sudah terlanjur membayar? Kirim konfirmasi terlambat</summary>
                            <div style="margin-top:12px;">@include('penyedia.promotions._proof-form', ['late' => true])</div>
                        </details>
                    @endif
                </div>
            </section>
        @elseif(in_array($state, ['rejected', 'failed'], true))
            <section class="pr-card pay-state">
                <div class="icon bad"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6 6 18M6 6l12 12"/></svg></div>
                <div>
                    <h2>{{ $state === 'rejected' ? 'Pembayaran Ditolak' : 'Pembayaran Gagal' }}</h2>
                    <p>{{ $trx->decision_note ?? $trx->failure_reason ?? 'Pembayaran tidak dapat diproses.' }} Promosi tidak diaktifkan.</p>
                    @if($canRetry)
                        <a href="{{ route('penyedia.promotions.checkout', $promotion->project) }}" class="tdg-btn primary">Buat Pembayaran Baru</a>
                    @endif
                </div>
            </section>
        @endif

        {{-- Simulasi: terpisah, jelas hanya untuk pengujian --}}
        @if($state === 'pending' && $simulationEnabled && ! $blocked)
            <section class="test-card" aria-label="Simulasi pembayaran">
                <span class="test-card-badge">Development Mode · Hanya untuk Pengujian</span>
                <h3>Simulasi Pembayaran</h3>
                <p>Tidak memproses uang dan tidak terhubung dengan QRIS di samping. Transaksi simulasi ditandai khusus dan <strong>tidak dihitung sebagai pendapatan</strong>. Fitur ini otomatis nonaktif di production.</p>
                <div class="test-card-actions">
                    <form method="POST" action="{{ route('penyedia.promotions.simulate.paid', $promotion) }}">
                        @csrf
                        <button type="submit" class="tdg-btn primary" onclick="this.disabled=true; this.form.submit();">Simulasikan Pembayaran Berhasil</button>
                    </form>
                    <form method="POST" action="{{ route('penyedia.promotions.simulate.failed', $promotion) }}">
                        @csrf
                        <button type="submit" class="tdg-btn ghost">Simulasikan Pembayaran Gagal</button>
                    </form>
                </div>
            </section>
        @endif

        @if($state === 'pending')
            <form method="POST" action="{{ route('penyedia.promotions.cancel', $promotion) }}"
                  data-confirm-title="Batalkan Checkout?"
                  data-confirm="Checkout promosi ini akan dibatalkan. Anda bisa membuat checkout baru kapan saja."
                  data-confirm-type="danger"
                  data-confirm-btn="Ya, Batalkan"
                  data-cancel-btn="Kembali">
                @csrf
                <button type="submit" class="tdg-btn danger sm">Batalkan checkout</button>
            </form>
        @endif
    </div>

    {{-- ═════════ KOLOM KANAN (±40%): QRIS, nominal, countdown, paket ═════════ --}}
    <aside class="pay-side">
        <section class="pr-card qris-card">
            <div class="qris-head">
                <b>QRIS</b>
                <span class="tdg-pill muted">Statis</span>
            </div>

            @if($qrisReady)
                <div class="qris-frame {{ $state === 'pending' && ! $blocked ? '' : 'is-void' }}"
                     data-void="{{ $state === 'awaiting' || $state === 'late_review' ? 'Menunggu verifikasi' : 'Tidak berlaku' }}">
                    <img src="{{ asset($qris['image']) }}" alt="Kode QRIS {{ $qris['merchant_name'] }}" width="280" height="280">
                </div>
                <div class="qris-merchant-box">
                    <span>Nama merchant penerima</span>
                    <b>{{ $qris['merchant_name'] }}</b>
                    @if($qris['merchant_nmid'])<span style="margin-top:4px; text-transform:none; letter-spacing:0;">NMID: {{ $qris['merchant_nmid'] }}</span>@endif
                </div>
            @else
                <div class="pay-unconfigured" role="alert">
                    <strong>QRIS merchant belum dikonfigurasi</strong>
                    Pembayaran nyata belum dibuka karena QRIS asli penerima dan nama merchant belum diatur.
                    Admin: simpan gambar QRIS di <code>public/{{ $qris['image'] }}</code> dan isi <code>QRIS_MERCHANT_NAME</code> pada <code>.env</code> sesuai nama yang muncul saat QR dipindai.
                </div>
            @endif

            <div class="qris-amount-v2">{{ $amount }}</div>
            <div class="qris-amount-sub">Masukkan nominal ini secara manual</div>

            @if($state === 'pending')
                <div class="pay-timer" id="payTimer" data-remaining="{{ $remainingSeconds }}" aria-live="polite">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                    <span>Selesaikan pembayaran dalam <b id="payCountdown">{{ sprintf('%02d:%02d', intdiv($remainingSeconds, 60), $remainingSeconds % 60) }}</b></span>
                </div>
            @elseif($state === 'awaiting')
                <div class="pay-timer done">Timer dihentikan · menunggu verifikasi</div>
            @endif

            @if($qrisReady && $state === 'pending' && ! $blocked)
                <div class="pay-warn">
                    <strong>Periksa penerima sebelum membayar.</strong> Pastikan nama merchant di aplikasimu sama dengan <b>{{ $qris['merchant_name'] }}</b> dan nominal <b>{{ $amount }}</b>. Jangan membayar bila nama berbeda.
                </div>
            @endif

            <dl class="pr-lines" style="margin-top:18px; text-align:left; padding-top:14px; border-top:1px dashed #e2e8f0;">
                <div><dt>Paket</dt><dd>{{ $promotion->package_name }}</dd></div>
                <div><dt>Masa tayang</dt><dd>4 × 24 jam setelah diverifikasi</dd></div>
                <div><dt>Jumlah proyek</dt><dd>1</dd></div>
            </dl>
        </section>
    </aside>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const timer = document.getElementById('payTimer');
    if (!timer) return;

    // Sisa detik berasal dari server (dihitung dari expires_at), lalu dihitung mundur
    // relatif terhadap jam browser — refresh tidak mengembalikan timer ke 05:00.
    const deadline = Date.now() + parseInt(timer.dataset.remaining, 10) * 1000;
    const out = document.getElementById('payCountdown');

    function tick() {
        const left = Math.max(0, Math.round((deadline - Date.now()) / 1000));
        out.textContent = String(Math.floor(left / 60)).padStart(2, '0') + ':' + String(left % 60).padStart(2, '0');
        if (left <= 0) {
            timer.classList.add('done');
            timer.querySelector('span').textContent = 'Waktu Pembayaran Habis';
            setTimeout(() => location.reload(), 1200);
            return;
        }
        setTimeout(tick, 1000);
    }
    tick();

    const statusUrl = @json(route('penyedia.promotions.status', $promotion));
    setInterval(() => {
        fetch(statusUrl, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(r => r.ok ? r.json() : null)
            .then(d => { if (d && d.transaction_status && d.transaction_status !== 'pending') location.reload(); })
            .catch(() => {});
    }, 10000);
})();
</script>
@endpush
