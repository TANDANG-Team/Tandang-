{{-- Tracking Sponsored Project (impression & klik). Butuh $pageToken. Dipakai di beranda, rekomendasi, cari. --}}
@push('scripts')
<script>
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
