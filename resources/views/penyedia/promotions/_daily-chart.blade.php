{{--
    Grafik batang harian satu seri (small multiple). Satu sumbu Y per grafik — tidak ada dual-axis.
    $days  : dari PromotionStatsService::daily()
    $key   : 'impressions' | 'clicks'
    $color : warna seri (sudah divalidasi CVD bersama pasangannya)
    $title, $subtitle
--}}
@php
    $W = 320; $H = 190; $padL = 34; $padR = 8; $padT = 22; $padB = 28;
    $plotW = $W - $padL - $padR; $plotH = $H - $padT - $padB;
    $values = collect($days)->pluck($key);
    $max = max(1, (int) $values->max());
    // Skala "rapi": 1, 2, 5 × 10^n
    $mag = 10 ** max(0, (int) floor(log10($max)));
    $niceMax = collect([1, 2, 5, 10])->map(fn ($m) => $m * $mag)->first(fn ($v) => $v >= $max);
    $ticks = [0, $niceMax / 2, $niceMax];
    $n = count($days);
    $slot = $plotW / max(1, $n);
    $barW = min(42, $slot * 0.56);
    $r = 4;
@endphp
<figure class="pr-chart" style="margin:0;">
    <h3>{{ $title }}</h3>
    <p>{{ $subtitle }}</p>
    <svg viewBox="0 0 {{ $W }} {{ $H }}" role="img" aria-label="{{ $title }} per hari: {{ collect($days)->map(fn ($d) => $d['label'] . ' ' . $d[$key])->implode(', ') }}">
        @foreach($ticks as $t)
            @php $y = $padT + $plotH - ($t / $niceMax) * $plotH; @endphp
            <line class="grid" x1="{{ $padL }}" x2="{{ $W - $padR }}" y1="{{ $y }}" y2="{{ $y }}"/>
            <text class="axis-label" x="{{ $padL - 6 }}" y="{{ $y + 4 }}" text-anchor="end">{{ number_format($t, 0, ',', '.') }}</text>
        @endforeach

        @foreach($days as $i => $d)
            @php
                $idx = $i - 1;
                $cx = $padL + $slot * $idx + $slot / 2;
                $x = $cx - $barW / 2;
                $v = (int) $d[$key];
                $h = $v > 0 ? max(3, ($v / $niceMax) * $plotH) : 0;
                $yTop = $padT + $plotH - $h;
                $base = $padT + $plotH;
                $notRun = in_array($d['state'], ['upcoming', 'stopped'], true);
            @endphp
            <g class="bar-group">
                <title>{{ $d['label'] }}{{ $d['start'] ? ' (' . \App\Support\Wib::date($d['start']) . ')' : '' }}: {{ $notRun ? ($d['state'] === 'stopped' ? 'dihentikan' : 'belum berjalan') : number_format($v, 0, ',', '.') . ' ' . strtolower($title) }}</title>
                <rect class="bar-hit" x="{{ $padL + $slot * $idx }}" y="{{ $padT }}" width="{{ $slot }}" height="{{ $plotH }}"/>
                @if($notRun)
                    <rect class="upcoming" x="{{ $x }}" y="{{ $base - 28 }}" width="{{ $barW }}" height="28" rx="4"/>
                    <text class="axis-label" x="{{ $cx }}" y="{{ $base - 34 }}" text-anchor="middle">—</text>
                @elseif($h > 0)
                    {{-- Batang dengan sudut atas membulat 4px, dasar rata di baseline --}}
                    <path class="bar" fill="{{ $color }}" d="M{{ $x }},{{ $base }} V{{ $yTop + $r }} Q{{ $x }},{{ $yTop }} {{ $x + $r }},{{ $yTop }} H{{ $x + $barW - $r }} Q{{ $x + $barW }},{{ $yTop }} {{ $x + $barW }},{{ $yTop + $r }} V{{ $base }} Z"/>
                    <text class="val" x="{{ $cx }}" y="{{ $yTop - 6 }}" text-anchor="middle">{{ number_format($v, 0, ',', '.') }}</text>
                @else
                    <text class="val" x="{{ $cx }}" y="{{ $base - 6 }}" text-anchor="middle">0</text>
                @endif
                <text class="axis-label" x="{{ $cx }}" y="{{ $H - 8 }}" text-anchor="middle">{{ $d['label'] }}</text>
            </g>
        @endforeach
        <line x1="{{ $padL }}" x2="{{ $W - $padR }}" y1="{{ $padT + $plotH }}" y2="{{ $padT + $plotH }}" stroke="#cbd5e1"/>
    </svg>
</figure>
