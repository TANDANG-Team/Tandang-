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
        'isApplied' => in_array($featuredProject->id, $appliedProjectIds),
    ])
@endif

@if($projects->count() > 0)
    <div class="project-grid">
        @foreach($projects as $p)
            @php
                $isApplied = in_array($p->id, $appliedProjectIds);
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
                            Kuota: {{ $p->quota }}
                        </span>
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
                    <button type="button" class="btn btn-secondary btn-sm {{ $p->promotion_id ? 'js-promo-cta' : '' }}" onclick='openDetailModal(@json($p), {{ $isApplied ? 'true' : 'false' }})'>
                        Lihat Detail & Lamar &rarr;
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
<div id="projectModal" class="modal-overlay">
    <div class="modal-box">
        <div style="margin:-4px -4px 16px; border-radius:12px; overflow:hidden; aspect-ratio:16/6; background:var(--teal-light);">
            <img id="modalCover" src="" alt="" style="width:100%; height:100%; object-fit:cover; display:block;">
        </div>
        <div class="modal-header">
            <div>
                <span id="modalProvider" style="font-size: 12px; font-weight: 700; color: var(--teal); text-transform: uppercase;"></span>
                <div id="modalProviderRating" style="font-size:12.5px; margin-top:2px;"></div>
                <h2 id="modalTitle" style="margin-top: 4px;"></h2>
            </div>
            <button type="button" class="btn-close" onclick="closeDetailModal()">&times;</button>
        </div>

        <div style="margin-bottom: 20px;">
            <div id="modalScoreBox" style="display: flex; align-items: center; justify-content: space-between; background: var(--teal-light); padding: 12px 16px; border-radius: 10px; margin-bottom: 16px;">
                <span style="font-weight: 700; color: var(--teal-dark);">Kecocokan Gaya Kerja & Skill</span>
                <span id="modalScoreBadge" style="font-size: 16px; font-weight: 800; color: var(--teal-dark);"></span>
            </div>

            <h4 style="font-size: 13.5px; font-weight: 700; margin-bottom: 6px;">Deskripsi Proyek</h4>
            <p id="modalDesc" style="font-size: 13.5px; color: #475569; line-height: 1.6; margin-bottom: 16px;"></p>

            <h4 style="font-size: 13.5px; font-weight: 700; margin-bottom: 6px;">Tanggung Jawab</h4>
            <p id="modalResp" style="font-size: 13.5px; color: #475569; line-height: 1.6; margin-bottom: 16px;"></p>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 16px; font-size: 13px;">
                <div style="background: #f8fafc; padding: 10px; border-radius: 8px;">
                    <strong>Lokasi:</strong> <span id="modalLoc"></span>
                </div>
                <div style="background: #f8fafc; padding: 10px; border-radius: 8px;">
                    <strong>Jadwal:</strong> <span id="modalSchedule"></span>
                </div>
                <div style="background: #f8fafc; padding: 10px; border-radius: 8px;">
                    <strong>Kompensasi:</strong> <span id="modalComp"></span>
                </div>
                <div style="background: #f8fafc; padding: 10px; border-radius: 8px;">
                    <strong>Batas Lamaran:</strong> <span id="modalDeadline"></span>
                </div>
            </div>

            <h4 style="font-size: 13.5px; font-weight: 700; margin-bottom: 8px;">Kompetensi yang Dibutuhkan</h4>
            <div id="modalSkills" class="skill-tags" style="margin-bottom: 24px;"></div>

            <form id="applyForm" method="POST" action="">
                @csrf
                <div id="applyInputs">
                    <div class="form-group">
                        <label for="message">Pesan Pengantar Lamaran (Opsional)</label>
                        <textarea name="message" id="message" class="form-control" placeholder="Ceritakan singkat motivasi dan pengalaman relevanmu..."></textarea>
                    </div>
                    <div style="display: flex; justify-content: flex-end; gap: 10px;">
                        <button type="button" class="btn btn-secondary" onclick="closeDetailModal()">Batal</button>
                        <button type="submit" class="btn btn-primary">Kirim Lamaran Sekarang</button>
                    </div>
                </div>
                <div id="alreadyAppliedNotice" style="display: none; background: #f1f5f9; padding: 14px; border-radius: 8px; text-align: center; color: var(--muted); font-size: 13.5px; font-weight: 600;">
                    ✓ Anda sudah melamar ke proyek ini. Cek status di menu <strong>Lamaran Saya</strong>.
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function openDetailModal(project, isApplied) {
    document.getElementById('modalCover').src = project.cover_url || '';
    document.getElementById('modalProvider').innerText = project.provider_name;
    const ratingBox = document.getElementById('modalProviderRating');
    ratingBox.innerHTML = '';
    if (project.user_id) {
        const link = document.createElement('a');
        link.href = '{{ url('/talent/penyedia') }}/' + project.user_id;
        link.style.cssText = 'color:var(--teal-dark); font-weight:600; text-decoration:none;';
        link.textContent = project.provider_rating !== null && project.provider_rating !== undefined
            ? '★ ' + Number(project.provider_rating).toFixed(1).replace('.', ',') + ' / 5 (' + project.provider_rating_count + ' penilaian) · Lihat profil Penyedia'
            : 'Belum Ada Penilaian · Lihat profil Penyedia';
        ratingBox.appendChild(link);
    }
    document.getElementById('modalTitle').innerText = project.title;
    document.getElementById('modalScoreBadge').innerText = (project.match_score || 0) + '% Cocok';
    document.getElementById('modalDesc').innerText = project.description || '-';
    document.getElementById('modalResp').innerText = project.responsibilities || '-';
    document.getElementById('modalLoc').innerText = (project.location || 'Remote') + ' (' + (project.work_type || 'Remote') + ')';
    document.getElementById('modalSchedule').innerText = project.schedule || 'Fleksibel';
    document.getElementById('modalComp').innerText = project.compensation || 'Sesuai Kesepakatan';
    document.getElementById('modalDeadline').innerText = project.deadline ? new Date(project.deadline).toLocaleDateString('id-ID') : '-';

    const skillsContainer = document.getElementById('modalSkills');
    skillsContainer.innerHTML = '';
    (project.skills || []).forEach(s => {
        const tag = document.createElement('span');
        tag.className = 'skill-tag';
        tag.innerText = s;
        skillsContainer.appendChild(tag);
    });

    document.getElementById('applyForm').action = `/talent/proyek/${project.id}/lamar`;
    document.getElementById('applyInputs').style.display = isApplied ? 'none' : 'block';
    document.getElementById('alreadyAppliedNotice').style.display = isApplied ? 'block' : 'none';

    document.getElementById('projectModal').classList.add('active');
}

function closeDetailModal() {
    document.getElementById('projectModal').classList.remove('active');
}

document.getElementById('projectModal').addEventListener('click', function(e) {
    if (e.target === this) closeDetailModal();
});

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
