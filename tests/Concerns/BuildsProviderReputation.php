<?php

namespace Tests\Concerns;

use App\Models\Project;
use App\Models\ProjectApplication;
use App\Models\ProjectWorkCompletion;
use App\Models\Rating;
use App\Models\User;

/**
 * Membuat riwayat kerja Selesai + rating Talenta → Penyedia yang terpublikasi
 * (data uji yang setara dengan alur nyata: lamaran diterima → pekerjaan selesai → rating).
 */
trait BuildsProviderReputation
{
    /**
     * @param list<float> $finalScores nilai akhir tiap penilaian dari Talenta
     * @return list<Rating>
     */
    protected function giveProviderRatings(User $provider, array $finalScores): array
    {
        $ratings = [];
        foreach ($finalScores as $score) {
            $talent  = User::factory()->create(['role' => 'talenta_muda']);
            $project = Project::factory()->closed()->create(['user_id' => $provider->id, 'title' => 'Riwayat ' . uniqid()]);

            $application = ProjectApplication::create([
                'project_id' => $project->id, 'user_id' => $talent->id,
                'status' => ProjectApplication::STATUS_DITERIMA, 'cover_letter' => 'uji', 'reviewed_at' => now(),
            ]);
            $completion = ProjectWorkCompletion::where('project_application_id', $application->id)->firstOrFail();
            $completion->forceFill([
                'status'             => ProjectWorkCompletion::STATUS_COMPLETED,
                'completed_at'       => now()->subDays(10),
                'rating_deadline_at' => now()->subDays(3),
            ])->save();

            $aspect = (int) max(1, min(5, round($score)));
            $ratings[] = Rating::create([
                'project_work_completion_id' => $completion->id,
                'project_id'  => $project->id,
                'rater_id'    => $talent->id,
                'ratee_id'    => $provider->id,
                'rater_role'  => Rating::ROLE_TALENTA,
                'ratee_role'  => Rating::ROLE_PENYEDIA,
                'scores'      => array_fill_keys(array_keys(config('rating.provider_aspects')), $aspect),
                'final_score' => $score,
                'published_at' => now()->subDays(3),
            ]);
        }

        return $ratings;
    }
}
