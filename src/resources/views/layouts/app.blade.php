<!doctype html>
<html lang="ja">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="{{ asset('css/sanitaze.css') }}">
    <link rel="stylesheet" href="{{ asset('css/common.css') }}">
    @stack('styles')
    <title>@yield('title', 'COACHTECH')</title>
</head>

<body>
    <header class="site-header">
        <div class="site-header__inner" style="display:flex;align-items:center;justify-content:space-between;">
            <a href="{{ url('/') }}" class="site-header__logoLink" aria-label="Home" style="display:inline-flex;align-items:center;">
                <img src="{{ asset('logo.svg') }}" alt="Logo" class="site-header__logo">
            </a>

            {{-- 右側アクション（ログイン/登録/メール認証誘導では非表示にする） --}}
            @if (empty($hideHeaderActions))
            @auth
            <nav class="site-header__nav" style="display:flex;gap:16px;align-items:center;">
                <a href="{{ route('attendance.index') }}">勤怠</a>
                <a href="{{ route('attendance.list') }}">勤怠一覧</a>
                <a href="{{ route('requests.index') }}">申請</a>
                <form method="POST" action="{{ route('logout') }}" style="display:inline;">
                    @csrf
                    <button type="submit" style="background:none;border:none;color:#fff;cursor:pointer;">ログアウト</button>
                </form>
            </nav>
            @endauth
            @endif
        </div>
    </header>

    @yield('content')
</body>

</html>