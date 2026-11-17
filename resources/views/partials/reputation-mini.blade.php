{{--
    Ringkasan reputasi untuk dashboard. $summary (RatingService::summaryFor), $title, $raterLabel, $allUrl
--}}
@php $avg = $summary['average']; @endphp
<section class="rep-card" style="padding:20px 22px;">
    <div style="display:flex; justify-content:space-between; gap:12px; align-items:flex-start; flex-wrap:wrap; margin-bottom:14px;">
        <div>
            <h2 class="rep-title" style="margin:0;">{{ $title }}</h2>
            <p class="rep-sub">Dihitung dari penilaian {{ $raterLabel }} pada proyek yang benar-benar selesai.</p>
        </div>
        <a href="{{ $allUrl }}" class="tdg-btn ghost sm">Lihat Semua Penilaian</a>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(140px, 1fr)); gap:12px;">
        <div class="rep-score" style="min-width:0;">
            @if($avg !== null)
                <b style="font-size:28px;">{{ number_format($avg, 1, ',', '') }} <span style="font-size:15px; color:#64748b;">/ 5</span></b>
                @include('partials.stars', ['score' => $avg])
                <span style="display:block; margin-top:2px;">{{ $summary['label'] }}</span>
            @else
                <b style="font-size:18px;">—</b>
                <span>Belum Ada Penilaian</span>
            @endif
        </div>
        <div class="rep-score" style="background:#fff; border-color:#eef2f6; min-width:0;">
            <b style="font-size:26px;">{{ $summary['completed'] }}</b>
            <span style="color:#64748b;">Proyek selesai</span>
        </div>
        <div class="rep-score" style="background:#fff; border-color:#eef2f6; min-width:0;">
            <b style="font-size:26px;">{{ $summary['count'] }}</b>
            <span style="color:#64748b;">Jumlah penilaian</span>
        </div>
    </div>

    @if($avg !== null)
        <p class="rep-sub" style="margin-top:12px;"><strong>{{ number_format($avg, 1, ',', '') }} / 5 ★</strong> — berdasarkan {{ $summary['count'] }} {{ $raterLabel === 'Talenta' ? 'ulasan Talenta' : 'penilaian proyek selesai' }}.</p>
        <h3 style="font-size:12.5px; font-weight:800; color:#182238; margin:14px 0 8px; text-transform:uppercase; letter-spacing:.4px;">Ulasan terbaru dari {{ $raterLabel }}</h3>
        <div class="rep-reviews">
            @forelse($summary['reviews']->take(3) as $review)
                <article class="rep-review" style="padding:12px 14px;">
                    <div class="rep-review-head">
                        <span><strong>{{ $review->rater?->name }}</strong> · {{ $review->project?->title }}</span>
                        <span>{{ number_format((float) $review->final_score, 2, ',', '') }} ★</span>
                    </div>
                    <p>{{ \Illuminate\Support\Str::limit($review->visible_review, 180) }}</p>
                </article>
            @empty
                <div class="rep-empty" style="padding:12px;">Belum ada ulasan tertulis.</div>
            @endforelse
        </div>
    @endif
</section>
