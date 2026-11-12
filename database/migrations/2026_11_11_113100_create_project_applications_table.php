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
        Schema::create('project_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('menunggu'); // menunggu, dalam_review, diterima, ditolak
            $table->text('cover_letter')->nullable();
            $table->unsignedTinyInteger('match_score')->nullable(); // percentage 0-100
            $table->json('talent_snapshot')->nullable(); // snapshot profile for seamless local/branch fallback
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'user_id']);
            $table->index(['project_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_applications');
    }
};
