{{-- Bintang 1–5 (dibulatkan ke setengah terdekat secara visual: ≥ .5 dianggap penuh). --}}
@php $filled = $score === null ? 0 : (int) floor((float) $score + 0.5); @endphp
<span class="tdg-stars" role="img" aria-label="{{ $score === null ? 'Belum ada penilaian' : number_format((float) $score, 1, ',', '') . ' dari 5 bintang' }}">
    @for($i = 1; $i <= 5; $i++)
        <svg viewBox="0 0 24 24" class="{{ $i <= $filled ? 'on' : 'off' }}" fill="currentColor" aria-hidden="true"><path d="M12 2.5l2.94 5.96 6.58.96-4.76 4.64 1.12 6.55L12 17.52l-5.88 3.09 1.12-6.55L2.48 9.42l6.58-.96z"/></svg>
    @endfor
</span>
