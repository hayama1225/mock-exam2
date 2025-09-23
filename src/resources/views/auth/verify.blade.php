@extends('layouts.app')

@php($hideHeaderActions = true)

@section('title', 'メール認証のお願い')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/verify.css') }}">
@endpush

@section('content')
<main class="main">
    <div class="verify-wrap">
        <p class="verify-lead">
            登録していただいたメールアドレスに認証メールを送付しました。<br>
            メール認証を完了してください。
        </p>

        <div class="verify-action">
            <a href="http://localhost:8025" target="_blank" rel="noopener" class="verify-btn">
                認証はこちらから
            </a>
        </div>

        @if (session('status') === 'verification-link-sent')
        <p class="verify-flash">認証メールを再送しました。</p>
        @endif

        {{-- Fortify要件：POSTで再送。見た目はアンカー風 --}}
        <form method="POST" action="{{ route('verification.send') }}" class="verify-resend">
            @csrf
            <button type="submit" class="verify-resend__link">認証メールを再送する</button>
        </form>
    </div>
</main>
@endsection