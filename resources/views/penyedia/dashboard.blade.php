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
            <span>+ Buat Proyek</span>
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
                            </div>
                        </div>
                        <a href="{{ route('penyedia.projects.edit', $project) }}" class="btn-secondary-action" style="padding: 6px 12px; font-size: 12.5px;">Edit</a>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
