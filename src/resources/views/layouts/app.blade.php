<!doctype html>
<html lang="ja">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>@yield('title','COACHTECH')</title>
</head>

<body>
    @yield('content')
    <a href="{{ route('requests.index') }}">申請</a>

    @auth
    <form method="POST" action="{{ route('logout') }}" style="display:inline">
        @csrf
        <button type="submit">ログアウト</button>
    </form>
    @endauth

</body>

</html>