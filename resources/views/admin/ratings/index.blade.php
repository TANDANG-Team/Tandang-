@extends('layouts.admin')

@section('title', 'Moderasi Rating')
@section('header', 'Reputasi & Ulasan')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/promotion.css') }}?v=2">
<style>
    .mod-form { display:grid; gap:6px; margin-top:6px; }
    .mod-form textarea { border:1px solid var(--line); border-radius:8px; padding:8px 10px; font:inherit; font-size:12.5px; min-height:54px; }
    .mod-scores { display:flex; flex-wrap:wrap; gap:4px 10px; font-size:11.5px; color:var(--muted); margin-top:4px; }
    .review-text { font-size:12.5px; color:#334155; max-width:320px; overflow-wrap:anywhere; }
    .review-text.hidden-review { color:#94a3b8; font-style:italic; }
</style>
@endpush

@section('content')
@php
    $tabs = ['rating' => 'Daftar Rating', 'laporan' => 'Laporan', 'sengketa' => 'Sengketa Pengerjaan', 'log' => 'Audit Log'];
    $aspectLabels = collect(config('rating.talent_aspects'))->merge(config('rating.provider_aspects'))->map(fn ($a) => $a['label']);
@endphp

<div class="page-head">
    <h1>Moderasi Rating</h1>
    <p>Sembunyikan ulasan yang melanggar aturan tanpa mengubah nilai bintang, tangani laporan, dan putuskan sengketa penyelesaian kerja. Semua tindakan tercatat.</p>
</div>

<div class="management-toolbar">
    <div class="filter-pills">
        @foreach($tabs as $key => $label)
            <a href="{{ route('admin.ratings.index', ['tab' => $key]) }}" class="{{ $tab === $key ? 'active' : '' }}">
                {{ $label }}
                @if(isset($counts[$key]))<span>{{ $counts[$key] }}</span>@endif
            </a>
        @endforeach
    </div>
</div>

<div class="table-card">
    @if($items->isEmpty())
        <div class="pr-empty"><strong>Tidak ada data</strong>Belum ada entri untuk tab ini.</div>
    @else
        <div class="pr-table-wrap">
            <table class="pr-table">
                @if($tab === 'rating')
                    <thead><tr><th>Pemberi → Penerima</th><th>Proyek</th><th class="num">Nilai</th><th>Ulasan</th><th>Publikasi</th><th>Tindakan</th></tr></thead>
                    <tbody>
                        @foreach($items as $r)
                            <tr>
                                <td style="min-width:180px;">
                                    <strong>{{ $r->rater?->name }}</strong> <span class="muted">({{ $r->rater_role }})</span><br>
                                    → <strong>{{ $r->ratee?->name }}</strong> <span class="muted">({{ $r->ratee_role }})</span>
                                </td>
                                <td>{{ $r->project?->title }}</td>
                                <td class="num">
                                    <strong>{{ number_format((float) $r->final_score, 2, ',', '') }}</strong>
                                    <div class="mod-scores" style="justify-content:flex-end;">
                                        @foreach($r->scores as $k => $v)
                                            <span title="{{ $aspectLabels[$k] ?? $k }}">{{ \Illuminate\Support\Str::limit($aspectLabels[$k] ?? $k, 14, '…') }}: {{ $v ?? 'N/A' }}</span>
                                        @endforeach
                                    </div>
                                </td>
                                <td>
                                    @if($r->review)
                                        <div class="review-text {{ $r->review_hidden ? 'hidden-review' : '' }}">{{ $r->review }}</div>
                                        @if($r->review_hidden)<div class="muted" style="font-size:11px;">Disembunyikan: {{ $r->hidden_reason }}</div>@endif
                                    @else
                                        <span class="muted">—</span>
                                    @endif
                                    @if($r->open_reports_count)<span class="tdg-pill warning" style="margin-top:4px;">{{ $r->open_reports_count }} laporan terbuka</span>@endif
                                </td>
                                <td>
                                    @if($r->isVoided())
                                        <span class="tdg-pill danger">Dibatalkan</span>
                                        <div class="muted" style="font-size:11px;">{{ $r->void_reason }}</div>
                                    @elseif($r->isPublished())
                                        <span class="tdg-pill success">Dipublikasikan</span>
                                    @else
                                        <span class="tdg-pill muted">Disembunyikan sementara</span>
                                        <div class="muted" style="font-size:11px;">hingga {{ \App\Support\Wib::format($r->completion?->rating_deadline_at) }}</div>
                                    @endif
                                </td>
                                <td style="min-width:200px;">
                                    @if($r->review)
                                        <form method="POST" action="{{ route($r->review_hidden ? 'admin.ratings.restore' : 'admin.ratings.hide', $r) }}" class="mod-form">
                                            @csrf
                                            <textarea name="reason" required minlength="5" maxlength="500" placeholder="Alasan {{ $r->review_hidden ? 'pemulihan' : 'penyembunyian' }}…"></textarea>
                                            <button class="btn {{ $r->review_hidden ? 'btn-success' : 'btn-danger' }}" style="padding:6px 10px; font-size:12px;">{{ $r->review_hidden ? 'Pulihkan ulasan' : 'Sembunyikan ulasan' }}</button>
                                        </form>
                                    @else
                                        <span class="muted" style="font-size:12px;">Tanpa ulasan tertulis</span>
                                    @endif
                                    <form method="POST" action="{{ route($r->isVoided() ? 'admin.ratings.unvoid' : 'admin.ratings.void', $r) }}" class="mod-form" style="margin-top:8px;"
                                          onsubmit="return confirm('{{ $r->isVoided() ? 'Hitung kembali rating ini ke reputasi?' : 'Batalkan rating ini? Nilai tidak lagi dihitung ke reputasi & syarat promosi.' }}');">
                                        @csrf
                                        <textarea name="reason" required minlength="5" maxlength="500" placeholder="Alasan {{ $r->isVoided() ? 'mencabut pembatalan' : 'pembatalan (pelanggaran terbukti)' }}…"></textarea>
                                        <button class="btn {{ $r->isVoided() ? 'btn-success' : 'btn-danger' }}" style="padding:6px 10px; font-size:12px;">{{ $r->isVoided() ? 'Cabut pembatalan' : 'Batalkan rating' }}</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                @elseif($tab === 'laporan')
                    <thead><tr><th>Jenis</th><th>Pelapor</th><th>Rating</th><th>Alasan</th><th>Status</th><th>Tindakan</th></tr></thead>
                    <tbody>
                        @foreach($items as $rep)
                            <tr>
                                <td><span class="tdg-pill {{ $rep->type === 'correction' ? 'info' : 'warning' }}">{{ $rep->type_label }}</span></td>
                                <td>{{ $rep->reporter?->name }}<div class="muted" style="font-size:11px;">{{ \App\Support\Wib::format($rep->created_at) }}</div></td>
                                <td style="min-width:200px;">
                                    {{ $rep->rating?->rater?->name }} → {{ $rep->rating?->ratee?->name }}
                                    <div class="muted" style="font-size:11.5px;">{{ $rep->rating?->project?->title }} · {{ number_format((float) $rep->rating?->final_score, 2, ',', '') }}</div>
                                    @if($rep->rating?->review)<div class="review-text" style="margin-top:4px;">"{{ \Illuminate\Support\Str::limit($rep->rating->review, 140) }}"</div>@endif
                                </td>
                                <td class="review-text">{{ $rep->reason }}</td>
                                <td>
                                    <span class="tdg-pill {{ $rep->status === 'open' ? 'warning' : ($rep->status === 'resolved' ? 'success' : 'muted') }}">{{ ['open' => 'Terbuka', 'resolved' => 'Ditindaklanjuti', 'dismissed' => 'Ditolak'][$rep->status] ?? $rep->status }}</span>
                                    @if($rep->admin_note)<div class="muted" style="font-size:11px; max-width:200px;">{{ $rep->admin_note }} — {{ $rep->handler?->name }}</div>@endif
                                </td>
                                <td style="min-width:210px;">
                                    @if($rep->status === 'open')
                                        <form method="POST" action="{{ route('admin.ratings.reports.handle', $rep) }}" class="mod-form">
                                            @csrf
                                            <textarea name="admin_note" required minlength="5" maxlength="1000" placeholder="Catatan keputusan…"></textarea>
                                            <div style="display:flex; gap:6px;">
                                                <button name="decision" value="resolved" class="btn btn-success" style="padding:6px 10px; font-size:12px;">Tindak lanjuti</button>
                                                <button name="decision" value="dismissed" class="btn btn-secondary" style="padding:6px 10px; font-size:12px;">Tolak</button>
                                            </div>
                                            <small class="muted" style="font-size:11px;">Gunakan tab Daftar Rating untuk menyembunyikan ulasan bila perlu.</small>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                @elseif($tab === 'sengketa')
                    <thead><tr><th>Proyek</th><th>Talenta / Penyedia</th><th>Diajukan</th><th>Alasan sengketa</th><th>Status</th><th>Keputusan</th></tr></thead>
                    <tbody>
                        @foreach($items as $c)
                            <tr>
                                <td>{{ $c->project?->title }}</td>
                                <td>{{ $c->talent?->name }}<br><span class="muted">{{ $c->provider?->name }}</span></td>
                                <td style="font-size:12px;">{{ $c->disputer?->name }}<div class="muted">{{ \App\Support\Wib::format($c->disputed_at) }}</div></td>
                                <td class="review-text">{{ $c->dispute_reason }}</td>
                                <td><span class="tdg-pill {{ $c->status_tone }}">{{ $c->status_label }}</span>@if($c->resolution_note)<div class="muted" style="font-size:11px; max-width:200px;">{{ $c->resolution_note }}</div>@endif</td>
                                <td style="min-width:220px;">
                                    @if($c->status === 'disputed')
                                        <form method="POST" action="{{ route('admin.ratings.disputes.resolve', $c) }}" class="mod-form">
                                            @csrf
                                            <textarea name="resolution_note" required minlength="5" maxlength="1000" placeholder="Dasar keputusan…"></textarea>
                                            <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                                <button name="decision" value="completed" class="btn btn-success" style="padding:6px 10px; font-size:12px;">Nyatakan selesai</button>
                                                <button name="decision" value="reopen" class="btn btn-secondary" style="padding:6px 10px; font-size:12px;">Lanjutkan pekerjaan</button>
                                            </div>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                @else
                    <thead><tr><th>Waktu</th><th>Tindakan</th><th>Oleh</th><th>Terkait</th><th>Detail</th></tr></thead>
                    <tbody>
                        @foreach($items as $log)
                            <tr>
                                <td style="white-space:nowrap; font-size:12px;">{{ \App\Support\Wib::format($log->created_at) }}</td>
                                <td><strong>{{ $log->action_label }}</strong></td>
                                <td>{{ $log->user?->name ?? 'Sistem' }}</td>
                                <td>{{ $log->rating?->project?->title ?? $log->completion?->project?->title ?? '—' }}</td>
                                <td class="review-text">{{ $log->details ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                @endif
            </table>
        </div>
        @if($items->hasPages())<div style="padding:14px 18px; border-top:1px solid var(--line);">{{ $items->links() }}</div>@endif
    @endif
</div>
@endsection
