<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\TalentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingAndTalentProjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_renders_projects_dynamically(): void
    {
        $provider = User::factory()->create(['role' => 'penyedia_proyek']);
        $project = Project::create([
            'user_id' => $provider->id,
            'provider_name' => 'Dapur Nusa',
            'title' => 'Fotografer Acara Kuliner',
            'category' => 'Kreatif',
            'description' => 'Dokumentasikan festival kuliner.',
            'responsibilities' => 'Ambil foto.',
            'skills' => ['Fotografi', 'Adobe Lightroom'],
            'location' => 'Bandung',
            'work_type' => 'Onsite',
            'schedule' => 'Akhir pekan',
            'compensation' => 'Rp 750.000',
            'quota' => 2,
            'status' => 'Dipublikasikan',
            'workstyle_confirmed' => true,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Dapur Nusa');
        $response->assertSee('Fotografer Acara Kuliner');
        $response->assertSee('Bandung (Onsite)');
        $response->assertSee('Fotografi');
    }

    public function test_talent_beranda_renders_same_project(): void
    {
        $talentUser = User::factory()->create(['role' => 'talenta_muda']);
        $talentProfile = TalentProfile::create([
            'user_id' => $talentUser->id,
            'category' => 'Mahasiswa Aktif',
            'institution' => 'ITB',
            'major' => 'Informatika',
            'city' => 'Bandung',
            'verification_status' => 'verified',
            'eligibility_status' => 'eligible',
            'skills' => ['Fotografi'],
            'assessment_answers' => [1 => 'A', 2 => 'B'],
            'workstyle_scores' => ['collaboration' => 4],
        ]);

        $provider = User::factory()->create(['role' => 'penyedia_proyek']);
        $project = Project::create([
            'user_id' => $provider->id,
            'provider_name' => 'Dapur Nusa',
            'title' => 'Fotografer Acara Kuliner',
            'category' => 'Kreatif',
            'description' => 'Dokumentasikan festival kuliner.',
            'responsibilities' => 'Ambil foto.',
            'skills' => ['Fotografi', 'Adobe Lightroom'],
            'location' => 'Bandung',
            'work_type' => 'Onsite',
            'schedule' => 'Akhir pekan',
            'compensation' => 'Rp 750.000',
            'quota' => 2,
            'status' => 'Dipublikasikan',
            'workstyle_requirements' => ['collaboration' => 4],
            'workstyle_confirmed' => true,
        ]);

        $response = $this->actingAs($talentUser)->get(route('talent.beranda'));

        $response->assertStatus(200);
        $response->assertSee('Dapur Nusa');
        $response->assertSee('Fotografer Acara Kuliner');
        $response->assertSee('Bandung (Onsite)');
        $response->assertSee('Rp 750.000');
    }
}

