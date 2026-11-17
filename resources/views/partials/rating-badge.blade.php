{{--
    Badge rating ringkas: "★ 4,6 / 5 (12 penilaian)" atau "Belum Ada Penilaian" (bukan 0,0).
    $average : ?float (sudah 1 desimal), $count : int
--}}
@if(($average ?? null) !== null && ($count ?? 0) > 0)
    <span class="rating-badge" aria-label="Rating {{ number_format((float) $average, 1, ',', '') }} dari 5, {{ $count }} penilaian">
        <span class="rb-star" aria-hidden="true">★</span>{{ number_format((float) $average, 1, ',', '') }} / 5
        <span class="rb-count">({{ $count }} penilaian)</span>
    </span>
@else
    <span class="rating-badge is-empty"><span class="rb-star" aria-hidden="true">☆</span>Belum Ada Penilaian</span>
@endif
