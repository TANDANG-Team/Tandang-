{{--
    Rating Penyedia di kartu proyek Talenta: "★★★★★ 4,6 / 5 (3 penilaian)".
    Tanpa rating valid → bintang abu-abu + "Rating tidak tersedia" (bukan 0,0).
    $rating : ?float  rata-rata (null = belum ada)
    $count  : int     jumlah penilaian
    $class  : ?string kelas tambahan
--}}
<div class="tdg-rating-inline {{ $class ?? '' }} {{ $rating === null ? 'is-empty' : '' }}">
    @include('partials.stars', ['score' => $rating])
    @if($rating !== null)
        {{ number_format($rating, 1, ',', '') }} / 5
        <span class="count">({{ $count }} penilaian)</span>
    @else
        <span class="count">Rating tidak tersedia</span>
    @endif
</div>
