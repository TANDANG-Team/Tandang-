<?php

namespace Tests\Concerns;

use App\Models\Project;
use App\Models\ProjectApplication;
use App\Models\TalentProfile;
use App\Models\User;

/**
 * Data uji bersama untuk test kuota "Apply Proyek".
 */
trait CreatesApplyFixtures
{
    protected function makeProvider(): User
    {
        return User::factory()->create(['role' => 'penyedia_proyek']);
    }

    protected function makeProject(User $provider, int $talentsNeeded, array $overrides = []): Project
    {
        return Project::create(array_merge([
            'user_id' => $provider->id,
            'provider_name' => 'Mitra Uji',
            'title' => 'Proyek Uji Kuota',
            'category' => 'Kreatif',
            'location' => 'Bandung',
            'work_method' => 'onsite',
            'availability' => 'Akhir pekan',
            'talents_needed' => $talentsNeeded,
            'quota' => $talentsNeeded,
            'start_date' => now()->addDays(7)->toDateString(),
            'end_date' => now()->addDays(14)->toDateString(),
            'description' => 'Proyek untuk menguji kuota pelamar.',
            'status' => Project::STATUS_PUBLISHED,
        ], $overrides));
    }

    /** Talenta terverifikasi yang boleh melamar. */
    protected function makeTalent(): User
    {
        $user = User::factory()->create(['role' => 'talenta_muda', 'is_active' => true]);

        TalentProfile::create([
            'user_id' => $user->id,
            'category' => 'Mahasiswa Aktif',
            'institution' => 'Universitas Uji',
            'major' => 'Informatika',
            'city' => 'Bandung',
            'verification_status' => 'verified',
            'eligibility_status' => 'eligible',
        ]);

        return $user;
    }

    /** @return User[] */
    protected function makeTalents(int $count): array
    {
        return array_map(fn () => $this->makeTalent(), range(1, $count));
    }

    protected function applicationOf(Project $project, User $talent): ?ProjectApplication
    {
        return ProjectApplication::where('project_id', $project->id)->where('user_id', $talent->id)->first();
    }
}
