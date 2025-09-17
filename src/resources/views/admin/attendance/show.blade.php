@extends('layouts.app')

@section('title', '勤怠詳細（管理者）')

@php
$fmtTime = function ($dt) {
return $dt ? \Carbon\Carbon::parse($dt)->format('H:i') : '';
};
$fmtDateY = \Carbon\Carbon::parse($attendance->work_date)->format('Y年');
$fmtDateMD = \Carbon\Carbon::parse($attendance->work_date)->format('n月j日');
@endphp

@section('content')
<div class="container" style="max-width:720px;margin:32px auto;">
    <h1 style="font-size:22px;margin-bottom:16px;">勤怠詳細</h1>

    @if (session('status'))
    <div style="background:#eef6ee;border:1px solid #cfe5cf;padding:10px 12px;border-radius:8px;margin-bottom:12px;">
        {{ session('status') }}
    </div>
    @endif

    @if ($errors->any())
    <div style="background:#fff3f3;border:1px solid #f5cccc;padding:10px 12px;border-radius:8px;margin-bottom:12px;">
        <ul style="margin:0;padding-left:18px;">
            @foreach ($errors->all() as $e)
            <li>{{ $e }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div style="display:flex;gap:8px;margin-bottom:12px;">
        @if ($prev)
        <a href="{{ route('admin.attendance.show', $prev) }}" style="text-decoration:none;border:1px solid #ccc;border-radius:6px;padding:6px 10px;">◀ 前日</a>
        @endif
        @if ($next)
        <a href="{{ route('admin.attendance.show', $next) }}" style="text-decoration:none;border:1px solid #ccc;border-radius:6px;padding:6px 10px;">翌日 ▶</a>
        @endif
        <a href="{{ route('admin.attendance.list') }}" style="margin-left:auto;text-decoration:none;border:1px solid #ccc;border-radius:6px;padding:6px 10px;">一覧へ戻る</a>
    </div>

    <form method="POST" action="{{ route('admin.attendance.update', $attendance) }}">
        @csrf
        @method('PUT')

        <table style="width:100%;border-collapse:collapse;border:1px solid #eee;border-radius:8px;overflow:hidden;">
            <tbody>
                <tr>
                    <th style="width:28%;background:#fafafa;padding:12px;border-bottom:1px solid #eee;text-align:left;">名前</th>
                    <td style="padding:12px;border-bottom:1px solid #eee;">{{ $attendance->user->name ?? '-' }}</td>
                </tr>
                <tr>
                    <th style="background:#fafafa;padding:12px;border-bottom:1px solid #eee;text-align:left;">メール</th>
                    <td style="padding:12px;border-bottom:1px solid #eee;">{{ $attendance->user->email ?? '-' }}</td>
                </tr>
                <tr>
                    <th style="background:#fafafa;padding:12px;border-bottom:1px solid #eee;text-align:left;">日付</th>
                    <td style="padding:12px;border-bottom:1px solid #eee;">{{ $fmtDateY }}　<strong>{{ $fmtDateMD }}</strong></td>
                </tr>
                <tr>
                    <th style="background:#fafafa;padding:12px;border-bottom:1px solid #eee;text-align:left;">出勤・退勤</th>
                    <td style="padding:12px;border-bottom:1px solid #eee;display:flex;align-items:center;gap:8px;">
                        <input type="time" name="clock_in" value="{{ old('clock_in', $fmtTime($attendance->clock_in_at)) }}" style="padding:6px 8px;border:1px solid #ccc;border-radius:6px;">
                        <span>〜</span>
                        <input type="time" name="clock_out" value="{{ old('clock_out', $fmtTime($attendance->clock_out_at)) }}" style="padding:6px 8px;border:1px solid #ccc;border-radius:6px;">
                    </td>
                </tr>

                @php
                $breaks = $attendance->breaks->values();
                $b0s = old('breaks.0.start', isset($breaks[0]) ? $fmtTime($breaks[0]->break_start_at) : '');
                $b0e = old('breaks.0.end', isset($breaks[0]) ? $fmtTime($breaks[0]->break_end_at) : '');
                $b1s = old('breaks.1.start', isset($breaks[1]) ? $fmtTime($breaks[1]->break_start_at) : '');
                $b1e = old('breaks.1.end', isset($breaks[1]) ? $fmtTime($breaks[1]->break_end_at) : '');
                @endphp

                <tr>
                    <th style="background:#fafafa;padding:12px;border-bottom:1px solid #eee;text-align:left;">休憩</th>
                    <td style="padding:12px;border-bottom:1px solid #eee;display:flex;align-items:center;gap:8px;">
                        <input type="time" name="breaks[0][start]" value="{{ $b0s }}" style="padding:6px 8px;border:1px solid #ccc;border-radius:6px;">
                        <span>〜</span>
                        <input type="time" name="breaks[0][end]" value="{{ $b0e }}" style="padding:6px 8px;border:1px solid #ccc;border-radius:6px;">
                    </td>
                </tr>
                <tr>
                    <th style="background:#fafafa;padding:12px;border-bottom:1px solid #eee;text-align:left;">休憩2</th>
                    <td style="padding:12px;border-bottom:1px solid #eee;display:flex;align-items:center;gap:8px;">
                        <input type="time" name="breaks[1][start]" value="{{ $b1s }}" style="padding:6px 8px;border:1px solid #ccc;border-radius:6px;">
                        <span>〜</span>
                        <input type="time" name="breaks[1][end]" value="{{ $b1e }}" style="padding:6px 8px;border:1px solid #ccc;border-radius:6px;">
                    </td>
                </tr>
                <tr>
                    <th style="background:#fafafa;padding:12px;border-bottom:1px solid #eee;text-align:left;">備考</th>
                    <td style="padding:12px;border-bottom:1px solid #eee;">
                        <textarea name="note" rows="3" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:6px;">{{ old('note', $attendance->note) }}</textarea>
                    </td>
                </tr>
            </tbody>
        </table>

        <div style="text-align:right;margin-top:16px;">
            <button type="submit" style="padding:10px 16px;border:none;border-radius:8px;background:#000;color:#fff;">修正</button>
        </div>
    </form>
</div>
@endsection