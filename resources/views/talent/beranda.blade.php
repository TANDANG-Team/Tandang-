@extends('talent.layout')

@section('title', 'Beranda Talenta')

@section('content')
<div class="page-head">
    <div>
        <h1>Halo, {{ explode(' ', $user->name)[0] }}! Siap berkarya?</h1>
        <p>Temukan peluang proyek UMKM yang selaras dengan keahlian dan gaya kerjamu.</p>
    </div>
</div>

<!-- Top Cards Grid -->
<div class="dash-grid-top">
    <!-- Card 1: Kelengkapan Profil -->
    <article class="dash-card">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <span class="badge badge-info" style="margin-bottom: 8px;">Profil</span>
                <h3>Kelengkapan Profil</h3>
            </div>
            <span style="font-size: 20px; font-weight: 800; color: var(--teal);">{{ $talent->completeness }}%</span>
        </div>
        <div class="progress-bar-bg">
            <div class="progress-bar-fill" style="width: {{ $talent->completeness }}%;"></div>
        </div>
        <a href="{{ route('talent.profil') }}" class="btn btn-secondary btn-sm" style="align-self: flex-start;">
            Lengkapi Profil
        </a>
    </article>

    <!-- Card 2: Status Verifikasi -->
    {{-- verification_status di DB berbahasa Inggris (verified/pending/rejected/...); pakai label agar data lama berbahasa Indonesia ikut terbaca. --}}
    @php
        $verifLabel = $talent->verification_status_label;
        $verif = match ($verifLabel) {
            'Terverifikasi' => [
                'tone' => 'success', 'badge' => 'badge-success',
                'text' => 'Akunmu sudah diverifikasi admin. Kamu bisa melamar proyek.',
                'cta' => 'Lihat Detail Verifikasi',
            ],
            'Menunggu Verifikasi' => [
                'tone' => 'warning', 'badge' => 'badge-warning',
                'text' => 'Dokumen sedang ditinjau admin. Kamu bisa melamar setelah terverifikasi.',
                'cta' => 'Cek Status Verifikasi',
            ],
            'Ditolak', 'Kedaluwarsa' => [
                'tone' => 'danger', 'badge' => 'badge-danger',
                'text' => $verifLabel === 'Ditolak'
                    ? 'Dokumen verifikasi ditolak. Perbaiki dan unggah ulang dokumenmu.'
                    : 'Masa verifikasi berakhir. Unggah dokumen terbaru untuk verifikasi ulang.',
                'cta' => 'Perbaiki Verifikasi',
            ],
            default => [
                'tone' => 'neutral', 'badge' => 'badge-info',
                'text' => 'Unggah bukti pelajar/mahasiswa/alumni untuk verifikasi.',
                'cta' => 'Mulai Verifikasi',
            ],
        };
    @endphp
    <article class="dash-card verif-card is-{{ $verif['tone'] }}">
        <div>
            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px;">
                <div>
                    <span class="badge {{ $verif['badge'] }}" style="margin-bottom: 8px;">Status Akun</span>
                    <h3>{{ $verifLabel }}</h3>
                </div>
                <span class="verif-icon" aria-hidden="true">
                    @switch($verif['tone'])
                        @case('success')
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
                            @break
                        @case('warning')
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            @break
                        @case('danger')
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                            @break
                        @default
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    @endswitch
                </span>
            </div>
            <p>{{ $verif['text'] }}</p>
            @if($verif['tone'] === 'success' && $talent->verified_at)
                <p class="verif-meta">Diverifikasi {{ $talent->verified_at->translatedFormat('d M Y') }}</p>
            @endif
        </div>
        <a href="{{ route('talent.verifikasi') }}" class="btn {{ $verif['tone'] === 'success' ? 'btn-secondary' : 'btn-primary' }} btn-sm" style="align-self: flex-start; margin-top: 14px;">
            {{ $verif['cta'] }} &rarr;
        </a>
    </article>

    <!-- Card 3: Asesmen Gaya Kerja -->
    <article class="dash-card" style="background: linear-gradient(135deg, #ffffff 0%, #f0fdfa 100%); border-color: #ccfbf1;">
        <div>
            <span class="badge badge-purple" style="margin-bottom: 8px;">
                {{ !empty($talent->assessment_answers) ? 'ASESMEN SELESAI' : 'KENALI GAYA KERJAMU' }}
            </span>
            <h3>{{ !empty($talent->assessment_answers) ? 'Preferensi Kerjamu Tersimpan' : 'Temukan Proyek Lebih Sesuai' }}</h3>
            <p>
                @if(!empty($talent->assessment_answers))
                    Terakhir diperbarui: {{ $talent->assessment_completed_at ? $talent->assessment_completed_at->format('d M Y') : 'Baru saja' }}
                @else
                    8 pertanyaan singkat · sekitar 2–3 menit untuk kalkulasi kecocokan proyek.
                @endif
            </p>
        </div>
        <a href="{{ route('talent.asesmen') }}" class="btn btn-primary btn-sm" style="align-self: flex-start; margin-top: 14px;">
            {{ !empty($talent->assessment_answers) ? 'Lihat Hasil Asesmen' : 'Mulai Asesmen Sekarang' }} &rarr;
        </a>
    </article>
