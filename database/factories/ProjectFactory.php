<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'user_id'          => User::factory()->state(['role' => 'penyedia_proyek']),
            'provider_name'    => fake()->company(),
            'title'            => 'Proyek ' . fake()->unique()->words(3, true),
            'category'         => 'Kreatif',
            'description'      => fake()->sentence(12),
            'responsibilities' => fake()->sentence(8),
            'location'         => 'Bandung',
            'work_type'        => 'Onsite',
            'work_method'      => 'onsite',
            'compensation'     => 'Rp500.000 / proyek',
            'skills'           => ['Desain', 'Komunikasi'],
            'quota'            => 2,
            'talents_needed'   => 2,
            'status'           => Project::STATUS_PUBLISHED,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => Project::STATUS_DRAFT]);
    }

    public function closed(): static
    {
        return $this->state(fn () => ['status' => Project::STATUS_CLOSED]);
    }
}
