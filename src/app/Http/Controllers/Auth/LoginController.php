<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class LoginController extends Controller
{
    public function create()
    {
        if (view()->exists('auth.login')) {
            return view('auth.login');
        }
        return response('login page', 200);
    }

    public function store(\App\Http\Requests\Auth\LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        if (\Illuminate\Support\Facades\Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = \Illuminate\Support\Facades\Auth::user();

            // ★変更：型を判定してからメソッドを呼ぶ（IDEも静的に理解できる）
            if ($user instanceof MustVerifyEmail && !$user->hasVerifiedEmail()) {
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

    public function destroy(\Illuminate\Http\Request $request)
    {
        \Illuminate\Support\Facades\Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
