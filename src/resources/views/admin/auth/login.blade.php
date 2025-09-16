@extends('layouts.app')

@section('title', '管理者ログイン')

@section('content')
<div class="container" style="max-width:760px;margin:60px auto;">
    <h1 style="text-align:center;font-size:28px;margin-bottom:32px;">管理者ログイン</h1>

    @if ($errors->any())
    <div style="background:#fee;border:1px solid #f99;padding:12px 16px;margin-bottom:16px;">
        <ul style="margin:0;padding-left:18px;">
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('admin.login.store') }}" novalidate>
        @csrf

        <div style="margin-bottom:18px;">
            <label for="email">メールアドレス</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}"
                style="width:100%;padding:12px;border:1px solid #ccc;border-radius:6px;">
            @error('email') <div style="color:#d00;margin-top:6px;">{{ $message }}</div> @enderror
        </div>

        <div style="margin-bottom:26px;">
            <label for="password">パスワード</label>
            <input id="password" type="password" name="password"
                style="width:100%;padding:12px;border:1px solid #ccc;border-radius:6px;">
            @error('password') <div style="color:#d00;margin-top:6px;">{{ $message }}</div> @enderror
        </div>

        <button type="submit"
            style="width:100%;padding:14px 16px;border:none;border-radius:6px;background:#000;color:#fff;font-weight:700;">
            管理者ログインする
        </button>
    </form>
</div>
@endsection