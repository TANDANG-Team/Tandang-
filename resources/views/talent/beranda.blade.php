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
    <article class="dash-card">
        <div>
            <span class="badge 
                {{ $talent->verification_status === 'Terverifikasi' ? 'badge-success' : ($talent->verification_status === 'Ditolak' ? 'badge-danger' : 'badge-warning') }}" 
                style="margin-bottom: 8px;">
                Status Akun
            </span>
            <h3>{{ $talent->verification_status }}</h3>
            <p>
                @if($talent->verification_status === 'Terverifikasi')
                    Akun talenta telah diverifikasi oleh tim admin.
                @elseif($talent->verification_status === 'Menunggu Verifikasi')
                    Dokumen sedang dalam proses peninjauan admin.
                @elseif($talent->verification_status === 'Ditolak')
                    Dokumen verifikasi ditolak. Klik untuk perbaiki.
                @else
                    Unggah bukti mahasiswa/alumni untuk verifikasi.
                @endif
            </p>
        </div>
        <a href="{{ route('talent.verifikasi') }}" class="btn btn-secondary btn-sm" style="align-self: flex-start; margin-top: 14px;">
            Kelola Verifikasi &rarr;
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

@if($topRecommendations->count() > 0)
    <div class="project-grid">
        @foreach($topRecommendations as $p)
            @php
                $applyStatus = $applyStatuses[$p->id] ?? null;
                $scoreClass = $p->match_score >= 80 ? 'high' : ($p->match_score >= 60 ? 'medium' : 'low');
            @endphp
            <article class="project-card has-cover">
                <div>
                    <div class="project-cover"><img src="{{ $p->cover_url }}" alt="" loading="lazy"></div>
                    <div class="project-card-top">
                        <span class="provider">{{ $p->provider_name }}</span>
                        <span class="match-score-badge {{ $scoreClass }}">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m12 3-1.9 5.8a2 2 0 0 1-1.3 1.3L3 12l5.8 1.9a2 2 0 0 1 1.3 1.3L12 21l1.9-5.8a2 2 0 0 1 1.3-1.3L21 12l-5.8-1.9a2 2 0 0 1-1.3-1.3Z"/></svg>
                            {{ $p->match_score }}% Cocok
                        </span>
                    </div>

                    <h3>{{ $p->title }}</h3>
                    <p class="desc">{{ $p->description }}</p>

                    <div class="project-meta-pills">
                        <span class="meta-pill">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                            {{ $p->location }} ({{ $p->work_type }})
                        </span>
                        <span class="meta-pill">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            {{ $p->schedule }}
                        </span>
                        <span class="meta-pill">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            Butuh {{ $applyStatus['talents_needed'] ?? $p->talents_needed }} talenta
                        </span>
                        @include('talent.partials.apply-status-pill')
                    </div>

                    <div class="skill-tags">
                        @foreach($p->skills ?? [] as $sk)
                            @php
                                $isMatched = in_array($sk, $p->match_data['matched_skills'] ?? []);
                            @endphp
                            <span class="skill-tag {{ $isMatched ? 'matched' : '' }}">
                                {{ $sk }} {{ $isMatched ? '✓' : '' }}
                            </span>
                        @endforeach
                    </div>
                </div>

                <div class="project-card-footer">
                    <span class="comp">{{ $p->compensation ?: 'Sesuai Kesepakatan' }}</span>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="openDetailModal({{ json_encode($p) }}, {{ json_encode($applyStatus) }})">
                        {{ ($applyStatus['can_apply'] ?? true) ? 'Lihat Detail & Lamar' : 'Lihat Detail' }} &rarr;
                    </button>
                </div>
            </article>
        @endforeach
    </div>
@else
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
@endsection

