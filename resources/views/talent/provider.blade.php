@extends('talent.layout')

@section('title', 'Profil Penyedia')

@section('content')
@php $org = $provider->organizationProfile; @endphp
<div class="page-head">
    <div>
        <h1>{{ $org?->organization_name ?: $provider->name }}</h1>
        <p>{{ $org?->industry_type ?: 'Penyedia Proyek' }}{{ ($org?->city ?? null) ? ' · ' . $org->city : '' }}</p>
    </div>
    <a href="{{ route('talent.cari') }}" class="btn btn-secondary btn-sm">&larr; Cari Proyek</a>
</div>

@if($org?->description ?? null)
    <div class="rep-card" style="margin-bottom:20px;">
        <h2 class="rep-title">Tentang Penyedia</h2>
        <p class="rep-sub" style="color:#334155;">{{ $org->description }}</p>
    </div>
@endif

@include('partials.rating-summary', ['summary' => $summary, 'subject' => 'Penyedia'])

<div class="section-title" style="margin-top:28px;">
    <h2>Proyek yang sedang dibuka</h2>
</div>
@if($openProjects->isEmpty())
    <div class="rep-card rep-empty">Penyedia ini belum memiliki proyek yang menerima lamaran.</div>
@else
    <div class="project-grid">
        @foreach($openProjects as $p)
            <article class="project-card has-cover">
                <div>
                    <div class="project-cover"><img src="{{ $p->cover_url }}" alt="" loading="lazy"></div>
                    <h3>{{ $p->title }}</h3>
                    <p class="desc">{{ $p->description }}</p>
                    <div class="project-meta-pills">
                        <span class="meta-pill">{{ $p->location }} ({{ $p->work_type }})</span>
                        <span class="meta-pill">{{ $p->category }}</span>
                    </div>
                </div>
                <div class="project-card-footer">
                    <span class="comp">{{ $p->compensation ?: 'Sesuai Kesepakatan' }}</span>
                    <a href="{{ route('talent.cari', ['q' => $p->title]) }}" class="btn btn-secondary btn-sm">Lihat &rarr;</a>
                </div>
            </article>
        @endforeach
    </div>
@endif
@endsection
