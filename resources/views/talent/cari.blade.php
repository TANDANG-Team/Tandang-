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

@php $total = $projects->count() + ($featuredProject ? 1 : 0); @endphp
<div style="margin-bottom: 18px; font-size: 13.5px; color: var(--muted);">
    Ditemukan <strong>{{ $total }}</strong> proyek aktif.
</div>

{{-- Featured Sponsored Project: satu kartu lebar penuh di atas hasil, hanya bila relevan dengan filter. --}}
@if($featuredProject)
    @include('partials.sponsored-feature', [
        'project'   => $featuredProject,
        'applyStatus' => $applyStatuses[$featuredProject->id] ?? null,
    ])
@endif

@if($projects->count() > 0)
    <div class="project-grid">
        @foreach($projects as $p)
            @php
                $applyStatus = $applyStatuses[$p->id] ?? null;
                $scoreClass = $p->match_score >= 80 ? 'high' : ($p->match_score >= 60 ? 'medium' : 'low');
            @endphp
            <article class="project-card has-cover {{ $p->promotion_id ? 'is-sponsored' : '' }}"
                @if($p->promotion_id) data-promo-id="{{ $p->promotion_id }}" data-placement="list" @endif>
                <div>
                    <div class="project-cover">
                        <img src="{{ $p->cover_url }}" alt="" loading="lazy">
                        @if($p->promotion_id)
                            <span class="tdg-pill sponsor">
                                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2.5l2.94 5.96 6.58.96-4.76 4.64 1.12 6.55L12 17.52l-5.88 3.09 1.12-6.55L2.48 9.42l6.58-.96z"/></svg>
                                Dipromosikan
                            </span>
                        @endif
                    </div>

                    <div class="project-card-top">
                        <span class="provider">{{ $p->provider_name }}</span>
                        <span class="match-score-badge {{ $scoreClass }}">
                            {{ $p->match_score }}% Cocok
                        </span>
                    </div>

                    <h3>{{ $p->title }}</h3>
                    @if($p->provider_rating !== null)
                        <div class="tdg-rating-inline" style="margin:-2px 0 8px;">
                            @include('partials.stars', ['score' => $p->provider_rating])
                            {{ number_format($p->provider_rating, 1, ',', '') }} / 5
                            <span class="count">({{ $p->provider_rating_count }} penilaian)</span>
                        </div>
                    @else
                        <div class="tdg-rating-inline" style="margin:-2px 0 8px;"><span class="count">Belum Ada Penilaian</span></div>
                    @endif
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
                    <button type="button" class="btn btn-secondary btn-sm {{ $p->promotion_id ? 'js-promo-cta' : '' }}" onclick="openDetailModal({{ json_encode($p) }}, {{ json_encode($applyStatus) }})">
                        {{ ($applyStatus['can_apply'] ?? true) ? 'Lihat Detail & Lamar' : 'Lihat Detail' }} &rarr;
                    </button>
                </div>
            </article>
        @endforeach
    </div>
@elseif(! $featuredProject)
    <div style="background: #fff; padding: 40px; border-radius: 12px; text-align: center; border: 1px dashed var(--line);">
        <p style="color: var(--muted);">Tidak ada proyek yang sesuai dengan kriteria pencarian Anda.</p>
    </div>
@endif

<!-- Project Detail & Apply Modal -->
@include('talent.partials.project-modal')

@push('scripts')
<script>
/* ── Tracking Sponsored Project ───────────────────────────────────────────
   Impression: ≥50% kartu terlihat selama ≥1 detik, maksimal sekali per promosi per muat-halaman.
   Klik: dikirim ke server saat CTA kartu promosi diklik (server melakukan dedup & atribusi). */
(function () {
    const cards = document.querySelectorAll('[data-promo-id]');
    if (!cards.length) return;

    const pageToken = @json($pageToken);
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const base = '{{ url('/talent/promosi') }}';
    const minRatio = {{ config('promotion.impression_visible_ratio') }};
    const minMs = {{ config('promotion.impression_min_ms') }};
    const sent = new Set();
    const timers = new Map();

    function post(url, body) {
        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify(body),
            keepalive: true,
            credentials: 'same-origin',
        }).catch(() => {});
    }

    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                const el = entry.target;
                const id = el.dataset.promoId;
                if (sent.has(id)) return;
                if (entry.isIntersecting && entry.intersectionRatio >= minRatio) {
                    if (!timers.has(el)) {
                        timers.set(el, setTimeout(() => {
                            sent.add(id);
                            observer.unobserve(el);
                            post(`${base}/${id}/impression`, { page_token: pageToken, placement: el.dataset.placement });
                        }, minMs));
                    }
                } else if (timers.has(el)) {
                    clearTimeout(timers.get(el));
                    timers.delete(el);
                }
            });
        }, { threshold: [0, minRatio, 1] });
        cards.forEach(c => observer.observe(c));
    }

    cards.forEach(card => {
        card.querySelectorAll('.js-promo-cta').forEach(btn => {
            btn.addEventListener('click', () => {
                post(`${base}/${card.dataset.promoId}/click`, { placement: card.dataset.placement });
            });
        });
    });
})();
</script>
@endpush
@endsection
