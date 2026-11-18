{{--
    Carousel Featured Sponsored Project (maks. config('promotion.banner_max') kartu, default 5).
    $projects      : Collection<Project> — hasil decorateListing (urutan sudah diacak)
    $applyStatuses : array — hasil ProjectApplyService::getApplyStatuses, dikunci project id
    Satu proyek → kartu tunggal tanpa kontrol. >1 proyek → geser (swipe/scroll-snap), tombol ‹ ›,
    titik indikator, dan autoplay yang berhenti saat disorot/difokus atau bila pengguna memilih reduced motion.
    Impression tetap diukur per kartu oleh promo-tracking: slide di luar viewport carousel terpotong
    (overflow) sehingga tidak dihitung terlihat.
--}}
@php
    $items = collect($projects)->values();
    $count = $items->count();
    $applyStatuses = $applyStatuses ?? [];
@endphp
@if($count > 0)
<section class="sponsored-carousel {{ $count > 1 ? 'is-multi' : '' }}"
    aria-roledescription="carousel" aria-label="Proyek dipromosikan">
    <div class="sponsored-carousel__viewport" @if($count > 1) aria-live="off" @endif>
        @foreach($items as $i => $project)
            <div class="sponsored-carousel__slide" role="group" aria-roledescription="slide"
                 aria-label="{{ $i + 1 }} dari {{ $count }}">
                @include('partials.sponsored-feature', [
                    'project'     => $project,
                    'applyStatus' => $applyStatuses[$project->id] ?? null,
                ])
            </div>
        @endforeach
    </div>

    @if($count > 1)
        <div class="sponsored-carousel__controls">
            <button type="button" class="sponsored-carousel__nav" data-dir="-1" aria-label="Proyek promosi sebelumnya">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
            </button>
            <div class="sponsored-carousel__dots" role="tablist" aria-label="Pilih proyek promosi">
                @foreach($items as $i => $project)
                    <button type="button" class="sponsored-carousel__dot {{ $i === 0 ? 'is-active' : '' }}" role="tab"
                            data-index="{{ $i }}" aria-selected="{{ $i === 0 ? 'true' : 'false' }}"
                            aria-label="Proyek promosi {{ $i + 1 }}: {{ $project->title }}"></button>
                @endforeach
            </div>
            <button type="button" class="sponsored-carousel__nav" data-dir="1" aria-label="Proyek promosi berikutnya">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
            </button>
        </div>

        @once
        @push('scripts')
        <script>
        /* ── Carousel Featured Sponsored Project ───────────────────────────────── */
        (function () {
            const AUTOPLAY_MS = 5000;
            const reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            document.querySelectorAll('.sponsored-carousel.is-multi').forEach(root => {
                const viewport = root.querySelector('.sponsored-carousel__viewport');
                const slides = Array.from(viewport.children);
                const dots = Array.from(root.querySelectorAll('.sponsored-carousel__dot'));
                let index = 0;
                let timer = null;
                let paused = false;

                function setActive(i) {
                    index = i;
                    dots.forEach((d, n) => {
                        d.classList.toggle('is-active', n === i);
                        d.setAttribute('aria-selected', n === i ? 'true' : 'false');
                    });
                    slides.forEach((s, n) => s.toggleAttribute('inert', n !== i));
                }

                function goTo(i, smooth = true) {
                    const n = (i + slides.length) % slides.length;
                    viewport.scrollTo({ left: slides[n].offsetLeft - viewport.offsetLeft, behavior: smooth && !reduceMotion ? 'smooth' : 'auto' });
                    setActive(n);
                }

                function stop() { clearInterval(timer); timer = null; }
                function start() {
                    stop();
                    if (reduceMotion || paused || document.hidden) return;
                    timer = setInterval(() => goTo(index + 1), AUTOPLAY_MS);
                }

                // Sinkronkan indikator bila pengguna menggeser manual (swipe / trackpad).
                let scrollTick = null;
                viewport.addEventListener('scroll', () => {
                    clearTimeout(scrollTick);
                    scrollTick = setTimeout(() => {
                        const i = Math.round(viewport.scrollLeft / viewport.clientWidth);
                        if (i !== index && slides[i]) { setActive(i); start(); }
                    }, 80);
                }, { passive: true });

                root.querySelectorAll('.sponsored-carousel__nav').forEach(btn => {
                    btn.addEventListener('click', () => { goTo(index + Number(btn.dataset.dir)); start(); });
                });
                dots.forEach(dot => dot.addEventListener('click', () => { goTo(Number(dot.dataset.index)); start(); }));

                root.addEventListener('keydown', e => {
                    if (e.key === 'ArrowLeft')  { e.preventDefault(); goTo(index - 1); start(); }
                    if (e.key === 'ArrowRight') { e.preventDefault(); goTo(index + 1); start(); }
                });

                // Jeda saat disorot / difokus / tab tidak aktif, agar pengguna sempat membaca & mengklik.
                root.addEventListener('mouseenter', () => { paused = true; stop(); });
                root.addEventListener('mouseleave', () => { paused = false; start(); });
                root.addEventListener('focusin', () => { paused = true; stop(); });
                root.addEventListener('focusout', e => { if (!root.contains(e.relatedTarget)) { paused = false; start(); } });
                document.addEventListener('visibilitychange', () => document.hidden ? stop() : start());
                window.addEventListener('resize', () => goTo(index, false));

                setActive(0);
                start();
            });
        })();
        </script>
        @endpush
        @endonce
    @endif
</section>
@endif
