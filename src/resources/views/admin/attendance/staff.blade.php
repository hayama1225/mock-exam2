@extends('layouts.app')

@section('title', '月次勤怠一覧（スタッフ別）')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/attendance-list.css') }}">
@endpush

@section('content')
@php
$monthRaw = isset($month) ? $month : \Carbon\Carbon::now('Asia/Tokyo')->format('Y-m');
$monthDisp = str_replace('-', '/', $monthRaw);

$toHm = function (?int $sec): string {
$sec = (int)($sec ?? 0);
if ($sec <= 0) return '' ;
    $h=intdiv($sec, 3600);
    $m=intdiv($sec % 3600, 60);
    return sprintf('%d:%02d', $h, $m);
    };
    $toHi=function ($dt, $tz='Asia/Tokyo' ): string {
    if (!$dt) return '' ;
    return \Carbon\Carbon::parse($dt)->setTimezone($tz)->format('H:i');
    };
    @endphp

    <main class="main list-main admin-staff">
        {{-- タイトル --}}
        <div class="list-heading">
            <span class="vbar" aria-hidden="true"></span>
            <h1 class="list-title">{{ $staff->name }}の勤怠</h1>
        </div>

        {{-- ★テスト用：氏名・メールの表示（デザイン簡素／900px幅に合わせる） --}}
        <div style="width:900px;margin:0 auto 8px;color:#737373;font-size:14px;">
            メール：<span>{{ $staff->email }}</span>
        </div>

        {{-- 前月／monthピッカー／翌月 --}}
        <div class="list-nav">
            <a class="nav-btn nav-prev"
                href="{{ route('admin.attendance.staff', ['user' => $staff->id, 'month' => $prevMonth]) }}">
                <img class="nav-arrow" src="{{ asset('img.png') }}" alt="" aria-hidden="true">
                <span>前月</span>
            </a>

            <div class="nav-current" aria-label="表示月">
                <span class="ico-calendar" aria-hidden="true"></span>
                <label class="nav-ym-wrap" id="ymWrap">
                    <span class="nav-ym">{{ $monthDisp }}</span>
                    <input type="month" id="ymInput" name="month" value="{{ $monthRaw }}" aria-label="月を選択">
                </label>
            </div>

            <a class="nav-btn nav-next"
                href="{{ route('admin.attendance.staff', ['user' => $staff->id, 'month' => $nextMonth]) }}">
                <span>翌月</span>
                <img class="nav-arrow rot" src="{{ asset('img.png') }}" alt="" aria-hidden="true">
            </a>
        </div>

        {{-- 一覧カード --}}
        <div class="list-card">
            <table class="att-table" aria-label="勤怠一覧（スタッフ別）">
                <thead>
                    <tr>
                        <th class="col-date">日付</th>
                        <th>出勤</th>
                        <th>退勤</th>
                        <th>休憩</th>
                        <th>合計</th>
                        <th class="col-detail">詳細</th>
                    </tr>
                </thead>
                <tbody>
                    @php $hasAny = false; @endphp
                    @foreach ($days as $row)
                    @php
                    /** @var \Carbon\Carbon $date */
                    $date = $row['date'];
                    $att = $row['attendance'] ?? null;
                    $hasAny = $hasAny || !is_null($att);

                    $in = $att ? $toHi($att->clock_in_at) : '';
                    $out = $att ? $toHi($att->clock_out_at) : '';
                    $brk = $att ? $toHm($att->total_break_seconds) : '';
                    $work = $att ? $toHm($att->work_seconds) : '';
                    @endphp
                    <tr>
                        <td class="col-date">{{ $date->locale('ja')->isoFormat('MM/DD(dd)') }}</td>
                        <td>{{ $in }}</td>
                        <td>{{ $out }}</td>
                        <td>{{ $brk }}</td>
                        <td>{{ $work }}</td>
                        <td class="col-detail">
                            @if ($att)
                            <a href="{{ route('admin.attendance.show', ['attendance' => $att->id]) }}" class="detail-link">詳細</a>
                            @else
                            <span class="detail-link disabled">詳細</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach

                    @if (!$hasAny)
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">この月の勤怠は登録がありません。</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>

        {{-- テーブル枠外・右下（att-table の右端と揃う） --}}
        <div id="staff-csv" style="width:900px;margin:10px auto 0;text-align:right;">
            <form method="get"
                action="{{ route('admin.attendance.staff.csv', ['user' => $staff->id, 'month' => $monthRaw]) }}"
                style="display:inline;width:auto;margin:0;">
                <button type="submit" class="btn-csv"
                    style="background:#000;color:#fff;border:1px solid #000;border-radius:8px;padding:10px 18px;cursor:pointer;">
                    CSV出力
                </button>
            </form>
        </div>
    </main>

    {{-- 月変更 --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const wrap = document.getElementById('ymWrap');
            const inp = document.getElementById('ymInput');

            wrap.addEventListener('click', function() {
                if (typeof inp.showPicker === 'function') {
                    try {
                        inp.showPicker();
                        return;
                    } catch (_) {}
                }
                inp.click();
                inp.focus();
            });

            inp.addEventListener('change', function() {
                if (!inp.value) return;
                const url = new URL("{{ route('admin.attendance.staff', ['user' => $staff->id]) }}", window.location.origin);
                url.searchParams.set('month', inp.value);
                window.location.href = url.toString();
            });
        });
    </script>
    @endsection