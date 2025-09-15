@extends('layouts.app')

@section('content')
<div class="container mx-auto max-w-md py-8">
    <h1 class="text-2xl font-bold mb-6 text-center">会員登録</h1>

    <form method="POST" action="{{ route('register.store') }}" novalidate>
        @csrf

        <div class="mb-4">
            <label class="block mb-2">名前</label>
            <input type="text" name="name" value="{{ old('name') }}" class="w-full border rounded p-2">
            @error('name')
            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-4">
            <label class="block mb-2">メールアドレス</label>
            <input type="email" name="email" value="{{ old('email') }}" class="w-full border rounded p-2">
            @error('email')
            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-4">
            <label class="block mb-2">パスワード</label>
            <input type="password" name="password" class="w-full border rounded p-2">
            @error('password')
            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6">
            <label class="block mb-2">パスワード確認</label>
            <input type="password" name="password_confirmation" class="w-full border rounded p-2">
        </div>

        <button type="submit" class="w-full bg-black text-white py-2 rounded">
            登録する
        </button>
    </form>
</div>
@endsection