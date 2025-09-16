<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Auth\RegistrationController;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

// 会員登録（一般ユーザー）
Route::get('/register', [RegistrationController::class, 'create'])->name('register');
Route::post('/register', [RegistrationController::class, 'store'])->name('register.store');

// ログイン（一般ユーザー）
Route::get('/login', [LoginController::class, 'create'])->name('login');
Route::post('/login', [LoginController::class, 'store'])->name('login.store');

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

Route::middleware(['auth'])->group(function () {
    // Attendance routes start
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');

    // 一覧・詳細
    Route::get('/attendance/list', [AttendanceController::class, 'list'])->name('attendance.list');
    Route::get('/attendance/detail/{attendance}', [AttendanceController::class, 'detail'])
        ->whereNumber('attendance')
        ->name('attendance.detail');
    // Attendance routes end

    Route::post(
        '/attendance/detail/{attendance}/request',
        [AttendanceController::class, 'requestCorrection']
    )->name('attendance.request');

    // 申請一覧・詳細（一般ユーザー）
    Route::get('/requests', [AttendanceController::class, 'requestsIndex'])->name('requests.index');
    Route::get('/requests/{correction}', [AttendanceController::class, 'requestsShow'])
        ->whereNumber('correction')
        ->name('requests.show');
});
