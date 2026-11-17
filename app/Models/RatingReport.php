<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RatingReport extends Model
{
    public const TYPE_VIOLATION  = 'violation';
    public const TYPE_CORRECTION = 'correction';

    protected $fillable = ['rating_id', 'reporter_id', 'type', 'reason', 'status', 'handled_by', 'handled_at', 'admin_note'];

    protected function casts(): array
    {
        return ['handled_at' => 'datetime'];
    }

    public function rating(): BelongsTo
    {
        return $this->belongsTo(Rating::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->type === self::TYPE_CORRECTION ? 'Permohonan koreksi' : 'Laporan pelanggaran';
    }
}
