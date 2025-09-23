@extends('layouts.app')

@php($hideHeaderActions = true)

@section('title', 'ログイン')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/login.css') }}">
@endpush

@section('content')
<main class="main">
    <div class="login-wrap">
        <h1 class="login-title">ログイン</h1>

        <form method="POST" action="{{ route('login.store') }}" novalidate class="login-form">
            @csrf

            {{-- メールアドレス --}}
            <div class="form-group">
                <label for="email" class="form-label">メールアドレス</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" class="input-text">
                @error('email')
                <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            {{-- パスワード --}}
            <div class="form-group">
                <label for="password" class="form-label">パスワード</label>
                <input id="password" type="password" name="password" class="input-text">
                @error('password')
                <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            {{-- ログインボタンのみ（申請ボタンは出しません） --}}
            <button type="submit" class="submit-btn">ログイン</button>
        </form>

        {{-- 会員登録への導線 --}}
        <p class="register-link">
            <a href="{{ route('register') }}">会員登録はこちら</a>
        </p>
    </div>
</main>
@endsection