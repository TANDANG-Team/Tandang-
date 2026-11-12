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
        Schema::table('projects', function (Blueprint $table) {
            $table->json('skills')->nullable()->after('responsibilities');
            $table->string('schedule')->default('Fleksibel')->after('work_type'); // Akhir pekan, Hari kerja, Fleksibel
            $table->unsignedInteger('quota')->default(1)->after('compensation');
            $table->date('start_date')->nullable()->after('deadline');
            $table->date('end_date')->nullable()->after('start_date');
            $table->json('workstyle_requirements')->nullable()->after('status');
            $table->boolean('workstyle_confirmed')->default(true)->after('workstyle_requirements');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'skills',
                'schedule',
                'quota',
                'start_date',
                'end_date',
                'workstyle_requirements',
                'workstyle_confirmed',
            ]);
        });
    }
};
