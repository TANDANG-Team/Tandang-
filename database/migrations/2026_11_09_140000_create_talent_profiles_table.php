<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('talent_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('category')->default('Mahasiswa'); // 'Siswa SMK/SMA', 'Mahasiswa', 'Fresh Graduate'
            $table->string('institution')->nullable();
            $table->string('major')->nullable();
            $table->string('city')->nullable();
            $table->string('document')->nullable(); // File name or document link
            $table->string('verification_status')->default('Menunggu Verifikasi'); // 'Menunggu Verifikasi', 'Terverifikasi', 'Ditolak'
            $table->text('rejection_reason')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talent_profiles');
    }
};

