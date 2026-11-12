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
        Schema::table('talent_profiles', function (Blueprint $table) {
            $table->text('bio')->nullable()->after('major');
            $table->json('skills')->nullable()->after('bio');
            $table->string('availability')->default('Fleksibel')->after('skills'); // Akhir pekan, Fleksibel, Penuh waktu
            $table->string('work_mode')->default('Keduanya')->after('availability'); // Remote, Onsite, Keduanya
            $table->json('assessment_answers')->nullable()->after('rejection_reason');
            $table->timestamp('assessment_completed_at')->nullable()->after('assessment_answers');
            $table->json('portfolios')->nullable()->after('assessment_completed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('talent_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'bio',
                'skills',
                'availability',
                'work_mode',
                'assessment_answers',
                'assessment_completed_at',
                'portfolios',
            ]);
        });
    }
};
