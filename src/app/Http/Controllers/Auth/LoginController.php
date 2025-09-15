<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function create()
    {
        // Blade があればビューを返す
        if (view()->exists('auth.login')) {
            return view('auth.login');
        }
        // 仮表示（Blade未作成時用）
        return response('login page', 200);
    }

    public function store(\App\Http\Requests\Auth\LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        if (\Illuminate\Support\Facades\Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = \Illuminate\Support\Facades\Auth::user();

            if (!$user->hasVerifiedEmail()) {
                // 未認証なら再送して誘導
                $user->sendEmailVerificationNotification();
                return redirect()->route('verification.notice')
                    ->with('status', 'verification-link-sent');
            }

            return redirect()->route('attendance.index');
        }

        return back()->withErrors([
            'email' => 'ログイン情報が登録されていません',
        ])->onlyInput('email');
    }
}
