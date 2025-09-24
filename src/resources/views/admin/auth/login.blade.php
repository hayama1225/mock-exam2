@extends('layouts.app')

@php($hideHeaderActions = true)
@php($headerLogoUrl = route('admin.login')) {{-- ← ヘッダーロゴの遷移先を管理者ログインに固定 --}}

@section('title', '管理者ログイン')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/login.css') }}">
@endpush

@section('content')
<main class="main">
    <div class="login-wrap">
        <h1 class="login-title">管理者ログイン</h1>

        <form method="POST" action="{{ route('admin.login.store') }}" novalidate class="login-form">
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

            {{-- 管理者ログインボタンのみ（会員登録リンクは出さない） --}}
            <button type="submit" class="submit-btn">管理者ログインする</button>
        </form>
    </div>
</main>
@endsection