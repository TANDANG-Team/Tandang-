<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\TalentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TalentVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    protected function createTalentUser(): User
    {
        $user = User::factory()->create([
            'role' => 'talenta_muda',
            'is_active' => true,
        ]);

        TalentProfile::create([
            'user_id' => $user->id,
            'category' => 'Mahasiswa Aktif',
            'institution' => '-',
            'major' => '-',
            'city' => 'Bandung',
            'verification_status' => 'not_submitted',
            'eligibility_status' => 'undetermined',
        ]);

        return $user;
    }

    protected function createAdminUser(): User
    {
        return User::factory()->create([
            'email' => 'admin_test_' . uniqid() . '@tandang.id',
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    /**
     * Kriteria 1: Siswa SMK mengisi data lengkap dan upload PDF valid -> status eligible dan pending
     */
    public function test_siswa_smk_can_submit_valid_verification_and_becomes_pending_eligible()
    {
        $user = $this->createTalentUser();
        $file = UploadedFile::fake()->create('kartu_pelajar.pdf', 500, 'application/pdf');

        $response = $this->actingAs($user)->post('/talent/verifikasi', [
            'category' => 'Siswa SMK/SMA',
            'institution' => 'SMKN 1 Cimahi',
            'major' => 'Rekayasa Perangkat Lunak',
            'city' => 'Cimahi',
            'class_level' => 'XII',
            'academic_year' => '2025/2026',
            'graduation_year' => now()->year,
            'document' => $file,
        ]);

        $response->assertSessionHas('success');

        $profile = $user->fresh()->talentProfile;
        $this->assertEquals('eligible', $profile->eligibility_status);
        $this->assertEquals('pending', $profile->verification_status);
        $this->assertNotNull($profile->document_path);
        Storage::disk('local')->assertExists($profile->document_path);
    }

    /**
     * Kriteria 2: Mahasiswa mengisi data valid dan upload PDF valid -> status eligible dan pending
     */
    public function test_mahasiswa_can_submit_valid_verification_and_becomes_pending_eligible()
    {
        $user = $this->createTalentUser();
        $file = UploadedFile::fake()->create('ktm_ui.pdf', 700, 'application/pdf');

        $response = $this->actingAs($user)->post('/talent/verifikasi', [
            'category' => 'Mahasiswa Aktif',
            'institution' => 'Universitas Indonesia',
            'major' => 'Ilmu Komputer',
            'city' => 'Depok',
            'entry_year' => now()->year - 2,
            'current_semester' => 4,
            'academic_year' => '2025/2026',
            'document' => $file,
        ]);

        $response->assertSessionHas('success');

        $profile = $user->fresh()->talentProfile;
        $this->assertEquals('eligible', $profile->eligibility_status);
        $this->assertEquals('pending', $profile->verification_status);
        $this->assertNotNull($profile->document_path);
    }

    /**
     * Kriteria 3: Fresh graduate kelulusan ≤ 24 bulan -> lolos screening (eligible dan pending)
     */
    public function test_fresh_graduate_within_24_months_becomes_pending_eligible()
    {
        $user = $this->createTalentUser();
        $file = UploadedFile::fake()->create('ijazah.pdf', 900, 'application/pdf');

        $response = $this->actingAs($user)->post('/talent/verifikasi', [
            'category' => 'Fresh Graduate (≤ 2 Tahun)',
            'institution' => 'Institut Teknologi Bandung',
            'major' => 'Teknik Elektro',
            'city' => 'Bandung',
            'graduation_month' => now()->month,
            'graduation_year' => now()->year - 1, // 12 bulan yang lalu
            'document' => $file,
        ]);

        $response->assertSessionHas('success');

        $profile = $user->fresh()->talentProfile;
        $this->assertEquals('eligible', $profile->eligibility_status);
        $this->assertEquals('pending', $profile->verification_status);
    }

    /**
     * Kriteria 4: Fresh graduate melewati 24 bulan -> ditolak otomatis (ineligible dan rejected)
     */
    public function test_fresh_graduate_exceeding_24_months_is_automatically_ineligible_and_rejected()
    {
        $user = $this->createTalentUser();
        $file = UploadedFile::fake()->create('ijazah_lama.pdf', 800, 'application/pdf');

        $response = $this->actingAs($user)->post('/talent/verifikasi', [
            'category' => 'Fresh Graduate (≤ 2 Tahun)',
            'institution' => 'Universitas Padjadjaran',
            'major' => 'Manajemen',
            'city' => 'Jatinangor',
            'graduation_month' => now()->month,
            'graduation_year' => now()->year - 3, // 36 bulan yang lalu (> 24 bulan)
            'document' => $file,
        ]);

        $response->assertSessionHas('error');

        $profile = $user->fresh()->talentProfile;
        $this->assertEquals('ineligible', $profile->eligibility_status);
        $this->assertEquals('rejected', $profile->verification_status);
        $this->assertNotNull($profile->rejection_reason);
    }

    /**
     * Kriteria 5: Upload PDF lebih dari 2 MB -> ditolak
     */
    public function test_upload_exceeding_2mb_is_rejected()
    {
        $user = $this->createTalentUser();
        // 3000 KB > 2048 KB
        $file = UploadedFile::fake()->create('large.pdf', 3000, 'application/pdf');

        $response = $this->actingAs($user)->post('/talent/verifikasi', [
            'category' => 'Mahasiswa Aktif',
            'institution' => 'Universitas Indonesia',
            'major' => 'Ilmu Komputer',
            'city' => 'Depok',
            'entry_year' => now()->year - 1,
            'current_semester' => 2,
            'academic_year' => '2025/2026',
            'document' => $file,
        ]);

        $response->assertSessionHasErrors('document');
    }

    /**
     * Kriteria 6: Upload non-PDF -> ditolak
     */
    public function test_upload_non_pdf_is_rejected()
    {
        $user = $this->createTalentUser();
        $file = UploadedFile::fake()->create('dokumen.jpg', 500, 'image/jpeg');

        $response = $this->actingAs($user)->post('/talent/verifikasi', [
            'category' => 'Mahasiswa Aktif',
            'institution' => 'Universitas Indonesia',
            'major' => 'Ilmu Komputer',
            'city' => 'Depok',
            'entry_year' => now()->year - 1,
            'current_semester' => 2,
            'academic_year' => '2025/2026',
            'document' => $file,
        ]);

        $response->assertSessionHasErrors('document');
    }

    /**
     * Kriteria 7: Admin dapat melihat dokumen PDF asli
     */
    public function test_admin_can_preview_uploaded_pdf()
    {
        $admin = $this->createAdminUser();
        $talent = $this->createTalentUser();

        // Buat file PDF di disk
        $filePath = 'talent_documents/test_doc.pdf';
        Storage::disk('local')->put($filePath, '%PDF-1.4 test file');

        $talent->talentProfile->update([
            'document_path' => $filePath,
            'document_original_name' => 'KTM_Test.pdf',
            'document_size' => 1024,
            'verification_status' => 'pending',
            'eligibility_status' => 'eligible',
        ]);

        $response = $this->actingAs($admin)->get("/admin/verifikasi/{$talent->talentProfile->id}/dokumen");
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    /**
     * Kriteria 8 & 9: Admin dapat menyetujui dan menolak dengan alasan
     */
    public function test_admin_can_approve_and_reject_verification()
    {
        $admin = $this->createAdminUser();
        $talent = $this->createTalentUser();

        $talent->talentProfile->update([
            'verification_status' => 'pending',
            'eligibility_status' => 'eligible',
        ]);

        // Test Approve
        $approveResp = $this->actingAs($admin)->post("/admin/verifikasi/{$talent->talentProfile->id}/approve");
        $approveResp->assertSessionHas('success');

        $this->assertEquals('verified', $talent->fresh()->talentProfile->verification_status);
        $this->assertEquals('eligible', $talent->fresh()->talentProfile->eligibility_status);
        $this->assertEquals($admin->id, $talent->fresh()->talentProfile->verified_by);

        // Test Reject
        $rejectResp = $this->actingAs($admin)->post("/admin/verifikasi/{$talent->talentProfile->id}/reject", [
            'reason' => 'Nama pada dokumen buram dan tidak terbaca jelas.',
        ]);
        $rejectResp->assertSessionHas('warning');

        $this->assertEquals('rejected', $talent->fresh()->talentProfile->verification_status);
        $this->assertEquals('Nama pada dokumen buram dan tidak terbaca jelas.', $talent->fresh()->talentProfile->rejection_reason);
    }

    /**
     * Kriteria 11 & 12: Pembatasan lamaran proyek & akses dokumen
     */
    public function test_unverified_talent_cannot_apply_to_project()
    {
        $talent = $this->createTalentUser();
        $project = Project::create([
            'provider_name' => 'Mitra Test',
            'title' => 'Proyek Uji Coba',
            'category' => 'Web Development',
            'description' => 'Deskripsi proyek pengujian',
            'status' => 'Dipublikasikan',
        ]);

        // Status unverified (not_submitted)
        $response = $this->actingAs($talent)->post("/talent/proyek/{$project->id}/lamar", [
            'message' => 'Saya ingin bergabung.',
        ]);

        // Harus diarahkan ke halaman verifikasi dengan peringatan
        $response->assertRedirect('/talent/verifikasi');
        $this->assertDatabaseMissing('applications', [
            'project_id' => $project->id,
            'user_id' => $talent->id,
        ]);
    }

    public function test_verified_talent_can_apply_to_project()
    {
        $talent = $this->createTalentUser();
        $talent->talentProfile->update([
            'verification_status' => 'verified',
            'eligibility_status' => 'eligible',
        ]);

        $project = Project::create([
            'provider_name' => 'Mitra Test 2',
            'title' => 'Proyek Uji Coba 2',
            'category' => 'Web Development',
            'description' => 'Deskripsi proyek pengujian 2',
            'status' => 'Dipublikasikan',
        ]);

        $response = $this->actingAs($talent)->post("/talent/proyek/{$project->id}/lamar", [
            'message' => 'Saya siap berkarya.',
        ]);

        $response->assertRedirect('/talent/lamaran');
        $this->assertDatabaseHas('applications', [
            'project_id' => $project->id,
            'user_id' => $talent->id,
            'status' => 'Menunggu',
        ]);
    }
}
