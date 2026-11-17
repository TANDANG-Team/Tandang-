{{--
    Kartu proyek untuk Talenta (beranda, rekomendasi, cari). Seluruh kartu bisa diklik untuk membuka modal detail.
    $p             : Project (sudah berisi match_score / match_data)
    $applyStatus   : ?array hasil ProjectApplyService::getApplyStatus
    $showRating    : bool — tampilkan rating Penyedia (halaman cari)
    $showBreakdown : bool — tampilkan rincian kecocokan singkat (halaman rekomendasi)
--}}
@php
    $showRating = $showRating ?? false;
    $showBreakdown = $showBreakdown ?? false;
    $scoreClass = $p->match_score >= 80 ? 'high' : ($p->match_score >= 60 ? 'medium' : 'low');
    $skills = $p->skills ?? [];
    $matchedSkills = $p->match_data['matched_skills'] ?? [];
    $visibleSkills = array_slice($skills, 0, 3);
    $hiddenSkills = count($skills) - count($visibleSkills);
    $workType = $p->work_type ?: ucfirst((string) $p->work_method);
    $location = $p->location ?: 'Remote';
    $where = strcasecmp($location, $workType) === 0 || ! $workType ? $location : "{$location} ({$workType})";
    $canApply = $applyStatus['can_apply'] ?? true;
    $components = $p->match_data['components'] ?? [];
@endphp
<article class="project-card has-cover js-detail-card pcard {{ $p->promotion_id ? 'is-sponsored' : '' }}"
    @if($p->promotion_id) data-promo-id="{{ $p->promotion_id }}" data-placement="list" @endif>
    <div class="project-cover">
        <img src="{{ $p->cover_url }}" alt="" loading="lazy">
        @if($p->promotion_id)
            <span class="tdg-pill sponsor">
                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2.5l2.94 5.96 6.58.96-4.76 4.64 1.12 6.55L12 17.52l-5.88 3.09 1.12-6.55L2.48 9.42l6.58-.96z"/></svg>
                Dipromosikan
            </span>
        @endif
        <span class="match-score-badge pcard-score {{ $scoreClass }}">{{ $p->match_score }}% Cocok</span>
    </div>

    <div class="pcard-body">
        <span class="pcard-provider">{{ $p->provider_name }}</span>
        <h3 class="pcard-title">{{ $p->title }}</h3>

        @if($showRating)
            <div class="tdg-rating-inline pcard-rating">
                @if($p->provider_rating !== null)
                    @include('partials.stars', ['score' => $p->provider_rating])
                    {{ number_format($p->provider_rating, 1, ',', '') }} / 5
                    <span class="count">({{ $p->provider_rating_count }} penilaian)</span>
                @else
                    <span class="count">Belum Ada Penilaian</span>
                @endif
            </div>
        @endif

        <p class="pcard-desc">{{ $p->description }}</p>

        <ul class="pcard-meta">
            <li>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                {{ $where }}
            </li>
            @if($p->schedule)
                <li>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    {{ $p->schedule }}
                </li>
            @endif
            <li title="Pelamar aktif (menunggu + review + diterima) dari batas 2× talenta dibutuhkan. Pelamar yang ditolak/dibatalkan tidak dihitung.">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Butuh {{ $applyStatus['talents_needed'] ?? $p->talents_needed }} talenta
                @if($applyStatus)
                    <span class="pcard-dot">·</span> {{ $applyStatus['active_count'] }}/{{ $applyStatus['max_applicants'] }} pelamar
                @endif
            </li>
        </ul>

        @if($applyStatus && ! $canApply)
            @php
                $reasonBadge = match ($applyStatus['reason']) {
                    'rejected' => 'badge-danger',
                    'already_applied' => 'badge-info',
                    'cancelled' => 'badge-purple',
                    default => 'badge-warning',
                };
            @endphp
            <span class="badge {{ $reasonBadge }} pcard-reason" title="{{ $applyStatus['message'] }}">{{ $applyStatus['reason_label'] }}</span>
        @endif

        @if($showBreakdown && $components)
            <dl class="pcard-breakdown">
                <div><dt>Kompetensi</dt><dd>{{ $components['skills']['detail'] ?? '-' }}</dd></div>
                <div><dt>Gaya kerja</dt><dd>{{ $components['workstyle']['detail'] ?? '-' }}</dd></div>
                <div><dt>Lokasi & waktu</dt><dd>{{ $components['location']['detail'] ?? '-' }}</dd></div>
            </dl>
        @endif

        @if($skills)
            <div class="pcard-skills">
                @foreach($visibleSkills as $sk)
                    @php $isMatched = in_array($sk, $matchedSkills); @endphp
                    <span class="skill-tag {{ $isMatched ? 'matched' : '' }}">{{ $sk }}{{ $isMatched ? ' ✓' : '' }}</span>
                @endforeach
                @if($hiddenSkills > 0)
                    <span class="skill-tag pcard-more" title="{{ implode(', ', array_slice($skills, 3)) }}">+{{ $hiddenSkills }}</span>
                @endif
            </div>
        @endif
    </div>

    <div class="pcard-footer">
        <div class="pcard-comp">
            <small>Kompensasi</small>
            <strong>{{ $p->compensation ?: 'Sesuai kesepakatan' }}</strong>
        </div>
        <button type="button"
            class="btn {{ $canApply ? 'btn-primary' : 'btn-secondary' }} pcard-cta js-detail-trigger {{ $p->promotion_id ? 'js-promo-cta' : '' }}"
            onclick="openDetailModal({{ json_encode($p) }}, {{ json_encode($applyStatus) }})">
            {{ $canApply ? 'Lihat Detail & Lamar' : 'Lihat Detail' }}
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </button>
    </div>
</article>
