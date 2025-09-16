<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;


class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function バリデーション_未入力メッセージが出る()
    {
        $resp = $this->post('/register', []);
        $resp->assertSessionHasErrors([
            'name'     => 'お名前を入力してください',
            'email'    => 'メールアドレスを入力してください',
            'password' => 'パスワードを入力してください',
        ]);
    }

    /** @test */
    public function バリデーション_パスワード8文字未満でエラー()
    {
        $resp = $this->post('/register', [
            'name'                  => '山田太郎',
            'email'                 => 'taro@example.com',
            'password'              => '1234567',
            'password_confirmation' => '1234567',
        ]);
        $resp->assertSessionHasErrors([
            'password' => 'パスワードは8文字以上で入力してください',
        ]);
    }

    /** @test */
    public function バリデーション_確認用パスワード不一致でエラー()
    {
        $resp = $this->post('/register', [
            'name'                  => '山田太郎',
            'email'                 => 'taro@example.com',
            'password'              => '12345678',
            'password_confirmation' => 'abcdefgh',
        ]);
        $resp->assertSessionHasErrors([
            'password' => 'パスワードと一致しません',
        ]);
    }

    /** @test */
    public function 正常系_ユーザーが保存され_打刻画面へリダイレクト()
    {
        $resp = $this->post('/register', [
            'name'                  => '山田太郎',
            'email'                 => 'taro@example.com',
            'password'              => '12345678',
            'password_confirmation' => '12345678',
        ]);

        $resp->assertRedirect(route('attendance.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'taro@example.com',
            'name'  => '山田太郎',
        ]);
        $this->assertAuthenticated();
    }

    /** @test */
    public function 会員登録直後に認証メールが送信される()
    {
        Notification::fake();

        $resp = $this->post('/register', [
            'name'                  => 'new user',
            'email'                 => 'new@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $resp->assertRedirect(); // 登録後にどこかへリダイレクト（既存の挙動でOK）
        $user = \App\Models\User::where('email', 'new@example.com')->first();
        $this->assertNotNull($user);

        // 認証メールが送られたことを検証
        Notification::assertSentTo($user, VerifyEmail::class);
    }
}
