<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * NOTE: Table 'projects' already exists. We only ADD missing columns needed
     * by the Penyedia (provider) module introduced in NonoRevisiBanyak.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // work_method: alias for existing work_type logic (onsite/hybrid/remote)
            if (!Schema::hasColumn('projects', 'work_method')) {
                $table->string('work_method')->default('onsite')->after('location');
            }
            // required_competencies: unified competency field
            if (!Schema::hasColumn('projects', 'required_competencies')) {
                $table->text('required_competencies')->nullable()->after('work_method');
            }
            // availability: schedule/availability text
            if (!Schema::hasColumn('projects', 'availability')) {
                $table->string('availability')->nullable()->after('quota');
            }
            // talents_needed: alias for quota
            if (!Schema::hasColumn('projects', 'talents_needed')) {
                $table->unsignedInteger('talents_needed')->default(1)->after('availability');
            }
            // registration_deadline: alias for deadline
            if (!Schema::hasColumn('projects', 'registration_deadline')) {
                $table->date('registration_deadline')->nullable()->after('end_date');
            }
            // workstyle_characteristics: new provider-side workstyle definition
            if (!Schema::hasColumn('projects', 'workstyle_characteristics')) {
                $table->json('workstyle_characteristics')->nullable()->after('status');
            }
            // deleted_at: soft deletes support
            if (!Schema::hasColumn('projects', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $cols = ['work_method', 'required_competencies', 'availability',
                     'talents_needed', 'registration_deadline', 'workstyle_characteristics',
                     'deleted_at'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('projects', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
