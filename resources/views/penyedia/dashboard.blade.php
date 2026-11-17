@extends('layouts.penyedia')

@section('title', 'Beranda')

@section('content')
<div class="page-header">
    <div class="page-title-box">
        <h1>Selamat datang, {{ $businessName }}!</h1>
        <p>Statistik dihitung dari data proyek dan pelamar aktual.</p>
    </div>
    <div>
        <a href="{{ route('penyedia.projects.create') }}" class="btn-primary-action">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"/>
                <line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            <span>Buat Proyek</span>
        </a>
    </div>
</div>

<!-- Metric Cards -->
<div class="metrics-grid">
    <div class="metric-card">
        <div class="metric-number">{{ $totalProjects }}</div>
        <div class="metric-title">Total proyek</div>
    </div>
    <div class="metric-card">
        <div class="metric-number" style="color: #059669;">{{ $activeProjectsCount }}</div>
        <div class="metric-title">Proyek aktif</div>
    </div>
    <div class="metric-card">
        <div class="metric-number" style="color: #2563eb;">{{ $totalApplicants }}</div>
        <div class="metric-title">Total pelamar</div>
    </div>
    <div class="metric-card">
        <div class="metric-number" style="color: #d97706;">{{ $pendingReviewCount }}</div>
        <div class="metric-title">Menunggu review</div>
    </div>
</div>

<!-- Integrasi: Sponsored Project & Reputasi -->
<div class="dash-integrations">
    <section class="dash-promo">
        <div class="dash-promo-head">
            <div>
                <span class="tdg-pill sponsor">Sponsored Project</span>
                <h3>{{ $livePromotions->isNotEmpty() ? $livePromotions->count() . ' promosi sedang tayang' : 'Tingkatkan visibilitas proyekmu' }}</h3>
                <p>
                    @if($livePromotions->isNotEmpty())
                        Pantau tayangan, klik, dan lamaran yang datang dari promosi.
                    @elseif($promotableCount > 0)
                        {{ $promotableCount }} proyek siap dipromosikan. Rp15.000 untuk 4 hari di posisi teratas Cari Proyek.
                    @else
                        Publikasikan proyek yang masih membutuhkan Talenta untuk mulai mempromosikannya.
                    @endif
                </p>
            </div>
            <a href="{{ route('penyedia.promotions.index') }}" class="tdg-btn {{ $livePromotions->isNotEmpty() ? 'ghost' : 'amber' }} sm">
                {{ $livePromotions->isNotEmpty() ? 'Kelola Promosi' : 'Promosikan Proyek' }}
            </a>
        </div>
        @foreach($livePromotions->take(3) as $promo)
            <a href="{{ route('penyedia.promotions.stats', $promo) }}" class="dash-promo-row">
                <span class="dash-promo-title">{{ $promo->project?->title }}</span>
                <span><b>{{ number_format($promo->impressions_total, 0, ',', '.') }}</b> impr.</span>
                <span><b>{{ number_format($promo->clicks_total, 0, ',', '.') }}</b> klik</span>
                <span><b>{{ $promo->applications_total }}</b> lamaran</span>
                <span class="dash-promo-left">sisa {{ $promo->remaining_label }}</span>
            </a>
        @endforeach
    </section>

    <section class="dash-rep">
        <div style="display:flex; align-items:center; gap:14px;">
            <div class="dash-rep-score">
                @if($reputation['average'] !== null)
                    <b>{{ number_format($reputation['average'], 1, ',', '') }}</b>
                    @include('partials.stars', ['score' => $reputation['average']])
                @else
                    <b style="font-size:16px;">—</b>
                @endif
            </div>
            <div style="min-width:0;">
                <h3>Reputasi usaha</h3>
                <p>{{ $reputation['average'] !== null ? $reputation['label'] . ' · ' . $reputation['count'] . ' penilaian Talenta' : 'Belum Ada Penilaian' }}</p>
            </div>
        </div>
        <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:14px;">
            <a href="{{ route('penyedia.engagements.index') }}" class="tdg-btn ghost sm">
                Penilaian Talenta
                @if($pendingWork > 0)<span class="tdg-pill warning" style="padding:1px 7px;">{{ $pendingWork }} perlu konfirmasi</span>@endif
            </a>
            <a href="{{ route('penyedia.profile') }}#reputasi" class="tdg-btn ghost sm">Lihat ulasan</a>
        </div>
    </section>
</div>

