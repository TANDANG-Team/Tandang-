<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RatingAuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['rating_id', 'project_work_completion_id', 'user_id', 'action', 'details', 'created_at'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rating(): BelongsTo
    {
        return $this->belongsTo(Rating::class);
    }

    public function completion(): BelongsTo
    {
        return $this->belongsTo(ProjectWorkCompletion::class, 'project_work_completion_id');
    }

    public static function record(string $action, ?string $details = null, ?User $user = null, ?Rating $rating = null, ?ProjectWorkCompletion $completion = null): self
    {
        return self::create([
            'rating_id'                  => $rating?->id,
            'project_work_completion_id' => $completion?->id ?? $rating?->project_work_completion_id,
            'user_id'                    => $user?->id,
            'action'                     => $action,
            'details'                    => $details,
            'created_at'                 => now(),
        ]);
    }

    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'completion_requested' => 'Pengajuan selesai',
            'completion_confirmed' => 'Konfirmasi selesai',
            'completion_auto'      => 'Selesai otomatis',
            'completion_disputed'  => 'Sengketa diajukan',
            'dispute_completed'    => 'Sengketa: dinyatakan selesai',
            'dispute_reopened'     => 'Sengketa: dilanjutkan',
            'rating_submitted'     => 'Rating dikirim',
            'rating_published'     => 'Rating dipublikasikan',
            'review_hidden'        => 'Ulasan disembunyikan',
            'review_restored'      => 'Ulasan dipulihkan',
            'rating_voided'        => 'Rating dibatalkan (pelanggaran)',
            'rating_unvoided'      => 'Pembatalan rating dicabut',
            'report_created'       => 'Laporan dibuat',
            'report_resolved'      => 'Laporan ditindaklanjuti',
            'report_dismissed'     => 'Laporan ditolak',
            default                => ucfirst(str_replace('_', ' ', $this->action)),
        };
    }
}
