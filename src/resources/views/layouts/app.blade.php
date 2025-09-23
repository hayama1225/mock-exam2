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
        <div class="site-header__inner">
            <a href="{{ url('/') }}" class="site-header__logoLink" aria-label="Home">
                <img src="{{ asset('logo.svg') }}" alt="COACHTECH" class="site-header__logo">
            </a>

            {{-- 右側アクション（ログイン/登録/メール認証誘導では非表示にする） --}}
            @if (empty($hideHeaderActions))
            @auth
            <nav class="site-header__nav" aria-label="主ナビゲーション">
                <a href="{{ route('attendance.index') }}"
                    class="site-header__navLink {{ request()->routeIs('attendance.index') ? 'is-current' : '' }}">勤怠</a>
                <a href="{{ route('attendance.list') }}"
                    class="site-header__navLink {{ request()->routeIs('attendance.list') ? 'is-current' : '' }}">勤怠一覧</a>
                <a href="{{ route('requests.index') }}"
                    class="site-header__navLink {{ request()->routeIs('requests.*') ? 'is-current' : '' }}">申請</a>
                <form method="POST" action="{{ route('logout') }}" class="site-header__logoutForm">
                    @csrf
                    <button type="submit" class="site-header__navLink site-header__logoutBtn">ログアウト</button>
                </form>
            </nav>
            @endauth
            @endif
        </div>
    </header>

    @yield('content')
</body>

</html>