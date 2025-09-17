<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Auth\RegistrationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\Auth\AuthenticatedSessionController as AdminLogin;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

// ルート（未ログインならログインへ）
Route::get('/', function () {
    return redirect()->route('login');
})->name('root');

// ★ guestミドルウェアが飛ばす /home を吸収して適切に振り分け
Route::get('/home', function () {
    if (Auth::guard('admin')->check()) {
        return redirect()->route('admin.attendance.list');
    }
    if (Auth::check()) {
        return redirect()->route('attendance.index');
    }
    return redirect()->route('login');
})->name('home');

// 会員登録（一般ユーザー）
Route::get('/register', [RegistrationController::class, 'create'])->name('register');
Route::post('/register', [RegistrationController::class, 'store'])->name('register.store');

// ログイン（一般ユーザー）
Route::get('/login', [LoginController::class, 'create'])->name('login');
Route::post('/login', [LoginController::class, 'store'])->name('login.store');

// ─────────────────────────────
// ★ 管理者ログイン（一般ユーザー認証の外側に配置）
// ─────────────────────────────
Route::prefix('admin')->name('admin.')->group(function () {
    // 未ログイン管理者のみ
    Route::middleware('guest:admin')->group(function () {
        Route::get('/login', [AdminLogin::class, 'create'])->name('login');
        Route::post('/login', [AdminLogin::class, 'store'])->name('login.store');
    });

    // ログアウト（管理者）
    Route::post('/logout', [AdminLogin::class, 'destroy'])
        ->middleware('auth:admin')
        ->name('logout');

    // ★ダッシュボード本実装（一覧）← ここをコントローラへ
    Route::middleware('auth:admin')->get('/attendance/list', [AdminAttendanceController::class, 'index'])
        ->name('attendance.list');

    // 勤怠詳細（表示・更新）
    Route::middleware('auth:admin')->group(function () {
        Route::get('/attendance/{attendance}', [AdminAttendanceController::class, 'show'])
            ->whereNumber('attendance')
            ->name('attendance.show');

        Route::put('/attendance/{attendance}', [AdminAttendanceController::class, 'update'])
            ->whereNumber('attendance')
            ->name('attendance.update');
    });
});

// 認証誘導/認証/再送（ログイン済みのみ）
Route::middleware('auth')->group(function () {
    // 認証誘導画面
    Route::get('/email/verify', function () {
        return view('auth.verify');
    })->name('verification.notice');

    // 認証リンク
    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill(); // email_verified_at をセット
        return redirect()->route('attendance.index');
    })->middleware(['signed', 'throttle:6,1'])->name('verification.verify');

    // 認証メール再送
    Route::post('/email/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();
        return back()->with('status', 'verification-link-sent');
    })->middleware('throttle:6,1')->name('verification.send');
});

// ログアウト（一般ユーザー）
Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// 一般ユーザーの勤怠系（ログイン必須）
Route::middleware(['auth'])->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');

    // 一覧・詳細
    Route::get('/attendance/list', [AttendanceController::class, 'list'])->name('attendance.list');
    Route::get('/attendance/detail/{attendance}', [AttendanceController::class, 'detail'])
        ->whereNumber('attendance')
        ->name('attendance.detail');

    Route::post('/attendance/detail/{attendance}/request', [AttendanceController::class, 'requestCorrection'])
        ->name('attendance.request');

    // 申請一覧・詳細（一般ユーザー）
    Route::get('/requests', [AttendanceController::class, 'requestsIndex'])->name('requests.index');
    Route::get('/requests/{correction}', [AttendanceController::class, 'requestsShow'])
        ->whereNumber('correction')
        ->name('requests.show');
});

