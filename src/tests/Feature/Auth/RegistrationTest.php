<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;


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
}
