@extends('layouts.app')

@section('content')
<div class="container" style="max-width:760px;">
    <h1 class="mb-4">勤怠打刻</h1>

    <div class="mb-3">
        <a href="{{ route('attendance.list') }}" class="btn btn-outline-secondary btn-sm">勤怠一覧</a>
    </div>

    @if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <p class="mb-1">本日: <strong>{{ \Carbon\Carbon::now($tz)->toDateString() }}</strong></p>
            <p class="mb-0">状態:
                @switch($status)
                @case('not_working') <span class="badge bg-secondary">勤務外</span> @break
                @case('working') <span class="badge bg-primary">出勤中</span> @break
                @case('on_break') <span class="badge bg-warning text-dark">休憩中</span> @break
                @case('completed') <span class="badge bg-success">退勤済</span> @break
                @endswitch
            </p>
        </div>
    </div>

    <form method="POST" action="{{ route('attendance.store') }}" novalidate class="mb-3">
        @csrf
        <div class="d-grid gap-2">
            @if($flags['can_clock_in'])
            <button name="action" value="clock_in" class="btn btn-primary btn-lg">出勤</button>
            @endif
            @if($flags['can_start_break'])
            <button name="action" value="start_break" class="btn btn-warning btn-lg">休憩開始</button>
            @endif
            @if($flags['can_end_break'])
            <button name="action" value="end_break" class="btn btn-warning btn-lg">休憩終了</button>
            @endif
            @if($flags['can_clock_out'])
            <button name="action" value="clock_out" class="btn btn-success btn-lg">退勤</button>
            @endif
        </div>
    </form>

    <div class="card">
        <div class="card-header">本日の打刻</div>
        <div class="card-body">
            @if($attendance)
            <ul class="list-unstyled mb-0">
                <li>出勤: {{ optional($attendance->clock_in_at)->setTimezone($tz) }}</li>
                <li>退勤: {{ optional($attendance->clock_out_at)->setTimezone($tz) }}</li>
                <li>休憩:
                    <ul class="mb-0">
                        @forelse($attendance->breaks as $b)
                        <li>
                            {{ optional($b->break_start_at)->setTimezone($tz) }}
                            〜
                            {{ $b->break_end_at ? \Carbon\Carbon::parse($b->break_end_at)->setTimezone($tz) : '（進行中）' }}
                        </li>
                        @empty
                        <li class="text-muted">休憩打刻はありません。</li>
                        @endforelse
                    </ul>
                </li>
            </ul>
            @else
            <p class="mb-0 text-muted">本日の打刻がまだありません。</p>
            @endif
        </div>
    </div>
</div>
@endsection