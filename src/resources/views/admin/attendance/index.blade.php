@extends('layouts.app')

@section('title', '管理者：勤怠一覧')

@php
// 秒 → H:MM 変換の軽ヘルパ
$toHm = function (?int $sec) {
if ($sec === null) return '-';
$h = intdiv($sec, 3600);
$m = intdiv($sec % 3600, 60);
return sprintf('%d:%02d', $h, $m);
};
@endphp

@section('content')
<div class="container" style="max-width:1100px;margin:32px auto;">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:16px;">
        <h1 style="font-size:22px;">管理者：勤怠一覧（{{ $start->format('Y年n月') }}）</h1>
        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit" style="padding:8px 12px;border:none;border-radius:6px;background:#000;color:#fff;">ログアウト</button>
        </form>
    </div>

    <div style="display:flex;gap:12px;align-items:center;margin-bottom:16px;">
        <a href="{{ route('admin.attendance.list', ['month' => $prevMonth, 'q' => $q]) }}" style="text-decoration:none;border:1px solid #ccc;border-radius:6px;padding:6px 10px;">◀ 前月</a>
        <a href="{{ route('admin.attendance.list', ['month' => $nextMonth, 'q' => $q]) }}" style="text-decoration:none;border:1px solid #ccc;border-radius:6px;padding:6px 10px;">翌月 ▶</a>

        <form method="GET" action="{{ route('admin.attendance.list') }}" style="margin-left:auto;display:flex;gap:8px;align-items:center;">
            <input type="month" name="month" value="{{ request('month', $start->format('Y-m')) }}"
                style="padding:6px 8px;border:1px solid #ccc;border-radius:6px;">
            <input type="text" name="q" value="{{ $q }}" placeholder="氏名 / メールで検索"
                style="padding:6px 8px;border:1px solid #ccc;border-radius:6px;min-width:220px;">
            <button type="submit" style="padding:6px 12px;border:none;border-radius:6px;background:#000;color:#fff;">検索</button>
        </form>
    </div>

    <div style="overflow:auto;border:1px solid #eee;border-radius:8px;">
        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr style="background:#fafafa;">
                    <th style="text-align:left;padding:10px;border-bottom:1px solid #eee;">日付</th>
                    <th style="text-align:left;padding:10px;border-bottom:1px solid #eee;">ユーザー</th>
                    <th style="text-align:left;padding:10px;border-bottom:1px solid #eee;">メール</th>
                    <th style="text-align:left;padding:10px;border-bottom:1px solid #eee;">出勤</th>
                    <th style="text-align:left;padding:10px;border-bottom:1px solid #eee;">退勤</th>
                    <th style="text-align:left;padding:10px;border-bottom:1px solid #eee;">休憩合計</th>
                    <th style="text-align:left;padding:10px;border-bottom:1px solid #eee;">勤務時間</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($attendances as $a)
                <tr>
                    <td style="padding:10px;border-bottom:1px solid #f2f2f2;">
                        <a href="{{ route('admin.attendance.show', $a) }}" style="text-decoration:underline;">
                            {{ \Carbon\Carbon::parse($a->work_date)->format('Y/m/d') }}
                        </a>
                    </td>
                    <td style="padding:10px;border-bottom:1px solid #f2f2f2;">{{ optional($a->user)->name ?? '-' }}</td>
                    <td style="padding:10px;border-bottom:1px solid #f2f2f2;">{{ optional($a->user)->email ?? '-' }}</td>
                    <td style="padding:10px;border-bottom:1px solid #f2f2f2;">{{ $a->clock_in_at ? \Carbon\Carbon::parse($a->clock_in_at)->format('H:i') : '-' }}</td>
                    <td style="padding:10px;border-bottom:1px solid #f2f2f2;">{{ $a->clock_out_at ? \Carbon\Carbon::parse($a->clock_out_at)->format('H:i') : '-' }}</td>
                    <td style="padding:10px;border-bottom:1px solid #f2f2f2;">{{ $toHm($a->total_break_seconds) }}</td>
                    <td style="padding:10px;border-bottom:1px solid #f2f2f2;">{{ $toHm($a->work_seconds) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="padding:16px;text-align:center;">該当データがありません</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:16px;">
        {{ $attendances->links() }}
    </div>
</div>
@endsection