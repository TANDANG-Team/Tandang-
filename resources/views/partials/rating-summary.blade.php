{{--
    Ringkasan reputasi publik.
    $summary : hasil RatingService::summaryFor()
    $subject : 'Talenta' | 'Penyedia'
    $reportRoute : (opsional) nama route laporan, ditampilkan bila $viewerId adalah pihak yang dinilai
--}}
@php
    $subject = $subject ?? 'Pengguna';
    $avg = $summary['average'];
    $raterLabel = $subject === 'Talenta' ? 'Penyedia' : 'Talenta';
@endphp
<section class="rep-card" aria-labelledby="rep-title-{{ $subject }}">
    <div class="rep-head">
        <div class="rep-score">
            @if($avg !== null)
                <b>{{ number_format($avg, 1, ',', '') }}</b>
                @include('partials.stars', ['score' => $avg])
                <span style="display:block; margin-top:4px;">{{ $summary['label'] }}</span>
            @else
                <b style="font-size:22px;">—</b>
                <span>Belum Ada Penilaian</span>
            @endif
        </div>
        <div>
            <h2 class="rep-title" id="rep-title-{{ $subject }}">Reputasi {{ $subject }}</h2>
            <p class="rep-sub">
                @if($avg !== null)
                    <strong>★ {{ number_format($avg, 1, ',', '') }} / 5 ({{ $summary['count'] }} penilaian) — {{ $summary['label'] }}.</strong>
                    Berdasarkan {{ $summary['count'] }} penilaian dari proyek yang selesai.
                @else
                    Reputasi terbentuk dari penilaian {{ $raterLabel }} setelah proyek benar-benar selesai, bukan dari jumlah lamaran.
                @endif
            </p>
            <div class="rep-facts">
                <span><strong>{{ $summary['completed'] }}</strong> proyek selesai</span>
                <span><strong>{{ $summary['count'] }}</strong> proyek dinilai</span>
            </div>
        </div>
    </div>

    @if($avg !== null)
        <h3 style="font-size:13px; font-weight:800; color:#182238; margin:0 0 10px; text-transform:uppercase; letter-spacing:.4px;">Ringkasan aspek</h3>
        <div class="rep-aspects">
            @foreach($summary['aspects'] as $aspect)
                <div class="rep-aspect">
                    <span>{{ $aspect['label'] }} <span style="color:#94a3b8;">· {{ $aspect['weight'] }}%</span></span>
                    <div class="rep-bar" aria-hidden="true"><i style="width: {{ $aspect['average'] ? ($aspect['average'] / 5 * 100) : 0 }}%"></i></div>
                    <b>{{ $aspect['average'] !== null ? number_format($aspect['average'], 1, ',', '') : 'N/A' }}</b>
                </div>
            @endforeach
        </div>

        <h3 style="font-size:13px; font-weight:800; color:#182238; margin:0 0 10px; text-transform:uppercase; letter-spacing:.4px;">Ulasan dari {{ $raterLabel }}</h3>
        <div class="rep-reviews">
            @forelse($summary['reviews'] as $review)
                <article class="rep-review">
                    <div class="rep-review-head">
                        <span><strong>{{ $review->rater?->name ?? 'Pengguna' }}</strong> · {{ $review->project?->title }}</span>
                        <span style="display:inline-flex; align-items:center; gap:6px;">
                            @include('partials.stars', ['score' => (float) $review->final_score])
                            {{ number_format((float) $review->final_score, 2, ',', '') }}
                        </span>
                    </div>
                    <p>{{ $review->visible_review }}</p>
                    @if(! empty($reportRoute) && auth()->id() === $review->ratee_id)
                        <details style="margin-top:8px;">
                            <summary style="cursor:pointer; font-size:12px; color:#94a3b8;">Laporkan ulasan ini</summary>
                            <form method="POST" action="{{ route($reportRoute, $review) }}" style="margin-top:8px; display:grid; gap:8px;">
                                @csrf
                                <input type="hidden" name="type" value="violation">
                                <textarea name="reason" required minlength="10" maxlength="1000" placeholder="Jelaskan pelanggaran (misal: kata kasar, data pribadi, tidak relevan)…" style="border:1.5px solid #e2e8f0; border-radius:10px; padding:10px; font:inherit; font-size:13px;"></textarea>
                                <button class="tdg-btn ghost sm" type="submit" style="justify-self:start;">Kirim laporan ke Admin</button>
                            </form>
                        </details>
                    @endif
                </article>
            @empty
                <div class="rep-empty">Belum ada ulasan tertulis.</div>
            @endforelse
        </div>
    @else
        <div class="rep-empty">
            <strong>Belum Ada Penilaian</strong>
            Penilaian muncul setelah proyek dikonfirmasi selesai dan kedua pihak menilai (atau setelah 7 hari).
        </div>
    @endif
</section>
