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

@php $total = $projects->count() + $featuredProjects->count(); @endphp
<div style="margin-bottom: 18px; font-size: 13.5px; color: var(--muted);">
    Ditemukan <strong>{{ $total }}</strong> proyek aktif.
</div>

{{-- Featured Sponsored Project: carousel lebar penuh (maks. 5) di atas hasil, hanya proyek yang relevan dengan filter. --}}
@include('partials.sponsored-carousel', ['projects' => $featuredProjects, 'applyStatuses' => $applyStatuses])

@if($projects->count() > 0)
    <div class="project-grid">
        @foreach($projects as $p)
            @include('talent.partials.project-card', ['applyStatus' => $applyStatuses[$p->id] ?? null])
        @endforeach
    </div>
@elseif($featuredProjects->isEmpty())
    <div style="background: #fff; padding: 40px; border-radius: 12px; text-align: center; border: 1px dashed var(--line);">
        <p style="color: var(--muted);">Tidak ada proyek yang sesuai dengan kriteria pencarian Anda.</p>
    </div>
@endif

<!-- Project Detail & Apply Modal -->
@include('talent.partials.project-modal')

@include('talent.partials.promo-tracking')
@endsection
