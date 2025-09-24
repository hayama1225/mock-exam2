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
            <a href="{{ $headerLogoUrl ?? url('/') }}" class="site-header__logoLink" aria-label="Home">
                <img src="{{ asset('logo.svg') }}" alt="COACHTECH" class="site-header__logo">
            </a>

            {{-- 右側アクション（ログイン/登録/メール認証誘導では非表示にする） --}}
            @if (empty($hideHeaderActions))
            @php
            // ★ 管理者ナビは admin配下 or 明示フラグのときだけ表示（adminガードのセッション有無では判定しない）
            $onAdmin = !empty($isAdminHeader) || request()->routeIs('admin.*') || request()->is('admin/*');
            @endphp

            @if ($onAdmin)
            {{-- 管理者ナビ：勤怠一覧 / スタッフ一覧 / 申請一覧 / ログアウト --}}
            <nav class="site-header__nav" aria-label="管理者ナビゲーション">
                <a href="{{ route('admin.attendance.list') }}"
                    class="site-header__navLink {{ request()->routeIs('admin.attendance.list') ? 'is-current' : '' }}">勤怠一覧</a>
                <a href="{{ route('admin.staff.list') }}"
                    class="site-header__navLink {{ request()->routeIs('admin.staff.list') ? 'is-current' : '' }}">スタッフ一覧</a>
                <a href="{{ route('admin.corrections.list') }}"
                    class="site-header__navLink {{ request()->routeIs('admin.corrections.*') ? 'is-current' : '' }}">申請一覧</a>
                <form method="POST" action="{{ route('admin.logout') }}" class="site-header__logoutForm">
                    @csrf
                    <button type="submit" class="site-header__navLink site-header__logoutBtn">ログアウト</button>
                </form>
            </nav>
            @elseif (Illuminate\Support\Facades\Auth::check())
            {{-- 一般ユーザーナビ --}}
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
            @endif
            @endif
        </div>
    </header>

    @yield('content')
</body>

</html>