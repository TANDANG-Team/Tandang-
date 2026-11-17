{{--
    Form penilaian bertimbang. $completion, $aspects, $ratee, $action (URL)
--}}
<form method="POST" action="{{ $action }}" class="rep-card" id="ratingForm" style="max-width:760px;">
    @csrf
    <div style="display:flex; gap:14px; align-items:center; margin-bottom:6px;">
        <div class="eng-thumb" style="width:96px;"><img src="{{ $completion->project?->cover_url }}" alt=""></div>
        <div style="min-width:0;">
            <div style="font-size:12px; font-weight:700; color:#0c7566; text-transform:uppercase; letter-spacing:.4px;">Menilai {{ $ratee?->name }}</div>
            <h2 style="font-size:18px; font-weight:800; color:#182238; margin:2px 0 0; overflow-wrap:anywhere;">{{ $completion->project?->title }}</h2>
            <div style="font-size:12.5px; color:#64748b; margin-top:2px;">Selesai {{ \App\Support\Wib::format($completion->completed_at) }} · batas menilai {{ \App\Support\Wib::format($completion->rating_deadline_at) }}</div>
        </div>
    </div>

    <div class="sim-note" style="margin:16px 0 6px; background:#f0fbf9; border:1px solid #cdebe5; color:#0c7566; border-radius:10px; padding:10px 14px; font-size:12.5px; line-height:1.5;">
        Penilaian bersifat final dan tidak bisa diedit. Isinya disembunyikan sampai pihak lain juga menilai atau 7 hari berlalu, agar tidak ada tekanan atau balas dendam.
    </div>

    @foreach($aspects as $key => $aspect)
        <div class="rate-aspect">
            <div class="rate-aspect-label">
                {{ $aspect['label'] }}
                <small>Bobot {{ $aspect['weight'] }}%@if(! empty($aspect['not_applicable'])) · proyek tanpa kompensasi finansial, bobot dinormalisasi ke aspek lain @endif</small>
            </div>
            @if(! empty($aspect['not_applicable']))
                <span class="rate-na">Tidak Berlaku (N/A)</span>
            @else
                <div class="star-input" role="radiogroup" aria-label="{{ $aspect['label'] }}">
                    @for($s = 5; $s >= 1; $s--)
                        <input type="radio" id="s-{{ $key }}-{{ $s }}" name="scores[{{ $key }}]" value="{{ $s }}" data-weight="{{ $aspect['weight'] }}" required {{ (int) old("scores.$key") === $s ? 'checked' : '' }}>
                        <label for="s-{{ $key }}-{{ $s }}" title="{{ $s }} bintang"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.5l2.94 5.96 6.58.96-4.76 4.64 1.12 6.55L12 17.52l-5.88 3.09 1.12-6.55L2.48 9.42l6.58-.96z"/></svg><span style="position:absolute; width:1px; height:1px; overflow:hidden; clip:rect(0 0 0 0);">{{ $s }} bintang</span></label>
                    @endfor
                </div>
            @endif
        </div>
    @endforeach

    <div class="rate-live" aria-live="polite">
        <span>Nilai akhir (rata-rata tertimbang)</span>
        <b id="liveScore">—</b>
    </div>

    <div style="margin-top:18px;">
        <label for="review" style="display:flex; justify-content:space-between; font-size:13.5px; font-weight:700; color:#182238; margin-bottom:6px;">
            Ulasan singkat <span style="font-weight:500; color:#94a3b8;"><span id="reviewCount">0</span>/500</span>
        </label>
        <textarea id="review" name="review" maxlength="500" rows="4" placeholder="Ceritakan pengalaman kerja secara objektif dan sopan…" style="width:100%; box-sizing:border-box; border:1.5px solid #e2e8f0; border-radius:10px; padding:12px; font:inherit; font-size:13.5px;">{{ old('review') }}</textarea>
    </div>

    <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:18px; flex-wrap:wrap;">
        <a href="{{ url()->previous() }}" class="tdg-btn ghost">Batal</a>
        <button type="submit" class="tdg-btn primary">Kirim Penilaian</button>
    </div>
</form>

<script>
(function () {
    const form = document.getElementById('ratingForm');
    const out = document.getElementById('liveScore');
    const review = document.getElementById('review');
    const count = document.getElementById('reviewCount');
    function recalc() {
        let sum = 0, weight = 0;
        form.querySelectorAll('.star-input input:checked').forEach(i => { sum += +i.value * +i.dataset.weight; weight += +i.dataset.weight; });
        out.textContent = weight ? (sum / weight).toFixed(2).replace('.', ',') + ' / 5' : '—';
    }
    form.addEventListener('change', recalc);
    review.addEventListener('input', () => count.textContent = review.value.length);
    count.textContent = review.value.length;
    recalc();
})();
</script>
