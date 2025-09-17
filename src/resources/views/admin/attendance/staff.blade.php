@extends('layouts.app')

@section('title', '月次勤怠一覧（スタッフ別）')

@section('content')
<div class="container" style="max-width:1100px;">
    <h1 class="mb-3" style="font-weight:700;">月次勤怠一覧（スタッフ別）</h1>

    <div class="mb-3">
        <div>スタッフ名：<strong>{{ $staff->name }}</strong></div>
        <div>メール　　：{{ $staff->email }}</div>
    </div>

    {{-- 月移動（FN044） --}}
    <div class="d-flex align-items-center gap-2 mb-3">
        <a class="btn btn-outline-secondary btn-sm"
            href="{{ route('admin.attendance.staff', ['user' => $staff->id, 'month' => $prevMonth]) }}">前月</a>

        <div class="px-2" style="font-weight:600;">{{ $start->format('Y-m') }}</div>

        <a class="btn btn-outline-secondary btn-sm"
            href="{{ route('admin.attendance.staff', ['user' => $staff->id, 'month' => $nextMonth]) }}">翌月</a>

        {{-- CSV（FN045） --}}
        <a class="btn btn-outline-dark btn-sm ms-auto"
            href="{{ route('admin.attendance.staff.csv', ['user' => $staff->id, 'month' => $start->format('Y-m')]) }}">
            CSV出力
        </a>
    </div>

    {{-- 一覧（FN043） --}}
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th style="width:18%;">日付</th>
                    <th style="width:14%;">出勤</th>
                    <th style="width:14%;">退勤</th>
                    <th style="width:14%;">休憩(H:MM)</th>
                    <th style="width:14%;">実働(H:MM)</th>
                    <th style="width:12%;">詳細</th>
                </tr>
            </thead>
            <tbody>
                @foreach($days as $row)
                @php
                $a = $row['attendance'];
                $dateStr = $row['date']->toDateString();
                @endphp
                <tr>
                    <td>{{ $dateStr }}</td>
                    <td>{{ optional($a?->clock_in_at)->format('H:i') }}</td>
                    <td>{{ optional($a?->clock_out_at)->format('H:i') }}</td>
                    <td>
                        @if($a && !is_null($a->total_break_seconds))
                        {{ sprintf('%d:%02d', intdiv($a->total_break_seconds,3600), intdiv($a->total_break_seconds%3600,60)) }}
                        @endif
                    </td>
                    <td>
                        @if($a && !is_null($a->work_seconds))
                        {{ sprintf('%d:%02d', intdiv($a->work_seconds,3600), intdiv($a->work_seconds%3600,60)) }}
                        @endif
                    </td>
                    <td>
                        @if($a)
                        <a class="btn btn-sm btn-outline-primary"
                            href="{{ route('admin.attendance.show', ['attendance' => $a->id]) }}">
                            詳細
                        </a>
                        @else
                        <span class="text-muted">-</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <a href="{{ route('admin.staff.list') }}" class="btn btn-outline-dark mt-2">スタッフ一覧へ戻る</a>
</div>
@endsection