@php
    $paymentQueue = \App\Models\PromotionTransaction::where('is_simulation', false)->awaitingReview()->count();
    $livePromoAdmin = \App\Models\ProjectPromotion::live()->count();
    $ratingQueue = \App\Models\RatingReport::where('status', 'open')->count()
        + \App\Models\ProjectWorkCompletion::where('status', 'disputed')->count();
@endphp
<a href="{{ route('admin.payments.index') }}" class="{{ request()->routeIs('admin.payments.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="5" width="20" height="14" rx="2"/>
                        <path d="M2 10h20M6 15h4"/>
                    </svg>
                    Verifikasi Pembayaran
                    @if($paymentQueue > 0)
                        <span title="Pembayaran menunggu verifikasi" style="margin-left:auto; background:#ffb547; color:#5c3a00; font-size:11px; font-weight:800; border-radius:99px; padding:1px 8px;">{{ $paymentQueue }}</span>
                    @endif
                </a>

                <a href="{{ route('admin.promotions.index') }}" class="{{ request()->routeIs('admin.promotions.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 11l18-5v12L3 14v-3z"/>
                        <path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/>
                    </svg>
                    Manajemen Promosi
                    @if($livePromoAdmin > 0)
                        <span title="Promosi sedang tayang" style="margin-left:auto; background:#e7f7f4; color:#0c7566; font-size:11px; font-weight:800; border-radius:99px; padding:1px 8px;">{{ $livePromoAdmin }}</span>
                    @endif
                </a>

                <a href="{{ route('admin.ratings.index') }}" class="{{ request()->routeIs('admin.ratings.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2.5l2.94 5.96 6.58.96-4.76 4.64 1.12 6.55L12 17.52l-5.88 3.09 1.12-6.55L2.48 9.42l6.58-.96z"/>
                    </svg>
                    Moderasi Rating
                    @if($ratingQueue > 0)
                        <span title="Laporan & sengketa terbuka" style="margin-left:auto; background:#ffb547; color:#5c3a00; font-size:11px; font-weight:800; border-radius:99px; padding:1px 8px;">{{ $ratingQueue }}</span>
                    @endif
                </a>
