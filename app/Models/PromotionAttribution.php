<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Menghubungkan lamaran aktual dengan promosi yang menjadi asal kunjungannya. */
class PromotionAttribution extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['project_promotion_id', 'project_application_id', 'clicked_at'];

    protected function casts(): array
    {
        return ['clicked_at' => 'datetime', 'created_at' => 'datetime'];
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(ProjectPromotion::class, 'project_promotion_id');
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(ProjectApplication::class, 'project_application_id');
    }
}
