@extends('layouts.app')

@section('title', '勤怠一覧')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/attendance-list.css') }}">
@endpush

@section('content')
@php
// 表示用：YYYY/MM と、フォーム用：YYYY-MM
$ymRaw = isset($ym) ? $ym : \Carbon\Carbon::now('Asia/Tokyo')->format('Y-m');
$ymDisp = str_replace('-', '/', $ymRaw);

// 秒→H:MM
$toHm = function (?int $sec): string {
$sec = (int)($sec ?? 0);
if ($sec <= 0) return '' ;
    $h=intdiv($sec, 3600);
    $m=intdiv($sec % 3600, 60);
    return sprintf('%d:%02d', $h, $m);
    };
    // dt→H:i
    $toHi=function ($dt, $tz='Asia/Tokyo' ): string {
    if (!$dt) return '' ;
    return \Carbon\Carbon::parse($dt)->setTimezone($tz)->format('H:i');
    };
    @endphp

    <main class="main list-main">
        {{-- タイトル行（vbar＋タイトルを1行で。900px左端に揃え） --}}
        <div class="list-heading">
            <span class="vbar" aria-hidden="true"></span>
            <h1 class="list-title">勤怠一覧</h1>
        </div>

        {{-- ページネーション＋カレンダー枠 --}}
        <div class="list-nav">
            <a class="nav-btn nav-prev" href="{{ route('attendance.list', ['ym' => $prevYm]) }}">
                <img class="nav-arrow" src="{{ asset('img.png') }}" alt="" aria-hidden="true">
                <span>前月</span>
            </a>

            {{-- 中央：テキスト上に透明の month 入力を重ね、JSで showPicker() も叩く --}}
            <div class="nav-current" aria-label="表示月">
                <span class="ico-calendar" aria-hidden="true"></span>
                <label class="nav-ym-wrap" id="ymWrap">
                    <span class="nav-ym">{{ $ymDisp }}</span>
                    <input type="month" id="ymInput" name="ym" value="{{ $ymRaw }}" aria-label="月を選択">
                </label>
            </div>

            <a class="nav-btn nav-next" href="{{ route('attendance.list', ['ym' => $nextYm]) }}">
                <span>翌月</span>
                <img class="nav-arrow rot" src="{{ asset('img.png') }}" alt="" aria-hidden="true">
            </a>
        </div>

        {{-- 一覧カード --}}
        <div class="list-card">
            <table class="att-table" aria-label="勤怠一覧">
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
                    @foreach ($days as $row)
                    @php
                    /** @var \Carbon\Carbon $date */
                    $date = $row['date'];
                    $att = $row['attendance'] ?? null;

                    $in = $att ? $toHi($att->clock_in_at, $tz) : '';
                    $out = $att ? $toHi($att->clock_out_at, $tz) : '';
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
                            <a href="{{ route('attendance.detail', ['attendance' => $att->id]) }}" class="detail-link">詳細</a>
                            @else
                            <span class="detail-link disabled">詳細</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </main>

    {{-- JS：クリック→showPicker / 変更→遷移 --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const wrap = document.getElementById('ymWrap');
            const inp = document.getElementById('ymInput');

            // ラベルクリックでも month ピッカーを開く
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

            // 月が変わったら /attendance/list?ym=YYYY-MM へ
            inp.addEventListener('change', function() {
                if (!inp.value) return;
                const url = new URL("{{ route('attendance.list') }}", window.location.origin);
                url.searchParams.set('ym', inp.value);
                window.location.href = url.toString();
            });
        });
    </script>
    @endsection