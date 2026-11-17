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
            @include('talent.partials.project-card', ['applyStatus' => $applyStatuses[$p->id] ?? null, 'showBreakdown' => true])
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

