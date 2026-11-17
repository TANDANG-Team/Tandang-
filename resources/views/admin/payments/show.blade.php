@extends('layouts.admin')

@section('title', 'Periksa Pembayaran')
@section('header', 'Pembayaran QRIS')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/promotion.css') }}?v=3">
@endpush

@section('content')
@php
    $trx = $transaction;
    $isPdf = $trx->proof_path && str_ends_with(strtolower($trx->proof_path), '.pdf');
    $amountMismatch = $trx->payer_amount !== null && (int) $trx->payer_amount !== (int) $trx->amount;
@endphp

<div class="page-head" style="display:flex; justify-content:space-between; gap:16px; flex-wrap:wrap; align-items:flex-end;">
    <div>
        <h1 style="font-family:ui-monospace,monospace; font-size:22px;">{{ $trx->transaction_number }}</h1>
        <p>{{ $trx->user?->name }} · {{ $trx->promotion?->project?->title }} · <span class="tdg-pill {{ $trx->status_tone }}">{{ $trx->status_label }}</span></p>
    </div>
    <a href="{{ route('admin.payments.index') }}" class="btn btn-secondary" style="text-decoration:none;">&larr; Kembali</a>
</div>

<div class="pay-grid">
    <div class="pr-wrap">
        <section class="pr-card">
            <div class="pr-card-head"><div><h2>Data transaksi & konfirmasi</h2><p>Bandingkan dengan mutasi rekening merchant {{ $qris['merchant_name'] ?: '(nama merchant belum dikonfigurasi)' }}.</p></div></div>
            <div class="pr-card-body">
                <dl class="pay-info">
                    <div><dt>Tagihan</dt><dd>{{ $trx->formatted_amount }}</dd></div>
                    <div><dt>Nominal dilaporkan</dt><dd style="{{ $amountMismatch ? 'color:#dc2626;' : '' }}">{{ $trx->payer_amount ? 'Rp' . number_format($trx->payer_amount, 0, ',', '.') : '—' }}{{ $amountMismatch ? ' (berbeda!)' : '' }}</dd></div>
                    <div><dt>Waktu checkout</dt><dd>{{ \App\Support\Wib::format($trx->created_at) }}</dd></div>
                    <div><dt>Batas bayar</dt><dd>{{ \App\Support\Wib::format($trx->expires_at) }}</dd></div>
                    <div><dt>Waktu konfirmasi</dt><dd>{{ \App\Support\Wib::format($trx->submitted_at) }}@if($trx->late_submission) <span class="tdg-pill warning">Terlambat</span>@endif</dd></div>
                    <div><dt>Waktu bayar (klaim)</dt><dd>{{ \App\Support\Wib::format($trx->payer_paid_at) }}</dd></div>
                    <div><dt>Nomor referensi</dt><dd style="font-family:ui-monospace,monospace;">{{ $trx->payer_reference ?? '—' }}</dd></div>
                    <div><dt>Nama pengirim</dt><dd>{{ $trx->payer_name ?? '—' }}</dd></div>
                    <div><dt>Penyedia</dt><dd>{{ $trx->user?->name }}<br><span style="font-weight:500; font-size:12px; color:#64748b;">{{ $trx->user?->email }}</span></dd></div>
                </dl>

                @if($trx->needs_admin_attention || ($trx->needsReview() && ! $standing['eligible']))
                    <div class="pr-note" style="margin-top:16px; background:#fffbeb; border-color:#fde68a; color:#92400e;">
                        <strong>Perlu penanganan Admin:</strong>
                        {{ $trx->attention_reason ?? ('Penyedia saat ini tidak memenuhi syarat Sponsored Project: ' . $standing['message']) }}
                        <div style="margin-top:6px; font-size:12px;">
                            Rating usaha saat ini: <b>{{ $standing['raw_average'] !== null ? number_format($standing['raw_average'], 2, ',', '') . '/5' : 'Belum Ada Penilaian' }}</b>
                            · {{ $standing['count'] }} penilaian valid (syarat ≥ {{ number_format($standing['min_rating'], 1, ',', '') }} & ≥ {{ $standing['min_reviews'] }} penilaian).
                            Bila dana sudah diterima, pilih: tolak dengan catatan pengembalian dana, atau setujui dengan alasan kebijakan.
                        </div>
                    </div>
                @endif

                @if($duplicates->isNotEmpty())
                    <div class="pr-note" style="margin-top:16px; background:#fef2f2; border-color:#fecaca; color:#991b1b;">
                        <strong>Peringatan duplikasi:</strong> bukti/nomor referensi yang sama juga ada pada
                        @foreach($duplicates as $d){{ $d->transaction_number }} ({{ $d->status }})@if(! $loop->last), @endif @endforeach.
                    </div>
                @endif
            </div>
        </section>

        <section class="pr-card">
            <div class="pr-card-head">
                <div><h2>Bukti pembayaran</h2><p>Disimpan privat. Gunakan hanya sebagai pendukung pencocokan mutasi.</p></div>
                @if($trx->proof_path)<a href="{{ route('admin.payments.proof', $trx) }}" target="_blank" rel="noopener" class="btn btn-secondary" style="text-decoration:none; padding:6px 12px; font-size:12px;">Buka di tab baru</a>@endif
            </div>
            <div class="pr-card-body">
                @if(! $trx->proof_path)
                    <div class="pr-empty" style="padding:20px;">Belum ada bukti.</div>
                @elseif($isPdf)
                    <p style="font-size:13px; color:#475569; margin:0;">Bukti berupa PDF. Klik "Buka di tab baru" untuk melihat.</p>
                @else
                    <img src="{{ route('admin.payments.proof', $trx) }}" alt="Bukti pembayaran {{ $trx->transaction_number }}" style="max-width:100%; max-height:520px; object-fit:contain; border:1px solid var(--line); border-radius:12px; display:block; margin:0 auto;">
                @endif
            </div>
        </section>
    </div>

    <div class="pr-wrap">
        <section class="pr-card">
            <div class="pr-card-head"><div><h2>Keputusan</h2></div></div>
            <div class="pr-card-body">
                @if($trx->needsReview())
                    <form method="POST" action="{{ route('admin.payments.approve', $trx) }}" style="display:grid; gap:10px; padding-bottom:18px; border-bottom:1px solid var(--line);">
                        @csrf
                        <label style="display:flex; gap:8px; align-items:flex-start; font-size:13px; color:#334155; line-height:1.5;">
                            <input type="checkbox" name="confirm_received" value="1" required style="margin-top:3px; accent-color:#0fa48f;">
                            Saya sudah memeriksa mutasi rekening merchant dan dana <strong>{{ $trx->formatted_amount }}</strong> untuk transaksi ini benar-benar diterima.
                        </label>
                        @if(! $standing['eligible'])
                            <label style="display:flex; gap:8px; align-items:flex-start; font-size:12.5px; color:#92400e; line-height:1.5; background:#fffbeb; border:1px solid #fde68a; border-radius:10px; padding:8px 10px;">
                                <input type="checkbox" name="override_eligibility" value="1" style="margin-top:3px;">
                                Penyedia tidak lagi memenuhi syarat rating. Saya tetap menyetujui aktivasi berdasarkan kebijakan (wajib isi catatan).
                            </label>
                        @endif
                        <textarea name="note" rows="2" maxlength="500" placeholder="Catatan (mis. waktu & ID mutasi)" style="border:1px solid var(--line); border-radius:10px; padding:10px 12px; font:inherit; font-size:13px;">{{ old('note') }}</textarea>
                        <button type="submit" class="btn btn-success">Setujui Pembayaran</button>
                        <small style="color:var(--muted); font-size:11.5px;">Status menjadi Paid dan promosi aktif 96 jam sejak persetujuan.@if($trx->late_submission) Ini konfirmasi terlambat — setujui hanya jika dana benar diterima.@endif</small>
                    </form>

                    <form method="POST" action="{{ route('admin.payments.reject', $trx) }}" style="display:grid; gap:10px; padding-top:18px;">
                        @csrf
                        <label for="reason" style="font-size:12.5px; font-weight:700; color:var(--navy);">Alasan penolakan (wajib)</label>
                        <textarea id="reason" name="reason" rows="3" required minlength="5" maxlength="500" placeholder="Mis. dana tidak ditemukan pada mutasi merchant." style="border:1px solid var(--line); border-radius:10px; padding:10px 12px; font:inherit; font-size:13px;">{{ old('reason') }}</textarea>
                        <button type="submit" class="btn btn-danger">Tolak Pembayaran</button>
                    </form>
                @else
                    <dl class="pr-lines">
                        <div><dt>Diputuskan oleh</dt><dd>{{ $trx->verifier?->name ?? '—' }}</dd></div>
                        <div><dt>Waktu keputusan</dt><dd>{{ \App\Support\Wib::format($trx->verified_at) }}</dd></div>
                        <div><dt>Catatan/alasan</dt><dd style="max-width:240px;">{{ $trx->decision_note ?? '—' }}</dd></div>
                    </dl>
                @endif
            </div>
        </section>

        <section class="pr-card">
            <div class="pr-card-head"><div><h2>Audit log</h2></div></div>
            <div class="pr-card-body">
                <ul class="pr-timeline">
                    @forelse($logs as $log)
                        <li class="{{ in_array($log->action, ['payment_rejected', 'payment_failed', 'cancelled']) ? 'bad' : (in_array($log->action, ['payment_expired', 'proof_late']) ? 'muted' : '') }}">
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