@push('styles')
<style>
    .dash-integrations { display:grid; grid-template-columns:minmax(0, 1.6fr) minmax(0, 1fr); gap:20px; margin-bottom:24px; }
    @media (max-width: 960px) { .dash-integrations { grid-template-columns:1fr; } }
    .dash-promo, .dash-rep { background:#fff; border:1px solid #e9edf2; border-radius:14px; padding:20px 22px; min-width:0; }
    .dash-promo { background:linear-gradient(180deg,#f0fbf9 0%,#fff 70%); border-color:#cdebe5; }
    .dash-promo-head { display:flex; justify-content:space-between; gap:14px; align-items:flex-start; flex-wrap:wrap; }
    .dash-promo h3, .dash-rep h3 { font-size:16px; font-weight:800; color:#182238; margin:8px 0 4px; }
    .dash-rep h3 { margin-top:0; }
    .dash-promo p, .dash-rep p { font-size:13px; color:#64748b; margin:0; line-height:1.5; }
    .dash-promo-row { display:grid; grid-template-columns:minmax(0,1fr) repeat(3, auto) auto; gap:14px; align-items:center; padding:10px 12px; margin-top:10px; border-radius:10px; background:#fff; border:1px solid #e2f3ef; text-decoration:none; font-size:12.5px; color:#64748b; }
    .dash-promo-row b { color:#182238; font-variant-numeric:tabular-nums; }
    .dash-promo-row:hover { border-color:#9fdcd2; }
    .dash-promo-title { font-weight:700; color:#182238; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .dash-promo-left { color:#0c7566; font-weight:700; }
    @media (max-width: 640px) { .dash-promo-row { grid-template-columns:repeat(3, 1fr); } .dash-promo-title, .dash-promo-left { grid-column:1 / -1; } }
    .dash-rep-score { flex:none; text-align:center; background:#f0fbf9; border:1px solid #cdebe5; border-radius:12px; padding:10px 14px; min-width:92px; }
    .dash-rep-score b { display:block; font-size:24px; font-weight:800; color:#182238; line-height:1.1; }
</style>
@endpush

<!-- Rating Penyedia: ringkasan + ulasan terbaru dari Talenta -->
<div style="margin-bottom: 24px;">
    @include('partials.reputation-mini', [
        'summary'    => $reputation,
        'title'      => 'Rating Penyedia',
        'raterLabel' => 'Talenta',
        'allUrl'     => route('penyedia.profile') . '#reputasi',
    ])
</div>

<!-- 2 Column Section: Pelamar Terbaru & Proyek Aktif -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; align-items: start;">
    <!-- Kolom Pelamar Terbaru -->
    <div style="background: #ffffff; border: 1px solid #e9edf2; border-radius: 14px; padding: 24px;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid #f3f4f6;">
            <div>
                <h3 style="font-size: 16px; font-weight: 700; color: #111827; margin: 0 0 4px;">PELAMAR TERBARU</h3>
                <span style="font-size: 13px; color: #6b7280;">Aktivitas lamaran</span>
            </div>
            <a href="{{ route('penyedia.applicants.index') }}" style="font-size: 13.5px; font-weight: 600; color: var(--primary);">Lihat semua &rarr;</a>
        </div>

        @if($recentApplicants->isEmpty())
            <div style="text-align: center; padding: 36px 16px; color: #9ca3af;">
                <svg viewBox="0 0 24 24" width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom: 10px;">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                </svg>
                <p style="margin: 0; font-size: 13.5px;">Belum ada aktivitas pelamar baru.</p>
            </div>
        @else
            <div style="display: flex; flex-direction: column; gap: 14px;">
                @foreach($recentApplicants as $applicant)
                    @php
                        $applicantName = $applicant->user->name ?? 'Talenta';
                        $initial = strtoupper(substr($applicantName, 0, 1));
                    @endphp
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px; border-radius: 10px; background: #f9fafb; gap: 12px;">
                        <div style="display: flex; align-items: center; gap: 12px; min-width: 0;">
                            <div style="width: 38px; height: 38px; border-radius: 50%; background: #e0e7ff; color: #4338ca; display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;">
                                {{ $initial }}
                            </div>
                            <div style="min-width: 0;">
                                <div style="font-size: 14px; font-weight: 700; color: #1f2a37; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    {{ $applicantName }}
                                </div>
                                <div style="font-size: 12px; color: #6b7280; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    {{ $applicant->project->title ?? '-' }} &bull; {{ $applicant->created_at->format('d M Y') }}
                                </div>
                            </div>
                        </div>
                        <span class="badge-status badge-{{ $applicant->status }}" style="flex-shrink: 0;">
                            {{ $applicant->status_label }}
                        </span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Kolom Proyek Aktif -->
    <div style="background: #ffffff; border: 1px solid #e9edf2; border-radius: 14px; padding: 24px;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid #f3f4f6;">
            <div>
                <h3 style="font-size: 16px; font-weight: 700; color: #111827; margin: 0 0 4px;">PROYEK AKTIF</h3>
                <span style="font-size: 13px; color: #6b7280;">Sedang dipublikasikan</span>
            </div>
            <a href="{{ route('penyedia.projects.index', ['status' => 'published']) }}" style="font-size: 13.5px; font-weight: 600; color: var(--primary);">Kelola &rarr;</a>
        </div>

        @if($activeProjects->isEmpty())
            <div style="text-align: center; padding: 36px 16px; color: #9ca3af;">
                <svg viewBox="0 0 24 24" width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom: 10px;">
                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                </svg>
                <p style="margin: 0 0 12px; font-size: 13.5px;">Belum ada proyek aktif yang dipublikasikan.</p>
                <a href="{{ route('penyedia.projects.create') }}" class="btn-primary-action" style="padding: 7px 14px; font-size: 13px;">Buat Proyek Pertama</a>
            </div>
        @else
            <div style="display: flex; flex-direction: column; gap: 14px;">
                @foreach($activeProjects as $project)
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 14px; border-radius: 10px; border: 1px solid #e5e7eb; background: #ffffff;">
                        <div>
                            <div style="font-size: 14.5px; font-weight: 700; color: #111827;">{{ $project->title }}</div>
                            <div style="font-size: 12.5px; color: #6b7280; margin-top: 2px;">
                                {{ $project->location }} &bull; <strong style="color: #2563eb;">{{ $project->applications_count }} pelamar</strong>
                                &bull; {{ $project->accepted_applications_count }}/{{ $project->talents_needed }} diterima
                            </div>
                            @if($project->isQuotaFilled())
                                <span style="display: inline-block; margin-top: 6px; background: #eff6ff; color: #1d4ed8; font-size: 11.5px; font-weight: 700; padding: 3px 8px; border-radius: 6px;">
                                    Kuota terpenuhi &middot; disembunyikan dari katalog
                                </span>
                            @endif
                        </div>
                        <a href="{{ route('penyedia.projects.edit', $project) }}" class="btn-secondary-action" style="padding: 6px 12px; font-size: 12.5px;">Edit</a>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
