<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->date('work_date');                             // 勤務日（1日1レコード）
            $table->dateTime('clock_in_at')->nullable();           // 出勤
            $table->dateTime('clock_out_at')->nullable();          // 退勤
            $table->tinyInteger('status')->default(0);             // 0:勤務外 1:出勤中 2:休憩中 3:退勤済
            // $table->timestamps(); 使わない
            $table->unique(['user_id', 'work_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
