@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/attendance-list.css') }}">
<style>
    /* ページ専用上書き */
    body {
        background: #f3f3f6;
    }

    .main.list-main {
        background: #f3f3f6;
    }

    .nav-ym-wrap {
        position: relative;
        font-family: Inter, system-ui, -apple-system, "Hiragino Kaku Gothic ProN", "Yu Gothic UI", Meiryo, sans-serif;
        font-weight: 700;
        font-size: 20px;
        line-height: 1;
    }

    .nav-ym-wrap .nav-ym {
        display: inline-block;
    }

    .nav-ym-wrap input[type="date"] {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        border: none;
        background: transparent;
        appearance: none;
        -webkit-appearance: none;
        cursor: pointer;
    }

    .att-table .detail-link {
        color: #000;
    }
</style>
@endpush

@section('title', '勤怠一覧（管理者）')

@php
$headerLogoUrl = route('admin.login'); // ロゴは /admin/login
$isAdminHeader = true; // 管理者ナビを出す
@endphp

@section('content')
@php
use Carbon\Carbon;
$tz = 'Asia/Tokyo';

/** @var \Illuminate\Pagination\LengthAwarePaginator $attendances */
/** @var Carbon $start */

// デフォルト表示日は「今日」。?date=YYYY-MM-DD があればそれを使用
$dateParam = (string) request()->query('date', '');
try { $dateCursor = $dateParam ? Carbon::parse($dateParam, $tz) : Carbon::now($tz); }
catch (\Throwable $e) { $dateCursor = Carbon::now($tz); }

$dateDisp = $dateCursor->format('Y/m/d');
$dateRaw = $dateCursor->format('Y-m-d');
$prevDate = $dateCursor->copy()->subDay()->format('Y-m-d');
$nextDate = $dateCursor->copy()->addDay()->format('Y-m-d');

// 既存の月ベース取得は維持（?month=YYYY-MM を併送）
$monthForQuery = $dateCursor->format('Y-m');
$qParam = isset($q) && trim((string)$q) !== '' ? ['q' => $q] : [];

$toHm = function (?int $sec): string {
$sec = (int)($sec ?? 0);
if ($sec <= 0) return '' ;
    $h=intdiv($sec, 3600);
    $m=intdiv($sec % 3600, 60);
    return sprintf('%d:%02d', $h, $m);
    };
    $toHi=function ($dt, $tzLocal='Asia/Tokyo' ): string {
    if (!$dt) return '' ;
    return Carbon::parse($dt)->setTimezone($tzLocal)->format('H:i');
    };
    @endphp

    <main class="main list-main">
        <div class="list-heading">
            <span class="vbar" aria-hidden="true"></span>
            <h1 class="list-title">{{ $dateCursor->isoFormat('YYYY年M月D日の勤怠') }}</h1>
        </div>

        <div class="list-nav">
            <a class="nav-btn nav-prev"
                href="{{ route('admin.attendance.list', array_merge(['date' => $prevDate, 'month' => Carbon::parse($prevDate, $tz)->format('Y-m')], $qParam)) }}">
                <img class="nav-arrow" src="{{ asset('img.png') }}" alt="" aria-hidden="true">
                <span>前日</span>
            </a>

            <div class="nav-current" aria-label="表示日">
                <span class="ico-calendar" aria-hidden="true"></span>
                <label class="nav-ym-wrap" id="dateWrap">
                    <span class="nav-ym">{{ $dateDisp }}</span>
                    <input type="date" id="dateInput" name="date" value="{{ $dateRaw }}" aria-label="日付を選択">
                </label>
            </div>

            <a class="nav-btn nav-next"
                href="{{ route('admin.attendance.list', array_merge(['date' => $nextDate, 'month' => Carbon::parse($nextDate, $tz)->format('Y-m')], $qParam)) }}">
                <span>翌日</span>
                <img class="nav-arrow rot" src="{{ asset('img.png') }}" alt="" aria-hidden="true">
            </a>
        </div>

        <div class="list-card">
            <table class="att-table" aria-label="勤怠一覧（管理者）">
                <thead>
                    <tr>
                        <th class="col-date">名前</th>
                        <th>出勤</th>
                        <th>退勤</th>
                        <th>休憩</th>
                        <th>合計</th>
                        <th class="col-detail">詳細</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($attendances as $att)
                    <tr>
                        <td class="col-date">{{ optional($att->user)->name }}</td>
                        <td>{{ $toHi($att->clock_in_at, $tz) }}</td>
                        <td>{{ $toHi($att->clock_out_at, $tz) }}</td>
                        <td>{{ $toHm($att->total_break_seconds) }}</td>
                        <td>{{ $toHm($att->work_seconds) }}</td>
                        <td class="col-detail">
                            <a href="{{ route('admin.attendance.show', ['attendance' => $att->id]) }}" class="detail-link">詳細</a>
                        </td>
                    </tr>
                    @empty
                    @endforelse
                </tbody>
            </table>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const wrap = document.getElementById('dateWrap');
            const inp = document.getElementById('dateInput');

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
                const url = new URL("{{ route('admin.attendance.list') }}", window.location.origin);
                url.searchParams.set('date', inp.value);
                url.searchParams.set('month', inp.value.slice(0, 7)); // 既存月ベース取得の維持
                @if(!empty($q))
                url.searchParams.set('q', @json($q));
                @endif
                window.location.href = url.toString();
            });
        });
    </script>
    @endsection