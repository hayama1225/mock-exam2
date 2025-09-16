<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attendance_corrections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('attendance_id');
            $table->date('work_date');

            // 申請後の希望時刻（全てnullable）
            $table->dateTime('clock_in_at')->nullable();
            $table->dateTime('clock_out_at')->nullable();
            $table->dateTime('break1_start_at')->nullable();
            $table->dateTime('break1_end_at')->nullable();
            $table->dateTime('break2_start_at')->nullable();
            $table->dateTime('break2_end_at')->nullable();

            $table->string('note');                  // 備考(必須)
            $table->string('status')->default('pending'); // pending|approved|rejected
            $table->timestamps();

            $table->index(['user_id', 'attendance_id']);
            $table->foreign('attendance_id')->references('id')->on('attendances')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_corrections');
    }
};
