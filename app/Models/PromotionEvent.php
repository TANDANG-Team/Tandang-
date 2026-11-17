<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromotionEvent extends Model
{
    public const TYPE_IMPRESSION = 'impression';
    public const TYPE_CLICK      = 'click';

    public $timestamps = false;

    protected $fillable = ['project_promotion_id', 'type', 'visitor_hash', 'page_token', 'placement', 'occurred_at'];

    protected function casts(): array
    {
        return ['occurred_at' => 'datetime'];
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(ProjectPromotion::class, 'project_promotion_id');
    }
}
