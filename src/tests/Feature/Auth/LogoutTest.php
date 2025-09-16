<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function ログイン中のユーザーはログアウトできてログイン画面へリダイレクトされる()
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user);

        $resp = $this->post('/logout');

        $resp->assertRedirect(route('login'));
        $this->assertGuest();
    }

    /** @test */
    public function 未ログイン状態でログアウトPOSTしてもログイン画面へリダイレクトされる()
    {
        $resp = $this->post('/logout');
        $resp->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
