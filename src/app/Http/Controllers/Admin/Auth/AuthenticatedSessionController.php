<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\AdminLoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    public function create()
    {
        return view('admin.auth.login');
    }

    public function store(AdminLoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        if (! Auth::guard('admin')->attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => trans('auth.failed')])
                ->withInput($request->only('email'));
        }

        $request->session()->regenerate();

        // 管理者ログイン後のダッシュボード（後続画面に合わせて変更）
        return redirect()->intended('/admin/attendance/list');
    }

    public function destroy(Request $request)
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/admin/login');
    }
}
