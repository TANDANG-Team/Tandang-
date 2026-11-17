<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PromotionPackage extends Model
{
    use HasFactory;

    public const DEFAULT_SLUG = 'sponsored-project';

    protected $fillable = ['name', 'slug', 'price', 'duration_hours', 'description', 'features', 'is_active'];

    protected function casts(): array
    {
        return [
            'price'          => 'integer',
            'duration_hours' => 'integer',
            'features'       => 'array',
            'is_active'      => 'boolean',
        ];
    }

    public function projectPromotions(): HasMany
    {
        return $this->hasMany(ProjectPromotion::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** Paket MVP satu-satunya: Sponsored Project. */
    public static function current(): ?self
    {
        return static::active()->where('slug', self::DEFAULT_SLUG)->first()
            ?? static::active()->orderBy('price')->first();
    }

    public function getFormattedPriceAttribute(): string
    {
        return 'Rp' . number_format($this->price, 0, ',', '.');
    }

    public function getDurationLabelAttribute(): string
    {
        return $this->duration_hours % 24 === 0
            ? ($this->duration_hours / 24) . ' hari'
            : $this->duration_hours . ' jam';
    }
}
