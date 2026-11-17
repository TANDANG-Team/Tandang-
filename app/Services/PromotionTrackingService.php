<?php

namespace App\Services;

use App\Models\ProjectApplication;
use App\Models\ProjectPromotion;
use App\Models\PromotionAttribution;
use App\Models\PromotionEvent;
use App\Models\User;
use Illuminate\Contracts\Session\Session;

/**
 * Pencatatan event analitik Sponsored Project.
 * - Identitas pengunjung disimpan sebagai HMAC (tidak bisa dibalik ke user id tanpa APP_KEY).
 * - Impression: maksimal satu per promosi per muat-halaman (page_token).
 * - Klik: dedup per pengunjung dalam jendela singkat.
 * - Atribusi lamaran: hanya bila klik tercatat di server & lamaran dikirim ≤ 24 jam setelahnya.
 */
class PromotionTrackingService
{
    private const SESSION_KEY = 'promo_attribution';

    public function visitorHash(User $user): string
    {
        return hash_hmac('sha256', 'visitor:' . $user->id, (string) config('app.key'));
    }

    public function recordImpression(ProjectPromotion $promotion, User $viewer, string $pageToken, string $placement): bool
    {
        if (! $this->isTrackable($promotion, $viewer)) {
            return false;
        }

        $hash = $this->visitorHash($viewer);

        $exists = PromotionEvent::where('project_promotion_id', $promotion->id)
            ->where('type', PromotionEvent::TYPE_IMPRESSION)
            ->where('visitor_hash', $hash)
            ->where('page_token', $pageToken)
            ->exists();

        if ($exists) {
            return false;
        }

        PromotionEvent::create([
            'project_promotion_id' => $promotion->id,
            'type'                 => PromotionEvent::TYPE_IMPRESSION,
            'visitor_hash'         => $hash,
            'page_token'           => $pageToken,
            'placement'            => $placement,
            'occurred_at'          => now(),
        ]);

        return true;
    }

    public function recordClick(ProjectPromotion $promotion, User $viewer, Session $session, string $placement): bool
    {
        if (! $this->isTrackable($promotion, $viewer)) {
            return false;
        }

        // Klik membuka atribusi, walau terdeduplikasi sebagai event.
        $attribution = $session->get(self::SESSION_KEY, []);
        $attribution[$promotion->project_id] = ['promotion_id' => $promotion->id, 'clicked_at' => now()->timestamp];
        $session->put(self::SESSION_KEY, $attribution);

        $hash = $this->visitorHash($viewer);
        $recent = PromotionEvent::where('project_promotion_id', $promotion->id)
            ->where('type', PromotionEvent::TYPE_CLICK)
            ->where('visitor_hash', $hash)
            ->where('occurred_at', '>=', now()->subSeconds(config('promotion.click_dedup_seconds')))
            ->exists();

        if ($recent) {
            return false;
        }

        PromotionEvent::create([
            'project_promotion_id' => $promotion->id,
            'type'                 => PromotionEvent::TYPE_CLICK,
            'visitor_hash'         => $hash,
            'placement'            => $placement,
            'occurred_at'          => now(),
        ]);

        return true;
    }

    /**
     * Dipanggil setelah lamaran tersimpan. Mengaitkan lamaran ke promosi bila
     * klik promosi untuk proyek ini tercatat di session server dalam batas atribusi.
     */
    public function attributeApplication(ProjectApplication $application, User $talent, Session $session): ?PromotionAttribution
    {
        $entry = $session->get(self::SESSION_KEY, [])[$application->project_id] ?? null;
        if (! $entry) {
            return null;
        }

        $clickedAt = \Carbon\Carbon::createFromTimestamp($entry['clicked_at']);
        if ($clickedAt->lt(now()->subHours(config('promotion.attribution_hours')))) {
            return null;
        }

        $promotion = ProjectPromotion::whereKey($entry['promotion_id'])
            ->where('project_id', $application->project_id)
            ->first();

        // Klik harus benar-benar tercatat di database (bukan sekadar klaim client)
        // dan terjadi saat promosi masih tayang.
        $verified = $promotion && PromotionEvent::where('project_promotion_id', $promotion->id)
            ->where('type', PromotionEvent::TYPE_CLICK)
            ->where('visitor_hash', $this->visitorHash($talent))
            ->where('occurred_at', '>=', now()->subHours(config('promotion.attribution_hours')))
            ->exists();

        if (! $verified || ($promotion->start_at && $clickedAt->lt($promotion->start_at))) {
            return null;
        }

        return PromotionAttribution::firstOrCreate(
            ['project_application_id' => $application->id],
            ['project_promotion_id' => $promotion->id, 'clicked_at' => $clickedAt]
        );
    }

    /** Hanya promosi yang benar-benar tayang, dilihat Talenta, dan bukan oleh pemilik proyek. */
    private function isTrackable(ProjectPromotion $promotion, User $viewer): bool
    {
        return $viewer->isTalenta()
            && (int) $viewer->id !== (int) $promotion->user_id
            && ProjectPromotion::forTalent()->whereKey($promotion->id)->exists();
    }
}
