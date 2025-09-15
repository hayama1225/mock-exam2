<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 未認証ユーザーがログインすると_認証誘導へリダイレクトされ_認証メールが再送される()
    {
        Notification::fake();

        // 未認証ユーザーを用意
        $user = User::factory()->create([
            'email'              => 'taro@example.com',
            'password'           => Hash::make('secret-pass'),
            'email_verified_at'  => null,
        ]);

        // 認証前ログイン
        $resp = $this->post('/login', [
            'email'    => 'taro@example.com',
            'password' => 'secret-pass',
        ]);

        $resp->assertRedirect(route('verification.notice'));
        $this->assertAuthenticatedAs($user);

        // 認証メールが送られていること
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    /** @test */
    public function 署名付きURLアクセスでメール認証が完了し_打刻画面へ遷移する()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        // 認証リンク（署名付き）を生成
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->getKey(), 'hash' => sha1($user->getEmailForVerification())]
        );

        // 認証処理はログイン済みで行う仕様
        $this->actingAs($user);

        $resp = $this->get($verificationUrl);

        $resp->assertRedirect(route('attendance.index'));

        // 認証済みになっていること
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    /** @test */
    public function 認証メールを再送できる()
    {
        Notification::fake();

        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $this->actingAs($user);

        $resp = $this->post(route('verification.send'));
        $resp->assertStatus(302); // back()

        Notification::assertSentTo($user, VerifyEmail::class);
    }
}
