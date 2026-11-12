<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('talent_profiles', function (Blueprint $table) {
            // Status Kelayakan (Rule-based automatic screening)
            $table->string('eligibility_status')->default('undetermined')->after('category'); // eligible, ineligible, undetermined

            // Berkas Dokumen PDF Asli di Storage Private
            $table->string('document_path')->nullable()->after('document');
            $table->string('document_original_name')->nullable()->after('document_path');
            $table->unsignedInteger('document_size')->nullable()->after('document_original_name'); // bytes

            // Dynamic fields untuk Siswa SMK
            $table->string('class_level')->nullable()->after('major'); // X, XI, XII, XIII
            $table->string('academic_year')->nullable()->after('class_level'); // 2025/2026
            $table->unsignedSmallInteger('graduation_year')->nullable()->after('academic_year'); // e.g. 2027

            // Dynamic fields untuk Mahasiswa Aktif
            $table->unsignedSmallInteger('entry_year')->nullable()->after('graduation_year');
            $table->unsignedTinyInteger('current_semester')->nullable()->after('entry_year');

            // Dynamic fields untuk Fresh Graduate
            $table->unsignedTinyInteger('graduation_month')->nullable()->after('current_semester'); // 1 - 12

            // Catatan screening kelayakan otomatis & Reviewer
            $table->text('screening_notes')->nullable()->after('rejection_reason');
            $table->foreignId('verified_by')->nullable()->after('verified_at')->constrained('users')->nullOnDelete();
        });

        // Migrasikan nilai status lama ke format standar baru jika ada
        DB::table('talent_profiles')->where('verification_status', 'Menunggu Verifikasi')->update([
            'verification_status' => 'pending',
            'eligibility_status' => 'eligible',
            'screening_notes' => 'Screening otomatis: Memenuhi syarat awal (data migrasi). Menunggu pemeriksaan dokumen oleh admin.',
        ]);

        DB::table('talent_profiles')->where('verification_status', 'Terverifikasi')->update([
            'verification_status' => 'verified',
            'eligibility_status' => 'eligible',
            'screening_notes' => 'Screening otomatis: Memenuhi syarat awal (data migrasi).',
        ]);

        DB::table('talent_profiles')->where('verification_status', 'Ditolak')->update([
            'verification_status' => 'rejected',
            'eligibility_status' => 'ineligible',
        ]);

        DB::table('talent_profiles')->where('verification_status', 'Belum Mengajukan')->update([
            'verification_status' => 'not_submitted',
            'eligibility_status' => 'undetermined',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('talent_profiles', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
            $table->dropColumn([
                'eligibility_status',
                'document_path',
                'document_original_name',
                'document_size',
                'class_level',
                'academic_year',
                'graduation_year',
                'entry_year',
                'current_semester',
                'graduation_month',
                'screening_notes',
                'verified_by',
            ]);
        });
    }
};
