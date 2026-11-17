<?php

namespace App\Http\Controllers;

use App\Models\ProjectPromotion;
use App\Services\PromotionTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Endpoint event analitik dari halaman Cari Proyek (dipanggil via fetch/sendBeacon). */
class PromotionTrackingController extends Controller
{
    public function __construct(private PromotionTrackingService $tracking) {}

    public function impression(Request $request, ProjectPromotion $promotion): JsonResponse
    {
        $data = $request->validate([
            'page_token' => ['required', 'string', 'regex:/^[A-Za-z0-9]{16,32}$/'],
            'placement'  => ['nullable', 'in:banner,list'],
        ]);

        $recorded = $this->tracking->recordImpression($promotion, $request->user(), $data['page_token'], $data['placement'] ?? 'list');

        return response()->json(['recorded' => $recorded]);
    }

    public function click(Request $request, ProjectPromotion $promotion): JsonResponse
    {
        $data = $request->validate(['placement' => ['nullable', 'in:banner,list']]);

        $recorded = $this->tracking->recordClick($promotion, $request->user(), $request->session(), $data['placement'] ?? 'list');

        return response()->json(['recorded' => $recorded]);
    }
}
