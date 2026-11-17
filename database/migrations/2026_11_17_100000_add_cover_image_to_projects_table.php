<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('projects', 'cover_image')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->string('cover_image')->nullable()->after('description');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('projects', 'cover_image')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->dropColumn('cover_image');
            });
        }
    }
};
