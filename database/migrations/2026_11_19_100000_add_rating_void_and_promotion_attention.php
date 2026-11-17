<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aditif (tanpa menghapus data):
 *  - ratings: rating yang dibatalkan Admin karena pelanggaran tidak dihitung ke reputasi.
 *  - promotion_transactions: penanda "perlu penanganan Admin" (mis. rating Penyedia turun di bawah
 *    syarat setelah membayar) agar dana/hak Penyedia tidak hilang tanpa keputusan Admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ratings', function (Blueprint $table) {
            $table->timestamp('voided_at')->nullable()->after('hidden_at');
            $table->foreignId('voided_by')->nullable()->after('voided_at')->constrained('users')->nullOnDelete();
            $table->string('void_reason', 500)->nullable()->after('voided_by');
        });

        Schema::table('promotion_transactions', function (Blueprint $table) {
            $table->boolean('needs_admin_attention')->default(false)->after('decision_note')->index();
            $table->string('attention_reason', 500)->nullable()->after('needs_admin_attention');
        });
    }

    public function down(): void
    {
        Schema::table('promotion_transactions', function (Blueprint $table) {
            $table->dropIndex(['needs_admin_attention']);
            $table->dropColumn(['needs_admin_attention', 'attention_reason']);
        });

        Schema::table('ratings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('voided_by');
            $table->dropColumn(['voided_at', 'void_reason']);
        });
    }
};
