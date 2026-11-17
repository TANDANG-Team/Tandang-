<?php

namespace App\Services;

use App\Models\ProjectPromotion;
use App\Models\PromotionAttribution;
use App\Models\PromotionEvent;

/**
 * Agregasi statistik dari event yang benar-benar tercatat (tanpa angka contoh).
 * Hari ke-N = jendela 24 jam ke-N sejak promosi diaktifkan.
 */
class PromotionStatsService
{
    /**
     * @return array{impressions:int, clicks:int, ctr:float, applications:int, days: array<int, array>}
     */
    public function forPromotion(ProjectPromotion $promotion): array
    {
        $totals = PromotionEvent::where('project_promotion_id', $promotion->id)
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $impressions  = (int) ($totals[PromotionEvent::TYPE_IMPRESSION] ?? 0);
        $clicks       = (int) ($totals[PromotionEvent::TYPE_CLICK] ?? 0);
        $applications = PromotionAttribution::where('project_promotion_id', $promotion->id)->count();

        return [
            'impressions'  => $impressions,
            'clicks'       => $clicks,
            'ctr'          => ProjectPromotion::ctr($clicks, $impressions),
            'applications' => $applications,
            'days'         => $this->daily($promotion),
        ];
    }

    /** @return array<int, array{label:string, start:\Carbon\Carbon, state:string, impressions:int, clicks:int, applications:int}> */
    public function daily(ProjectPromotion $promotion): array
    {
        $dayCount = max(1, (int) ceil(($promotion->duration_hours ?: 96) / 24));
        $days = [];

        if (! $promotion->start_at) {
            for ($i = 1; $i <= $dayCount; $i++) {
                $days[$i] = ['label' => "Hari {$i}", 'start' => null, 'state' => 'upcoming', 'impressions' => 0, 'clicks' => 0, 'applications' => 0];
            }
            return $days;
        }

        // Penayangan berhenti lebih awal bila promosi dihentikan.
        $cutoff = $promotion->stopped_at ?? $promotion->end_at;

        $events = PromotionEvent::where('project_promotion_id', $promotion->id)
            ->get(['type', 'occurred_at']);
        $attributions = PromotionAttribution::where('project_promotion_id', $promotion->id)->get(['created_at']);

        for ($i = 1; $i <= $dayCount; $i++) {
            $start = $promotion->start_at->copy()->addHours(24 * ($i - 1));
            $end   = $start->copy()->addHours(24);

            $state = match (true) {
                $start->gte($cutoff)      => $promotion->stopped_at ? 'stopped' : 'upcoming',
                $start->isFuture()        => 'upcoming',
                $end->isFuture()          => 'running',
                default                   => 'done',
            };

            $inDay = fn ($t) => $t && $t->gte($start) && $t->lt($end);

            $days[$i] = [
                'label'        => "Hari {$i}",
                'start'        => $start,
                'state'        => $state,
                'impressions'  => $events->where('type', PromotionEvent::TYPE_IMPRESSION)->filter(fn ($e) => $inDay($e->occurred_at))->count(),
                'clicks'       => $events->where('type', PromotionEvent::TYPE_CLICK)->filter(fn ($e) => $inDay($e->occurred_at))->count(),
                'applications' => $attributions->filter(fn ($a) => $inDay($a->created_at))->count(),
            ];
        }

        return $days;
    }
}
