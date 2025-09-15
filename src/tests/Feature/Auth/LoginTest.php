<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function バリデーション_未入力メッセージが出る()
    {
        $resp = $this->post('/login', []);
        $resp->assertSessionHasErrors([
            'email'    => 'メールアドレスを入力してください',
            'password' => 'パスワードを入力してください',
        ]);
    }

    /** @test */
    public function 入力情報が誤っている場合_エラーメッセージが出る()
    {
        $user = User::factory()->create([
            'email'    => 'taro@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        $resp = $this->post('/login', [
            'email'    => 'taro@example.com',
            'password' => 'wrong-password',
        ]);

        $resp->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }

    /** @test */
    public function 正常系_ログイン成功後_打刻画面へリダイレクト()
    {
        $user = User::factory()->create([
            'email'    => 'taro@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        $resp = $this->post('/login', [
            'email'    => 'taro@example.com',
            'password' => 'correct-password',
        ]);

        $resp->assertRedirect(route('attendance.index'));
        $this->assertAuthenticatedAs($user);
    }
}
