{{-- Pill slot pelamar + badge alasan tidak bisa melamar, untuk kartu proyek. Butuh $applyStatus. --}}
@if(!empty($applyStatus))
    <span class="meta-pill" title="Pelamar aktif (menunggu + review + diterima) dari batas 2× talenta dibutuhkan. Pelamar yang ditolak tidak dihitung.">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        {{ $applyStatus['active_count'] }}/{{ $applyStatus['max_applicants'] }} pelamar
    </span>
    @unless($applyStatus['can_apply'])
        @php
            $reasonBadge = match ($applyStatus['reason']) {
                'rejected' => 'badge-danger',
                'already_applied' => 'badge-info',
                'cancelled' => 'badge-purple',
                default => 'badge-warning',
            };
        @endphp
        <span class="badge {{ $reasonBadge }}" title="{{ $applyStatus['message'] }}">{{ $applyStatus['reason_label'] }}</span>
    @endunless
@endif
