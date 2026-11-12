<?php

namespace Tests\Feature;

use App\Models\OrganizationProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PenyediaProyekTest extends TestCase
{
    use RefreshDatabase;

    public function test_penyedia_proyek_can_register_with_valid_data(): void
    {
        $payload = [
            'name' => 'Budi Santoso',
            'email' => 'budi.penyedia@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'penyedia_proyek',
        ];

        $response = $this->postJson('/api/register', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', [
            'name' => 'Budi Santoso',
            'email' => 'budi.penyedia@example.com',
            'role' => 'penyedia_proyek',
        ]);

        $user = User::where('email', 'budi.penyedia@example.com')->first();
        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertTrue($user->isPenyedia());
        $this->assertNotNull($user->organizationProfile);
        $this->assertEquals('Budi Santoso', $user->organizationProfile->organization_name);
    }

    public function test_registration_rejects_invalid_role(): void
    {
        $payload = [
            'name' => 'Hacker User',
            'email' => 'hacker@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'superadmin',
        ];

        $response = $this->postJson('/api/register', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['role']);
        $this->assertDatabaseMissing('users', ['email' => 'hacker@example.com']);
    }

    public function test_web_login_redirects_penyedia_proyek_to_organization_profile(): void
    {
        $penyedia = User::create([
            'name' => 'Penyedia UMKM',
            'email' => 'umkm@example.com',
            'password' => 'secret123',
            'role' => 'penyedia_proyek',
        ]);

        $response = $this->postJson('/web-login', [
            'email' => 'umkm@example.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Login berhasil.',
            'redirect' => route('penyedia.dashboard'),
        ]);
        $this->assertAuthenticatedAs($penyedia);
    }

    public function test_web_login_redirects_talenta_muda_to_dashboard(): void
    {
        $talenta = User::create([
            'name' => 'Siswa SMK',
            'email' => 'talenta@example.com',
            'password' => 'secret123',
            'role' => 'talenta_muda',
        ]);

        $response = $this->postJson('/web-login', [
            'email' => 'talenta@example.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Login berhasil.',
            'redirect' => route('dashboard'),
        ]);
        $this->assertAuthenticatedAs($talenta);
    }

    public function test_guest_cannot_access_penyedia_profile(): void
    {
        $response = $this->get('/penyedia/profil');
        $response->assertRedirect('/login');
    }

    public function test_talenta_muda_cannot_access_penyedia_profile(): void
    {
        $talenta = User::create([
            'name' => 'Talenta User',
            'email' => 'talenta2@example.com',
            'password' => 'secret123',
            'role' => 'talenta_muda',
        ]);

        $response = $this->actingAs($talenta)->get('/penyedia/profil');
        $response->assertStatus(403);
    }

    public function test_penyedia_proyek_can_access_and_view_profile_page(): void
    {
        $penyedia = User::create([
            'name' => 'Pemilik Usaha',
            'email' => 'pemilik@example.com',
            'password' => 'secret123',
            'role' => 'penyedia_proyek',
        ]);

        $response = $this->actingAs($penyedia)->get('/penyedia/profil');

        $response->assertStatus(200);
        $response->assertSee('Kelola Profil Organisasi');
        $response->assertSee('Pemilik Usaha');
    }

    public function test_penyedia_proyek_can_update_organization_profile(): void
    {
        $penyedia = User::create([
            'name' => 'CV Kreatif',
            'email' => 'kreatif@example.com',
            'password' => 'secret123',
            'role' => 'penyedia_proyek',
        ]);

        $updateData = [
            'organization_name' => 'CV Tandang Maju Jaya',
            'industry_type' => 'Teknologi & Informasi',
            'description' => 'Agensi digital terpercaya yang fokus pada pengembangan UMKM lokal.',
            'city' => 'Bandung',
            'address' => 'Jl. Braga No. 10',
            'phone' => '08123456789',
            'website' => 'https://tandangmaju.com',
        ];

        $response = $this->actingAs($penyedia)->post('/penyedia/profil', $updateData);

        $response->assertRedirect(route('penyedia.profile'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('organization_profiles', [
            'user_id' => $penyedia->id,
            'organization_name' => 'CV Tandang Maju Jaya',
            'industry_type' => 'Teknologi & Informasi',
            'city' => 'Bandung',
            'phone' => '08123456789',
            'website' => 'https://tandangmaju.com',
        ]);
    }

    public function test_penyedia_proyek_can_upload_logo(): void
    {
        $penyedia = User::create([
            'name' => 'Toko Kopi',
            'email' => 'kopi@example.com',
            'password' => 'secret123',
            'role' => 'penyedia_proyek',
        ]);

        $pngContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        $file = UploadedFile::fake()->createWithContent('logo.png', $pngContent);

        $response = $this->actingAs($penyedia)->post('/penyedia/profil', [
            'organization_name' => 'Kopi Tandang',
            'logo' => $file,
        ]);

        $response->assertRedirect(route('penyedia.profile'));
        $profile = OrganizationProfile::where('user_id', $penyedia->id)->first();
        $this->assertNotNull($profile->logo);
        $this->assertFileExists(public_path($profile->logo));

        // Clean up created fake file
        if (File::exists(public_path($profile->logo))) {
            File::delete(public_path($profile->logo));
        }
    }

    public function test_organization_name_is_required_when_updating_profile(): void
    {
        $penyedia = User::create([
            'name' => 'Usaha Jaya',
            'email' => 'jaya@example.com',
            'password' => 'secret123',
            'role' => 'penyedia_proyek',
        ]);

        $response = $this->actingAs($penyedia)->post('/penyedia/profil', [
            'organization_name' => '',
        ]);

        $response->assertSessionHasErrors(['organization_name']);
    }

    public function test_invalid_website_format_is_rejected(): void
    {
        $penyedia = User::create([
            'name' => 'Usaha Jaya 2',
            'email' => 'jaya2@example.com',
            'password' => 'secret123',
            'role' => 'penyedia_proyek',
        ]);

        $response = $this->actingAs($penyedia)->post('/penyedia/profil', [
            'organization_name' => 'Usaha Jaya 2',
            'website' => 'not-a-valid-url',
        ]);

        $response->assertSessionHasErrors(['website']);
    }

    public function test_penyedia_proyek_cannot_access_or_modify_other_penyedia_profile(): void
    {
        $penyediaA = User::create([
            'name' => 'Penyedia A',
            'email' => 'a@example.com',
            'password' => 'secret123',
            'role' => 'penyedia_proyek',
        ]);
        $profileA = OrganizationProfile::create([
            'user_id' => $penyediaA->id,
            'organization_name' => 'Perusahaan A',
        ]);

        $penyediaB = User::create([
            'name' => 'Penyedia B',
            'email' => 'b@example.com',
            'password' => 'secret123',
            'role' => 'penyedia_proyek',
        ]);
        $profileB = OrganizationProfile::create([
            'user_id' => $penyediaB->id,
            'organization_name' => 'Perusahaan B',
        ]);

        // When Penyedia B views profile, it only shows Perusahaan B, never Perusahaan A
        $response = $this->actingAs($penyediaB)->get('/penyedia/profil');
        $response->assertStatus(200);
        $response->assertSee('Perusahaan B');
        $response->assertDontSee('Perusahaan A');

        // When Penyedia B updates profile, Perusahaan A remains completely unchanged
        $this->actingAs($penyediaB)->post('/penyedia/profil', [
            'organization_name' => 'Perusahaan B Baru',
        ]);

        $this->assertDatabaseHas('organization_profiles', [
            'id' => $profileA->id,
            'organization_name' => 'Perusahaan A',
        ]);
        $this->assertDatabaseHas('organization_profiles', [
            'id' => $profileB->id,
            'organization_name' => 'Perusahaan B Baru',
        ]);
    }

    public function test_penyedia_navbar_displays_circular_avatar_role_badge_and_dropdown_menu(): void
    {
        $penyedia = User::create([
            'name' => 'Ahmad Subagyo Panjang Sekali Namanya',
            'email' => 'ahmad@example.com',
            'password' => 'secret123',
            'role' => 'penyedia_proyek',
        ]);
        OrganizationProfile::create([
            'user_id' => $penyedia->id,
            'organization_name' => 'PT Makmur Sentosa Jaya Abadi',
        ]);

        $response = $this->actingAs($penyedia)->get('/penyedia/profil');

        $response->assertStatus(200);
        $response->assertSee('user-avatar-circle', false);
        $response->assertSee('user-profile-role-badge', false);
        $response->assertSee('Penyedia Proyek');
        $response->assertSee('PT Makmur Sentosa Jaya Abadi');
        $response->assertSee('user-dropdown-menu', false);
        $response->assertSee(route('penyedia.profile'), false);
        $response->assertSee(route('logout'), false);
    }

    public function test_talenta_navbar_displays_avatar_role_badge_and_dropdown_menu(): void
    {
        $talenta = User::create([
            'name' => 'Rian Pratama',
            'email' => 'rian@example.com',
            'password' => 'secret123',
            'role' => 'talenta_muda',
        ]);

        $response = $this->actingAs($talenta)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('user-avatar-circle', false);
        $response->assertSee('user-profile-role-badge', false);
        $response->assertSee('Talenta Muda');
        $response->assertSee('Rian Pratama');
        $response->assertSee('user-dropdown-menu', false);
        $response->assertSee(route('dashboard'), false);
        $response->assertSee(route('logout'), false);
    }

    public function test_dropdown_logout_action_successfully_logs_out_user(): void
    {
        $user = User::create([
            'name' => 'User Logout Test',
            'email' => 'logout@example.com',
            'password' => 'secret123',
            'role' => 'penyedia_proyek',
        ]);

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect('/login');
        $this->assertGuest();
    }
}
