@extends('layouts.app')

@php($hideHeaderActions = true)

@section('title', '会員登録')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/register.css') }}">
@endpush

@section('content')
<main class="main">
    <div class="register-wrap">
        <h1 class="register-title">会員登録</h1>

        <form method="POST" action="{{ route('register.store') }}" novalidate class="register-form">
            @csrf

            <div class="form-group">
                <label for="name" class="form-label">名前</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" class="input-text">
                @error('name')
                <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group">
                <label for="email" class="form-label">メールアドレス</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" class="input-text">
                @error('email')
                <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group">
                <label for="password" class="form-label">パスワード</label>
                <input id="password" type="password" name="password" class="input-text">
                @error('password')
                <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group">
                <label for="password_confirmation" class="form-label">パスワード確認</label>
                <input id="password_confirmation" type="password" name="password_confirmation" class="input-text">
            </div>

            <button type="submit" class="submit-btn">登録する</button>
        </form>

        <p class="login-link">
            <a href="{{ route('login') }}">ログインはこちら</a>
        </p>
    </div>
</main>
@endsection