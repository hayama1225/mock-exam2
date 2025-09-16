<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            if (!Schema::hasColumn('attendances', 'total_break_seconds')) {
                // 末尾に追加（AFTERを使わない）
                $table->unsignedInteger('total_break_seconds')->default(0);
            }
            if (!Schema::hasColumn('attendances', 'work_seconds')) {
                $table->unsignedInteger('work_seconds')->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            if (Schema::hasColumn('attendances', 'work_seconds')) {
                $table->dropColumn('work_seconds');
            }
            if (Schema::hasColumn('attendances', 'total_break_seconds')) {
                $table->dropColumn('total_break_seconds');
            }
        });
    }
};
