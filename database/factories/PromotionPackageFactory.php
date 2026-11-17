<?php

namespace Database\Factories;

use App\Models\PromotionPackage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PromotionPackage>
 */
class PromotionPackageFactory extends Factory
{
    protected $model = PromotionPackage::class;

    public function definition(): array
    {
        return [
            'name'           => 'Sponsored Project',
            'slug'           => PromotionPackage::DEFAULT_SLUG,
            'price'          => 15000,
            'duration_hours' => 96,
            'description'    => 'Paket uji.',
            'features'       => ['Penempatan prioritas'],
            'is_active'      => true,
        ];
    }
}
