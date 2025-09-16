<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attendance_breaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->constrained('attendances')->cascadeOnDelete();
            $table->dateTime('break_start_at');     // 休憩入り
            $table->dateTime('break_end_at')->nullable(); // 休憩戻
            // $table->timestamps(); 使わない
            $table->index('attendance_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_breaks');
    }
};
