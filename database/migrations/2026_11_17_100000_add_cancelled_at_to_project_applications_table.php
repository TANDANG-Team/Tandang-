<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pembatalan lamaran oleh talenta: status "dibatalkan" + waktu pembatalan.
     * cancelled_at dipakai untuk membatasi 3 pembatalan per 7 hari per talenta.
     */
    public function up(): void
    {
        Schema::table('project_applications', function (Blueprint $table) {
            $table->timestamp('cancelled_at')->nullable()->after('reviewed_at');
            $table->index(['user_id', 'cancelled_at']);
        });
    }

    public function down(): void
    {
        Schema::table('project_applications', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'cancelled_at']);
            $table->dropColumn('cancelled_at');
        });
    }
};