</div>


<!-- Section: Reputasi (rating dari Penyedia) -->
<div style="margin: 28px 0;">
    @include('partials.reputation-mini', [
        'summary'    => $reputation,
        'title'      => 'Rating Saya',
        'raterLabel' => 'Penyedia',
        'allUrl'     => route('talent.profil') . '#reputasi',
    ])
</div>

<!-- Section: Rekomendasi Proyek -->
<div class="section-title">
    <h2>Rekomendasi Proyek untukmu</h2>
    <a href="{{ route('talent.rekomendasi') }}" class="btn btn-secondary btn-sm">Lihat Semua Rekomendasi</a>
</div>

{{-- Featured Sponsored Project (berbayar): satu kartu lebar penuh, dirotasi acak dari promosi aktif. --}}
@if($featuredProject)
    @include('partials.sponsored-feature', [
        'project'     => $featuredProject,
        'applyStatus' => $applyStatuses[$featuredProject->id] ?? null,
    ])
@endif

@if($topRecommendations->count() > 0)
    <div class="project-grid">
        @foreach($topRecommendations as $p)
            @include('talent.partials.project-card', ['applyStatus' => $applyStatuses[$p->id] ?? null])
        @endforeach
    </div>
@elseif(! $featuredProject)
    <div style="background: #fff; padding: 40px; border-radius: 12px; text-align: center; border: 1px dashed var(--line); margin-bottom: 32px;">
        <p style="color: var(--muted);">Belum ada proyek yang dipublikasikan saat ini.</p>
    </div>
@endif

<!-- Section: Riwayat Lamaran Terbaru -->
@if($recentApplications->count() > 0)
    <div class="section-title">
        <h2>Status Lamaran Terbaru</h2>
        <a href="{{ route('talent.lamaran') }}" class="btn btn-secondary btn-sm">Semua Lamaran</a>
    </div>

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Proyek & Penyedia</th>
                    <th>Tanggal Melamar</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recentApplications as $app)
                    <tr>
                        <td>
                            <strong>{{ $app->project->title ?? 'Proyek Telah Dihapus' }}</strong><br>
                            <small style="color: var(--muted);">{{ $app->project->provider_name ?? '-' }}</small>
                        </td>
                        <td>{{ $app->created_at->format('d M Y') }}</td>
                        <td>
                            <span class="badge 
                                {{ $app->status === 'Diterima' ? 'badge-success' : ($app->status === 'Ditolak' ? 'badge-danger' : ($app->status === 'Dalam Review' ? 'badge-info' : 'badge-warning')) }}">
                                {{ $app->status }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

<!-- Project Detail & Apply Modal -->
@include('talent.partials.project-modal')
@include('talent.partials.promo-tracking')
@endsection

