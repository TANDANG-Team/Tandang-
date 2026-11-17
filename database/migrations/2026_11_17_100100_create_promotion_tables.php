<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sponsored Project: paket, transaksi, promosi, event analitik, atribusi lamaran, audit log.
 * Hanya membuat tabel baru — tidak mengubah tabel yang sudah ada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('price');                 // rupiah (integer)
            $table->unsignedSmallInteger('duration_hours');
            $table->text('description')->nullable();
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('promotion_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('transaction_number')->unique();
            $table->unsignedInteger('amount');                          // ditetapkan backend
            $table->string('status')->default('pending')->index();      // pending, paid, failed, expired, refunded
            $table->string('payment_method')->default('qris_simulation');
            $table->string('payment_reference')->nullable()->unique();  // referensi gateway/simulasi
            $table->text('qr_payload')->nullable();
            $table->timestamp('expires_at')->nullable()->index();       // batas bayar (≠ masa tayang)
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('failure_reason')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->unsignedInteger('refunded_amount')->default(0);
            $table->json('gateway_payload')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::create('project_promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('promotion_package_id')->constrained('promotion_packages')->restrictOnDelete();
            $table->foreignId('promotion_transaction_id')->unique()->constrained('promotion_transactions')->restrictOnDelete();

            // Snapshot paket saat pembelian
            $table->string('package_name');
            $table->unsignedInteger('price');
            $table->unsignedSmallInteger('duration_hours');

            $table->string('status')->default('pending_payment')->index(); // pending_payment, active, ended, stopped, cancelled
            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();

            $table->timestamp('stopped_at')->nullable();
            $table->foreignId('stopped_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('stop_reason', 500)->nullable();
            $table->unsignedInteger('remaining_seconds_at_stop')->nullable();
            $table->timestamps();

            $table->index(['status', 'start_at', 'end_at']);
            $table->index(['project_id', 'status']);
        });

        Schema::create('promotion_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_promotion_id')->constrained('project_promotions')->cascadeOnDelete();
            $table->string('type', 20);                   // impression, click
            $table->char('visitor_hash', 64);             // HMAC id pengguna, tanpa data pribadi
            $table->char('page_token', 32)->nullable();   // id muat-halaman untuk dedup impression
            $table->string('placement', 20)->nullable();  // banner, list
            $table->timestamp('occurred_at')->index();

            $table->index(['project_promotion_id', 'type', 'occurred_at']);
            $table->index(['project_promotion_id', 'visitor_hash', 'type']);
        });

        Schema::create('promotion_attributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_promotion_id')->constrained('project_promotions')->cascadeOnDelete();
            $table->foreignId('project_application_id')->unique()->constrained('project_applications')->cascadeOnDelete();
            $table->timestamp('clicked_at');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['project_promotion_id', 'created_at']);
        });

        Schema::create('promotion_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_promotion_id')->nullable()->constrained('project_promotions')->cascadeOnDelete();
            $table->foreignId('promotion_transaction_id')->nullable()->constrained('promotion_transactions')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 50);
            $table->text('details')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_audit_logs');
        Schema::dropIfExists('promotion_attributions');
        Schema::dropIfExists('promotion_events');
        Schema::dropIfExists('project_promotions');
        Schema::dropIfExists('promotion_transactions');
        Schema::dropIfExists('promotion_packages');
    }
};
