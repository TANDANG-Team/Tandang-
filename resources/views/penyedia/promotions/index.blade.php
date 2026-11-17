@extends('layouts.penyedia')

@section('title', 'Promosi Proyek')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/promotion.css') }}?v=2">
@endpush

@section('content')
<div class="page-header">
    <div class="page-title-box">
        <h1>Promosi Proyek</h1>
        <p>Tingkatkan visibilitas proyekmu di halaman Cari Proyek. Opsional — membuat & mempublikasikan proyek tetap gratis.</p>
    </div>
    <a href="{{ route('penyedia.promotions.history') }}" class="btn-secondary-action">Riwayat Promosi</a>
</div>

<div class="pr-wrap">
    {{-- Paket --}}
    <section class="pr-hero" aria-label="Paket Sponsored Project">
        <div style="position:relative; z-index:1;">
            <span class="pr-hero-eyebrow">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.5l2.94 5.96 6.58.96-4.76 4.64 1.12 6.55L12 17.52l-5.88 3.09 1.12-6.55L2.48 9.42l6.58-.96z"/></svg>
                Sponsored Project
            </span>
            <h2>Tampil paling atas saat Talenta mencari proyek yang relevan</h2>
            <p>Satu kartu Featured di atas hasil pencarian, label "Dipromosikan", dan dashboard statistik (impressions, klik, CTR, lamaran). Promosi membantu visibilitas, <strong style="color:#fff;">bukan jaminan</strong> jumlah pelamar.</p>
        </div>
        @if($package)
            <div class="pr-hero-price">
                <b>{{ $package->formatted_price }}</b>
                <span>/ {{ $package->duration_label }} penayangan</span>
                <small>1 proyek · tanpa langganan</small>
            </div>
        @endif
    </section>

    {{-- Kelayakan Promosi --}}
    @php
        $fmt = fn ($v) => number_format((float) $v, 1, ',', '');
        $ok = $standing['eligible'];
    @endphp
    <section class="pr-card elig-card {{ $ok ? 'is-ok' : 'is-locked' }}" aria-labelledby="eligTitle">
        <div class="pr-card-head">
            <div>
                <h2 id="eligTitle">Kelayakan Promosi Anda</h2>
                <p>Sponsored Project terbuka untuk Penyedia dengan rating usaha minimal {{ $fmt($standing['min_rating']) }}/5 dari minimal {{ $standing['min_reviews'] }} penilaian valid Talenta pada proyek yang selesai.</p>
            </div>
            <span class="tdg-pill {{ $ok ? 'success' : 'warning' }}" style="font-size:12.5px; padding:6px 12px;">{{ $ok ? 'Memenuhi Syarat' : 'Belum Memenuhi Syarat' }}</span>
        </div>
        <div class="pr-card-body">
            <div class="elig-grid">
                <div class="elig-metric">
                    <span class="elig-label">Rating Usaha</span>
                    <b class="elig-value">
                        @if($standing['average'] !== null)
                            <span class="elig-star" aria-hidden="true">★</span> {{ $fmt($standing['average']) }}<small>/5</small>
                        @else
                            <span style="font-size:15px;">Belum Ada Penilaian</span>
                        @endif
                    </b>
                    <div class="elig-bar" role="progressbar" aria-label="Progres rating usaha" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $standing['rating_progress'] }}">
                        <i style="width: {{ $standing['rating_progress'] }}%;" class="{{ $standing['meets_rating'] ? 'ok' : '' }}"></i>
                    </div>
                    <span class="elig-hint">Minimal Rating: {{ $fmt($standing['min_rating']) }}/5 @if($standing['meets_rating'])· ✓ terpenuhi @endif</span>
                </div>
                <div class="elig-metric">
                    <span class="elig-label">Jumlah Penilaian</span>
                    <b class="elig-value">{{ $standing['count'] }}<small> penilaian</small></b>
                    <div class="elig-bar" role="progressbar" aria-label="Progres jumlah penilaian" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $standing['count_progress'] }}">
                        <i style="width: {{ $standing['count_progress'] }}%;" class="{{ $standing['meets_count'] ? 'ok' : '' }}"></i>
                    </div>
                    <span class="elig-hint">Minimal Penilaian: {{ $standing['min_reviews'] }} @if($standing['meets_count'])· ✓ terpenuhi @endif</span>
                </div>
                <div class="elig-metric">
                    <span class="elig-label">Status</span>
                    <b class="elig-value" style="font-size:17px; color: {{ $ok ? '#047857' : '#b45309' }};">{{ $ok ? 'Memenuhi Syarat' : 'Belum Memenuhi Syarat' }}</b>
                    <span class="elig-hint">{{ $ok ? 'Tombol "Promosikan Proyek" aktif untuk proyek yang layak.' : 'Tombol "Promosikan Proyek" dinonaktifkan.' }}</span>
                </div>
            </div>

            @unless($ok)
                <div class="elig-message">
                    <p>{{ $standing['message'] }}</p>
                    @if($standing['missing'])
                        <ul>@foreach($standing['missing'] as $m)<li>{{ $m }}</li>@endforeach</ul>
                    @endif
                    <a href="{{ route('penyedia.profile') }}#reputasi" class="tdg-btn ghost sm">Lihat Penilaian Saya</a>
                </div>
            @endunless
        </div>
    </section>

    {{-- KPI ringkas --}}
    <div class="pr-kpis">
        <div class="pr-kpi accent">
            <div class="pr-kpi-label"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 11l18-5v12L3 14v-3z"/></svg>Promosi tayang</div>
            <div class="pr-kpi-value">{{ $summary['live'] }}</div>
            <div class="pr-kpi-sub">sedang aktif saat ini</div>
        </div>
        <div class="pr-kpi">
            <div class="pr-kpi-label"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>Impressions aktif</div>
            <div class="pr-kpi-value">{{ number_format($summary['impressions'], 0, ',', '.') }}</div>
            <div class="pr-kpi-sub">dari promosi yang sedang tayang</div>
        </div>
        <div class="pr-kpi">
            <div class="pr-kpi-label"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 9 5 12 1.8-5.2L21 14Z"/><path d="M7.2 2.2 8 5.1M5.1 8l-2.9-.8M14 4.1 12 6M6 12l-1.9 2"/></svg>Klik aktif</div>
            <div class="pr-kpi-value">{{ number_format($summary['clicks'], 0, ',', '.') }}</div>
            <div class="pr-kpi-sub">menuju detail proyek</div>
        </div>
        <div class="pr-kpi">
            <div class="pr-kpi-label"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>Siap dipromosikan</div>
            <div class="pr-kpi-value">{{ $summary['eligible'] }}</div>
            <div class="pr-kpi-sub">proyek memenuhi syarat</div>
        </div>
    </div>

    {{-- Status pembayaran & promosi --}}
    <section class="pr-card">
        <div class="pr-card-head">
            <div><h2>Status pembayaran & promosi</h2><p>Pembayaran QRIS diverifikasi manual oleh Admin sebelum promosi aktif.</p></div>
            <a href="{{ route('penyedia.promotions.history') }}" class="tdg-btn ghost sm">Lihat riwayat</a>
        </div>
        <div class="pr-card-body" style="display:flex; flex-wrap:wrap; gap:10px;">
            @foreach($statusBoard as [$label, $count, $tone])
                <span class="tdg-pill {{ $count ? $tone : 'muted' }}" style="font-size:12.5px; padding:6px 12px;">{{ $label }} · <b style="margin-left:3px;">{{ $count }}</b></span>
            @endforeach
        </div>
    </section>

    {{-- Promosi yang sedang tayang --}}
    @if($live->isNotEmpty())
        <section class="pr-card">
            <div class="pr-card-head">
                <div><h2>Sedang tayang</h2><p>Data diperbarui setiap kali Talenta melihat atau mengklik kartu promosimu.</p></div>
            </div>
            <div class="pr-card-body pr-live">
                @foreach($live as $promo)
                    <div class="pr-live-item">
                        <div style="min-width:0;">
                            <strong>{{ $promo->project?->title }}</strong>
                            <div style="font-size:12px; color:#64748b; margin-top:2px;">Berakhir {{ \App\Support\Wib::format($promo->end_at) }} · sisa {{ $promo->remaining_label }}</div>
                        </div>
                        <div class="pr-live-num"><b>{{ number_format($promo->impressions_total, 0, ',', '.') }}</b><span>Impressions</span></div>
                        <div class="pr-live-num"><b>{{ number_format($promo->clicks_total, 0, ',', '.') }}</b><span>Klik</span></div>
                        <div class="pr-live-num"><b>{{ $promo->applications_total }}</b><span>Lamaran</span></div>
                        <a href="{{ route('penyedia.promotions.stats', $promo) }}" class="tdg-btn ghost sm">Lihat Statistik</a>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Daftar proyek --}}
    <section class="pr-card">
        <div class="pr-card-head">
            <div>
                <h2>Pilih proyek untuk dipromosikan</h2>
                <p>Syarat: dipublikasikan, masih menerima lamaran, kuota Talenta belum penuh, dan tidak sedang dipromosikan.</p>
            </div>
        </div>
        <div class="pr-card-body">
            @if($projects->isEmpty())
                <div class="pr-empty">
                    <strong>Belum ada proyek</strong>
                    Buat dan publikasikan proyek terlebih dahulu.
                    <div style="margin-top:14px;"><a href="{{ route('penyedia.projects.create') }}" class="tdg-btn primary">Buat Proyek</a></div>
                </div>
            @else
                <div class="pr-projects">
                    @foreach($projects as $project)
                        @php
                            $promo = $project->livePromotion;
                            $latestTrx = $project->latestPromotion?->transaction;
                            $pendingPromo = $latestTrx && (($latestTrx->isPending() && ! $latestTrx->isPaymentWindowOver()) || $latestTrx->needsReview()) ? $project->latestPromotion : null;
                        @endphp
                        <article class="pr-project">
                            <div class="pr-thumb"><img src="{{ $project->cover_url }}" alt="" loading="lazy"></div>
                            <div style="min-width:0;">
                                <h3>{{ $project->title }}</h3>
                                <div class="pr-project-meta">
                                    <span>{{ $project->category }}</span>
                                    <span>·</span>
                                    <span>Kuota {{ $project->acceptedCount() }}/{{ max(1, (int) $project->talents_needed) }}</span>
                                    <span class="tdg-pill {{ $project->isPublished() ? 'success' : 'muted' }}">{{ $project->status_label }}</span>
                                    @if($promo)
                                        <span class="tdg-pill sponsor">Dipromosikan · sisa {{ $promo->remaining_label }}</span>
                                    @elseif($pendingPromo)
                                        <span class="tdg-pill {{ $latestTrx->status_tone }}">{{ $latestTrx->status_label }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="pr-project-actions">
                                @if($promo)
                                    <a href="{{ route('penyedia.promotions.stats', $promo) }}" class="tdg-btn ghost sm">Lihat Statistik</a>
                                @elseif($pendingPromo)
                                    <a href="{{ route('penyedia.promotions.payment', $pendingPromo) }}" class="tdg-btn amber sm">{{ $latestTrx->isPending() ? 'Lanjutkan Pembayaran' : 'Lihat Status' }}</a>
                                @elseif(! $standing['eligible'])
                                    <button type="button" class="tdg-btn primary sm" disabled aria-disabled="true" title="{{ $standing['message'] }}">Promosikan Proyek</button>
                                    <span class="pr-blocker">Rating usaha belum memenuhi syarat</span>
                                @elseif($project->blocker === null)
                                    <a href="{{ route('penyedia.promotions.checkout', $project) }}" class="tdg-btn primary sm">Promosikan Proyek</a>
                                @else
                                    <span class="pr-blocker">{{ $project->blocker }}</span>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</div>
@endsection
