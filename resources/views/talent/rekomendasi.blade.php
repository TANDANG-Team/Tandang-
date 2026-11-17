@extends('talent.layout')

@section('title', 'Rekomendasi Proyek')

@section('content')
<div class="page-head">
    <div>
        <h1>Rekomendasi Proyek Cerdas</h1>
        <p>Proyek diurutkan secara otomatis berdasarkan kecocokan kompetensi, gaya kerja, lokasi, dan ketersediaan waktumu.</p>
    </div>
</div>

@if(empty($talent->assessment_answers))
    <div class="alert alert-warning" style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <strong>Kecocokan Gaya Kerja Belum Maksimal!</strong>
            <p style="margin: 0; font-size: 13px;">Selesaikan asesmen gaya kerja untuk mendapatkan kalkulasi kecocokan yang lebih akurat.</p>
        </div>
        <a href="{{ route('talent.asesmen') }}" class="btn btn-primary btn-sm" style="white-space: nowrap;">Mulai Asesmen</a>
    </div>
@endif

@if($projects->count() > 0)
    <div class="project-grid">
        @foreach($projects as $p)
            @php
                $applyStatus = $applyStatuses[$p->id] ?? null;
                $scoreClass = $p->match_score >= 80 ? 'high' : ($p->match_score >= 60 ? 'medium' : 'low');
                $components = $p->match_data['components'] ?? [];
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

                    <!-- Breakdown Evaluasi Kecocokan -->
                    <div style="background: #f8fafc; border: 1px solid var(--line); border-radius: 8px; padding: 10px 12px; margin-bottom: 14px; font-size: 11.5px; color: var(--muted);">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                            <span>Kompetensi:</span>
                            <strong style="color: var(--navy);">{{ $components['skills']['detail'] ?? '-' }}</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                            <span>Gaya Kerja:</span>
                            <strong style="color: var(--navy);">{{ $components['workstyle']['detail'] ?? '-' }}</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span>Lokasi & Waktu:</span>
                            <strong style="color: var(--navy);">{{ $components['location']['detail'] ?? '-' }}</strong>
                        </div>
                    </div>

                    <div class="project-meta-pills">
                        <span class="meta-pill">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                            {{ $p->location }} ({{ $p->work_type }})
                        </span>
                        <span class="meta-pill">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            {{ $p->schedule }}
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
    <div style="background: #fff; padding: 40px; border-radius: 12px; text-align: center; border: 1px dashed var(--line);">
        <p style="color: var(--muted);">Tidak ada proyek yang sesuai dengan kriteria saat ini.</p>
    </div>
@endif

<!-- Project Detail & Apply Modal -->
@include('talent.partials.project-modal')
@endsection

