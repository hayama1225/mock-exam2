@extends('layouts.app')

@section('content')
<div class="container mx-auto max-w-md py-8">
    <h1 class="text-2xl font-bold mb-6 text-center">ログイン</h1>

    <form method="POST" action="{{ route('login.store') }}" novalidate>
        @csrf

        {{-- メールアドレス --}}
        <div class="mb-4">
            <label class="block mb-2">メールアドレス</label>
            <input type="email" name="email" value="{{ old('email') }}" class="w-full border rounded p-2">
            @error('email')
            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- パスワード --}}
        <div class="mb-6">
            <label class="block mb-2">パスワード</label>
            <input type="password" name="password" class="w-full border rounded p-2">
            @error('password')
            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- ログインボタン --}}
        <button type="submit" class="w-full bg-black text-white py-2 rounded">
            ログイン
        </button>
    </form>

    {{-- 会員登録への導線 --}}
    <p class="mt-4 text-center">
        アカウントをお持ちでない方は
        <a href="{{ route('register') }}" class="text-blue-600 underline">こちら</a>から会員登録
    </p>
</div>
@endsection