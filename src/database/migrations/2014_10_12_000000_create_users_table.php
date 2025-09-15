<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');                    // お名前
            $table->string('email')->unique();         // メール
            $table->string('password');                // パスワード
            $table->boolean('is_admin')->default(false); // 管理者フラグ（将来用）
            $table->timestamp('email_verified_at')->nullable();
            $table->string('remember_token', 100)->nullable();
            // ※ timestamps() は使わない
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
