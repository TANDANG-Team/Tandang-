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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('provider_name')->default('Mitra Tandang');
            $table->string('title');
            $table->string('category'); // e.g. Web Development, UI/UX Design, Desain Grafis, Digital Marketing
            $table->text('description');
            $table->text('responsibilities')->nullable();
            $table->string('location')->default('Remote'); // Remote, Bandung, Jakarta, dll
            $table->string('work_type')->default('Remote'); // Remote, Onsite, Hybrid
            $table->string('compensation')->nullable(); // e.g. Rp 1.500.000 / Proyek
            $table->date('deadline')->nullable();
            $table->string('status')->default('Dipublikasikan'); // Dipublikasikan, Draf, Ditutup, Diarsipkan
            $table->unsignedInteger('applicants_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};

