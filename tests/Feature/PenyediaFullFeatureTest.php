<?php

namespace Tests\Feature;

use App\Models\OrganizationProfile;
use App\Models\Project;
use App\Models\ProjectApplication;
use App\Models\ProviderNotification;
use App\Models\User;
use Database\Seeders\ProviderDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PenyediaFullFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_provider_login_redirects_to_provider_dashboard(): void
    {
        $this->seed(ProviderDemoSeeder::class);

        $response = $this->postJson('/web-login', [
            'email' => 'penyedia@tandang.id',
            'password' => 'demo12345',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Login berhasil.',
            'redirect' => route('penyedia.dashboard'),
        ]);

        $this->assertAuthenticated();
        $user = auth()->user();
        $this->assertTrue($user->isPenyedia());
        $this->assertFalse($user->isTalenta());

        // Access provider dashboard
        $dashboardResponse = $this->get('/penyedia/dashboard');
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertSee('RUANG PENYEDIA');
        $dashboardResponse->assertSee('Selamat datang, Dapur Nusa!');
        $dashboardResponse->assertSee('Fotografer Acara Kuliner');
    }

    public function test_role_isolation_penyedia_and_talenta(): void
    {
        $provider = User::create([
            'name' => 'Pak Budi',
            'email' => 'budi@example.com',
            'password' => Hash::make('password123'),
            'role' => 'penyedia_proyek',
        ]);

        $talent = User::create([
            'name' => 'Siti Talenta',
            'email' => 'siti@example.com',
            'password' => Hash::make('password123'),
            'role' => 'talenta_muda',
        ]);

        // Provider visiting /dashboard is safely redirected to provider dashboard
        $response = $this->actingAs($provider)->get('/dashboard');
        $response->assertRedirect(route('penyedia.dashboard'));

        // Talent visiting provider routes receives 403 Forbidden
        $response = $this->actingAs($talent)->get('/penyedia/dashboard');
        $response->assertStatus(403);

        $response = $this->actingAs($talent)->get('/penyedia/proyek');
        $response->assertStatus(403);

        $response = $this->actingAs($talent)->get('/penyedia/pelamar');
        $response->assertStatus(403);
    }

    public function test_project_crud_and_status_transitions(): void
    {
        $provider = User::create([
            'name' => 'Owner Kedai',
            'email' => 'kedai@example.com',
            'password' => Hash::make('password123'),
            'role' => 'penyedia_proyek',
        ]);

        // Create project as published
        $response = $this->actingAs($provider)->post('/penyedia/proyek', [
            'title' => 'Barista Kedai Kopi',
            'category' => 'F&B',
            'location' => 'Bandung',
            'work_method' => 'onsite',
            'compensation' => 'Rp 50.000 / shift',
            'availability' => 'Shift Sore',
            'talents_needed' => 2,
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->addDays(20)->toDateString(),
            'description' => 'Mencari barista muda untuk membantu operasional kedai kopi di akhir pekan.',
            'action' => 'published',
        ]);

        $response->assertRedirect(route('penyedia.projects.index'));
        $this->assertDatabaseHas('projects', [
            'user_id' => $provider->id,
            'title' => 'Barista Kedai Kopi',
            'status' => 'published',
        ]);

        $project = Project::where('title', 'Barista Kedai Kopi')->first();

        // Update project status to closed
        $response = $this->actingAs($provider)->patch("/penyedia/proyek/{$project->id}/status", [
            'status' => 'closed',
        ]);
        $response->assertSessionHas('success');
        $this->assertEquals('closed', $project->fresh()->status);

        // Another provider cannot edit this project
        $otherProvider = User::create([
            'name' => 'Other Provider',
            'email' => 'other@example.com',
            'password' => Hash::make('password123'),
            'role' => 'penyedia_proyek',
        ]);

        $response = $this->actingAs($otherProvider)->get("/penyedia/proyek/{$project->id}/edit");
        $response->assertStatus(403);
    }

    public function test_applicant_acceptance_and_quota_enforcement(): void
    {
        $provider = User::create([
            'name' => 'Studio Foto',
            'email' => 'studio@example.com',
            'password' => Hash::make('password123'),
            'role' => 'penyedia_proyek',
        ]);

        $project = Project::create([
            'user_id' => $provider->id,
            'title' => 'Fotografer Studio',
            'category' => 'Kreatif',
            'location' => 'Surabaya',
            'work_method' => 'onsite',
            'availability' => 'Full-time',
            'talents_needed' => 1, // Quota only 1
            'start_date' => now()->addDays(1)->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
            'description' => 'Fotografer katalog.',
            'status' => 'published',
        ]);

        $talent1 = User::create([
            'name' => 'Talenta 1',
            'email' => 't1@example.com',
            'password' => Hash::make('password123'),
            'role' => 'talenta_muda',
        ]);

        $talent2 = User::create([
            'name' => 'Talenta 2',
            'email' => 't2@example.com',
            'password' => Hash::make('password123'),
            'role' => 'talenta_muda',
        ]);

        $app1 = ProjectApplication::create([
            'project_id' => $project->id,
            'user_id' => $talent1->id,
            'status' => 'menunggu',
        ]);

        $app2 = ProjectApplication::create([
            'project_id' => $project->id,
            'user_id' => $talent2->id,
            'status' => 'menunggu',
        ]);

        // Accept Talent 1 -> Succeeds
        $response = $this->actingAs($provider)->patch("/penyedia/pelamar/{$app1->id}/status", [
            'status' => 'diterima',
        ]);
        $response->assertSessionHas('success');
        $this->assertEquals('diterima', $app1->fresh()->status);

        // Accept Talent 2 -> Fails due to quota (quota is 1 and already 1 accepted)
        $response = $this->actingAs($provider)->patch("/penyedia/pelamar/{$app2->id}/status", [
            'status' => 'diterima',
        ]);
        $response->assertSessionHas('error');
        $this->assertNotEquals('diterima', $app2->fresh()->status);
    }

    public function test_account_password_change_security(): void
    {
        $provider = User::create([
            'name' => 'Security Test',
            'email' => 'sec@example.com',
            'password' => Hash::make('oldpassword123'),
            'role' => 'penyedia_proyek',
        ]);

        // Wrong old password fails
        $response = $this->actingAs($provider)->post('/penyedia/pengaturan/password', [
            'current_password' => 'wrongpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);
        $response->assertSessionHasErrors(['current_password']);

        // Correct old password succeeds
        $response = $this->actingAs($provider)->post('/penyedia/pengaturan/password', [
            'current_password' => 'oldpassword123',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);
        $response->assertSessionHas('success');
        $this->assertTrue(Hash::check('newpassword123', $provider->fresh()->password));
    }
}
