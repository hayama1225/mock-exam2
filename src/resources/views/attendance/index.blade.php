@extends('layouts.app')

@section('title', '勤怠')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endpush

@php
// Controller側: 'not_working' | 'working' | 'on_break' | 'completed'
$rawStatus = $status ?? null;
switch ($rawStatus) {
case 'working': $uiStatus = 'working'; break;
case 'on_break': $uiStatus = 'break'; break;
case 'completed': $uiStatus = 'done'; break;
case 'not_working':
default: $uiStatus = 'off'; break;
}

$displayDate = $displayDate ?? \Carbon\Carbon::now('Asia/Tokyo')->locale('ja')->isoFormat('YYYY年M月D日(dd)');
$displayTime = $displayTime ?? \Carbon\Carbon::now('Asia/Tokyo')->format('H:i');
@endphp

@section('content')
<main class="main attendance-main">
    <section class="attendance">

        @if (session('error'))
        <p class="form-error" style="text-align:center;color:#d12a2a;margin:-8px 0 16px;">
            {{ session('error') }}
        </p>
        @endif

        {{-- ステータスピル --}}
        <div class="status-pill">
            @switch($uiStatus)
            @case('working') 出勤中 @break
            @case('break') 休憩中 @break
            @case('done') 退勤済 @break
            @default 勤務外
            @endswitch
        </div>

        {{-- 日付・時刻 --}}
        <p class="att-date">{{ $displayDate }}</p>
        <p class="att-time">{{ $displayTime }}</p>

        {{-- アクション（POST先は /attendance 固定、Controller期待値で送信） --}}
        @if ($uiStatus === 'off')
        <form method="POST" action="{{ route('attendance.store') }}" class="action-single">
            @csrf
            <button type="submit" class="btn btn-solid" name="action" value="clock_in">出勤</button>
        </form>

        @elseif ($uiStatus === 'working')
        <div class="action-row">
            <form method="POST" action="{{ route('attendance.store') }}">
                @csrf
                <button type="submit" class="btn btn-solid" name="action" value="clock_out">退勤</button>
            </form>
            <form method="POST" action="{{ route('attendance.store') }}">
                @csrf
                <button type="submit" class="btn btn-outline" name="action" value="start_break">休憩入</button>
            </form>
        </div>

        @elseif ($uiStatus === 'break')
        <form method="POST" action="{{ route('attendance.store') }}" class="action-single">
            @csrf
            <button type="submit" class="btn btn-outline" name="action" value="end_break">休憩戻</button>
        </form>

        @elseif ($uiStatus === 'done')
        <p class="thanks-message">お疲れ様でした。</p>
        @endif

    </section>
</main>
@endsection