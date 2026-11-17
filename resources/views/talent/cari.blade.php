@extends('talent.layout')

@section('title', 'Cari Proyek')

@section('content')
<div class="page-head">
    <div>
        <h1>Katalog & Pencarian Proyek</h1>
        <p>Jelajahi berbagai proyek nyata dari UMKM dan mitra terverifikasi.</p>
    </div>
</div>

<!-- Filter Bar -->
<form method="GET" action="{{ route('talent.cari') }}" class="filter-bar">
    <div class="search-input-wrap">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
        <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari judul proyek, penyedia, atau kata kunci...">
    </div>

    <select name="category" class="filter-select">
        <option value="Semua">Semua Kategori</option>
        @foreach($categories as $cat)
            <option value="{{ $cat }}" {{ ($filters['category'] ?? '') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
        @endforeach
    </select>

    <select name="work_type" class="filter-select">
        <option value="Semua">Semua Tipe Kerja</option>
        <option value="Remote" {{ ($filters['work_type'] ?? '') === 'Remote' ? 'selected' : '' }}>Remote</option>
        <option value="Onsite" {{ ($filters['work_type'] ?? '') === 'Onsite' ? 'selected' : '' }}>Onsite</option>
        <option value="Hybrid" {{ ($filters['work_type'] ?? '') === 'Hybrid' ? 'selected' : '' }}>Hybrid</option>
    </select>

    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
    <a href="{{ route('talent.cari') }}" class="btn btn-secondary btn-sm">Reset</a>
</form>

<div style="margin-bottom: 18px; font-size: 13.5px; color: var(--muted);">
    Ditemukan <strong>{{ $projects->count() }}</strong> proyek aktif.
</div>

@if($projects->count() > 0)
    <div class="project-grid">
        @foreach($projects as $p)
            @php
                $applyStatus = $applyStatuses[$p->id] ?? null;
                $scoreClass = $p->match_score >= 80 ? 'high' : ($p->match_score >= 60 ? 'medium' : 'low');
            @endphp
            <article class="project-card">
                <div>
                    <div class="project-card-top">
                        <span class="provider">{{ $p->provider_name }}</span>
                        <span class="match-score-badge {{ $scoreClass }}">
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
    <div style="background: #fff; padding: 40px; border-radius: 12px; text-align: center; border: 1px dashed var(--line);">
        <p style="color: var(--muted);">Tidak ada proyek yang sesuai dengan kriteria pencarian Anda.</p>
    </div>
@endif

<!-- Project Detail & Apply Modal -->
@include('talent.partials.project-modal')
@endsection

