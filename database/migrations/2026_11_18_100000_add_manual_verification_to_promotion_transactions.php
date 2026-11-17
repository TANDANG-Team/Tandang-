<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * QRIS statis + verifikasi manual Admin. Hanya menambah kolom (aman untuk data yang ada).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promotion_transactions', function (Blueprint $table) {
            $table->boolean('is_simulation')->default(false)->after('payment_method')->index();

            // Konfirmasi dari Penyedia ("Saya Sudah Bayar") — klaim, bukan bukti lunas.
            $table->string('proof_path')->nullable()->after('qr_payload');
            $table->char('proof_hash', 64)->nullable()->after('proof_path')->index();
            $table->string('payer_name', 150)->nullable()->after('proof_hash');
            $table->string('payer_reference', 100)->nullable()->after('payer_name')->index();
            $table->unsignedInteger('payer_amount')->nullable()->after('payer_reference');
            $table->timestamp('payer_paid_at')->nullable()->after('payer_amount');
            $table->timestamp('submitted_at')->nullable()->after('payer_paid_at');
            $table->boolean('late_submission')->default(false)->after('submitted_at');

            // Keputusan Admin
            $table->foreignId('verified_by')->nullable()->after('late_submission')->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable()->after('verified_by');
            $table->string('decision_note', 1000)->nullable()->after('verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('promotion_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('verified_by');
            $table->dropIndex(['is_simulation']);
            $table->dropIndex(['proof_hash']);
            $table->dropIndex(['payer_reference']);
            $table->dropColumn([
                'is_simulation', 'proof_path', 'proof_hash', 'payer_name', 'payer_reference', 'payer_amount',
                'payer_paid_at', 'submitted_at', 'late_submission', 'verified_at', 'decision_note',
            ]);
        });
    }
};
