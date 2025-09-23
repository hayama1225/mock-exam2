@extends('layouts.app')

@section('title', '月次勤怠一覧（スタッフ別）')

@section('content')
<div class="container" style="max-width:1100px;">
    <h1 class="mb-3" style="font-weight:700;">月次勤怠一覧（スタッフ別）</h1>

    <div class="mb-3">
        <div>スタッフ名：<strong>{{ $staff->name }}</strong></div>
        <div>メール　　：{{ $staff->email }}</div>
    </div>

    @php
    $summary = $summary ?? ['worked_days' => 0, 'break_hm' => '0:00', 'worked_hm' => '0:00'];
    @endphp

    {{-- サマリー: 出勤日数 / 総休憩 / 総実働 --}}
    <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge text-bg-secondary p-2">出勤日数：{{ $summary['worked_days'] }} 日</span>
        <span class="badge text-bg-secondary p-2">総休憩：{{ $summary['break_hm'] }}</span>
        <span class="badge text-bg-secondary p-2">総実働：{{ $summary['worked_hm'] }}</span>
    </div>

    {{-- 月移動（FN044）＋ 月ジャンプ --}}
    <div class="d-flex align-items-center gap-2 mb-3">
        <a class="btn btn-outline-secondary btn-sm"
            href="{{ route('admin.attendance.staff', ['user' => $staff->id, 'month' => $prevMonth]) }}">前月</a>

        <div class="px-2" style="font-weight:600; min-width:7rem; text-align:center;">
            {{ $start->format('Y-m') }}
        </div>

        <a class="btn btn-outline-secondary btn-sm"
            href="{{ route('admin.attendance.staff', ['user' => $staff->id, 'month' => $nextMonth]) }}">翌月</a>

        {{-- 月ジャンプ --}}
        <form class="ms-3 d-flex align-items-center" method="get" action="{{ route('admin.attendance.staff', ['user' => $staff->id]) }}">
            <input type="month" name="month" value="{{ $start->format('Y-m') }}" class="form-control form-control-sm" style="width: 145px;">
            <button class="btn btn-sm btn-outline-secondary ms-2">移動</button>
        </form>

        {{-- CSV（FN045） --}}
        <a class="btn btn-outline-dark btn-sm ms-auto"
            href="{{ route('admin.attendance.staff.csv', ['user' => $staff->id, 'month' => $start->format('Y-m')]) }}">
            CSV出力
        </a>
    </div>

    {{-- 一覧（FN043） --}}
    <div class="table-responsive" style="max-height: 70vh;">
        <table class="table table-striped align-middle">
            <thead class="table-light" style="position: sticky; top: 0; z-index: 1;">
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
                @php $hasAny = false; @endphp
                @foreach($days as $row)
                @php
                $a = $row['attendance'];
                $dateStr = $row['date']->toDateString();
                $hasAny = $hasAny || !is_null($a);
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

                {{-- 空状態 --}}
                @if(!$hasAny)
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        この月の勤怠は登録がありません。
                    </td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>

    <a href="{{ route('admin.staff.list') }}" class="btn btn-outline-dark mt-2">スタッフ一覧へ戻る</a>
</div>
@endsection