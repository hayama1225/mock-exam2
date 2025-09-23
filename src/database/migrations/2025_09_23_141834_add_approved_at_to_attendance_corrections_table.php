<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 位置指定（after）は外す。存在チェックも入れる
        if (!Schema::hasColumn('attendance_corrections', 'approved_at')) {
            Schema::table('attendance_corrections', function (Blueprint $table) {
                $table->timestamp('approved_at')->nullable(); // 末尾に追加でOK
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('attendance_corrections', 'approved_at')) {
            Schema::table('attendance_corrections', function (Blueprint $table) {
                $table->dropColumn('approved_at');
            });
        }
    }
};
