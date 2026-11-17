<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rating dua arah. Status pengerjaan per Talenta disimpan terpisah dari status lamaran,
 * sehingga status `diterima` di project_applications tidak diubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_work_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_application_id')->unique()->constrained('project_applications')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('talent_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained('users')->cascadeOnDelete();

            $table->string('status')->default('in_progress')->index(); // in_progress, pending_confirmation, completed, disputed
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('requested_at')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('rating_deadline_at')->nullable()->index();

            $table->foreignId('disputed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('disputed_at')->nullable();
            $table->string('dispute_reason', 1000)->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->string('resolution_note', 1000)->nullable();
            $table->timestamps();

            $table->index(['provider_id', 'status']);
            $table->index(['talent_id', 'status']);
        });

        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_work_completion_id')->constrained('project_work_completions')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('rater_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('ratee_id')->constrained('users')->cascadeOnDelete();
            $table->string('rater_role', 20); // penyedia, talenta
            $table->string('ratee_role', 20);
            $table->json('scores');            // {aspek: 1-5 | null (N/A)}
            $table->decimal('final_score', 3, 2);
            $table->string('review', 500)->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->boolean('review_hidden')->default(false);
            $table->string('hidden_reason', 500)->nullable();
            $table->foreignId('hidden_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('hidden_at')->nullable();
            $table->timestamps();

            $table->unique(['project_work_completion_id', 'rater_id']); // anti rating ganda
            $table->index(['ratee_id', 'ratee_role', 'published_at']);
        });

        Schema::create('rating_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rating_id')->constrained('ratings')->cascadeOnDelete();
            $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 20)->default('violation'); // violation, correction
            $table->string('reason', 1000);
            $table->string('status', 20)->default('open')->index(); // open, resolved, dismissed
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->string('admin_note', 1000)->nullable();
            $table->timestamps();

            $table->unique(['rating_id', 'reporter_id', 'type']);
        });

        Schema::create('rating_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rating_id')->nullable()->constrained('ratings')->cascadeOnDelete();
            $table->foreignId('project_work_completion_id')->nullable()->constrained('project_work_completions')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 50);
            $table->text('details')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rating_audit_logs');
        Schema::dropIfExists('rating_reports');
        Schema::dropIfExists('ratings');
        Schema::dropIfExists('project_work_completions');
    }
};
