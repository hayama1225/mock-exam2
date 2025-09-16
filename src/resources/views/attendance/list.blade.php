@extends('layouts.app')

@section('content')
<div class="container" style="max-width:1000px;">
    <h1 class="mb-4">勤怠一覧</h1>

    <div class="d-flex align-items-center gap-3 mb-3">
        <a class="btn btn-outline-secondary" href="{{ route('attendance.list', ['ym' => $prevYm]) }}">« 前月</a>
        <div class="h5 mb-0">
            {{ \Carbon\Carbon::createFromFormat('Y-m-d', $ym.'-01', $tz)->format('Y/m') }}
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('attendance.list', ['ym' => $nextYm]) }}">翌月 »</a>
        <div class="ms-auto">
            <a class="btn btn-outline-primary" href="{{ route('attendance.index') }}">打刻画面へ</a>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width:140px;">日付</th>
                        <th style="width:140px;">出勤</th>
                        <th style="width:140px;">退勤</th>
                        <th style="width:140px;">休憩</th>
                        <th style="width:140px;">合計</th>
                        <th style="width:100px;">詳細</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($days as $row)
                    @php
                    $a = $row['attendance'] ?? null;
                    $date = $row['date'];
                    @endphp
                    <tr>
                        <td>{{ $date->format('m/d(D)') }}</td>
                        <td>
                            @if($a && $a->clock_in_at)
                            {{ \Carbon\Carbon::parse($a->clock_in_at)->setTimezone($tz)->format('H:i') }}
                            @endif
                        </td>
                        <td>
                            @if($a && $a->clock_out_at)
                            {{ \Carbon\Carbon::parse($a->clock_out_at)->setTimezone($tz)->format('H:i') }}
                            @endif
                        </td>
                        <td>
                            @if($a)
                            {{-- total_break_seconds を H:MM 表示 --}}
                            {{ sprintf('%d:%02d', intdiv((int)$a->total_break_seconds,3600), intdiv(((int)$a->total_break_seconds)%3600,60)) }}
                            @endif
                        </td>
                        <td>
                            @if($a)
                            {{-- work_seconds を H:MM 表示 --}}
                            {{ sprintf('%d:%02d', intdiv((int)$a->work_seconds,3600), intdiv(((int)$a->work_seconds)%3600,60)) }}
                            @endif
                        </td>
                        <td>
                            @if($a)
                            <a href="{{ route('attendance.detail', ['attendance' => $a->id]) }}" class="btn btn-link btn-sm p-0">詳細</a>
                            @else
                            <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection