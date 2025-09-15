@extends('layouts.app')

@section('content')
<div class="container mx-auto max-w-xl py-20 text-center">
    <p class="mb-8">
        登録していただいたメールアドレスに認証メールを送付しました。<br>
        メール認証を完了してください。
    </p>

    <div class="mb-8">
        <a href="http://localhost:8025" target="_blank" class="inline-block px-6 py-3 border rounded">
            認証はこちらから
        </a>
    </div>

    @if (session('status') === 'verification-link-sent')
    <p class="text-green-600 mb-4">認証メールを再送しました。</p>
    @endif

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="text-blue-600 underline">
            認証メールを再送する
        </button>
    </form>
</div>
@endsection