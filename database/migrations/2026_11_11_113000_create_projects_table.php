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
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('category');
            $table->string('location');
            $table->string('work_method')->default('onsite'); // onsite, hybrid, remote
            $table->string('compensation')->nullable();
            $table->text('required_competencies')->nullable(); // comma-separated or json
            $table->string('availability')->nullable(); // full-time, part-time, fleksibel, akhir pekan, dll.
            $table->unsignedInteger('talents_needed')->default(1);
            $table->date('start_date');
            $table->date('end_date');
            $table->date('registration_deadline')->nullable();
            $table->text('description');
            $table->text('responsibilities')->nullable();
            $table->json('workstyle_characteristics')->nullable(); // 8 dimensions
            $table->string('status')->default('draft'); // draft, published, closed, archived
            $table->softDeletes();
            $table->timestamps();

            $table->index(['user_id', 'status']);
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
