{{--
    Kartu Featured Sponsored Project (horizontal di desktop, vertikal di tablet/mobile).
    $project  : Project
    $preview  : bool — mode pratinjau di halaman checkout (tanpa tracking & tombol aktif)
    $applyStatus: ?array — hasil ProjectApplyService::getApplyStatus (null = belum diketahui)
--}}
@php
    $preview = $preview ?? false;
    $applyStatus = $applyStatus ?? null;
    $initial = strtoupper(mb_substr($project->provider_name ?: 'T', 0, 1));
@endphp
<article class="sponsored-feature"
    @unless($preview)
        data-promo-id="{{ $project->promotion_id }}" data-placement="banner"
    @endunless
    aria-label="Proyek dipromosikan: {{ $project->title }}">
    <div class="sponsored-feature__media">
        <img src="{{ $project->cover_url }}" alt="" loading="lazy">
    </div>
    <div class="sponsored-feature__body">
        <div class="sponsored-feature__eyebrow">
            <span class="tdg-pill sponsor">
                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2.5l2.94 5.96 6.58.96-4.76 4.64 1.12 6.55L12 17.52l-5.88 3.09 1.12-6.55L2.48 9.42l6.58-.96z"/></svg>
                Dipromosikan
            </span>
            @if($project->category)
                <span class="tdg-pill muted">{{ $project->category }}</span>
            @endif
        </div>

        <div class="sponsored-feature__provider">
            <span class="sponsored-feature__logo" aria-hidden="true">{{ $initial }}</span>
            <div style="min-width:0;">
                <div class="sponsored-feature__provider-name">{{ $project->provider_name }}</div>
                @if(($project->provider_rating ?? null) !== null)
                    <span class="tdg-rating-inline">
                        @include('partials.stars', ['score' => $project->provider_rating])
                        {{ number_format($project->provider_rating, 1, ',', '') }}
                        / 5 <span class="count">({{ $project->provider_rating_count }} penilaian)</span>
                    </span>
                @else
                    <span class="tdg-rating-inline"><span class="count">Belum Ada Penilaian</span></span>
                @endif
            </div>
        </div>

        <h3 class="sponsored-feature__title">{{ $project->title }}</h3>
        <p class="sponsored-feature__desc">{{ $project->description }}</p>

        <div class="sponsored-feature__meta">
            <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>{{ $project->location ?: 'Remote' }}</span>
            <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>{{ ucfirst($project->work_type ?: $project->work_method) }}</span>
            <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>Butuh {{ max(1, (int) $project->talents_needed) }} Talenta</span>
        </div>

        <div class="sponsored-feature__footer">
            <div class="sponsored-feature__comp">
                <small>Kompensasi</small>
                {{ $project->compensation ?: 'Sesuai kesepakatan' }}
            </div>
            @if($preview)
                <span class="tdg-btn primary" aria-disabled="true">Lihat Detail Proyek &rarr;</span>
            @else
                <button type="button" class="tdg-btn primary js-promo-cta"
                        onclick='openDetailModal(@json($project), @json($applyStatus))'>
                    Lihat Detail Proyek &rarr;
                </button>
            @endif
        </div>
    </div>
</article>
