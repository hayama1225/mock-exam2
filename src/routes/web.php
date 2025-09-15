<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegistrationController;
use App\Http\Controllers\Auth\LoginController;

// 会員登録（一般ユーザー）
Route::get('/register', [RegistrationController::class, 'create'])->name('register');
Route::post('/register', [RegistrationController::class, 'store'])->name('register.store');

// ログイン（一般ユーザー）
Route::get('/login', [LoginController::class, 'create'])->name('login');
Route::post('/login', [LoginController::class, 'store'])->name('login.store');

// 打刻画面（遷移先のプレースホルダ：後で実装を行う。ここではルートだけ確保）
Route::view('/attendance', 'attendance.index')->name('attendance.index');
