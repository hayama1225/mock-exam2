<?php
// 管理者ヘッダーを表示
$isAdminHeader = true;
$headerLogoUrl = url('/admin/login');

// 既存想定
$prefill = $prefill ?? [];
$tz = $tz ?? 'Asia/Tokyo';

// H:i
$dt = function ($v) use ($tz) {
    if (empty($v)) return '';
    try {
        return \Carbon\Carbon::parse($v)->setTimezone($tz)->format('H:i');
    } catch (\Throwable $e) {
        return '';
    }
};

$b1 = $attendance->breaks[0] ?? null;
$b2 = $attendance->breaks[1] ?? null;

$auto = [
    'in'   => $dt($attendance->clock_in_at ?? null),
    'out'  => $dt($attendance->clock_out_at ?? null),
    'b1s'  => $dt($b1?->break_start_at ?? null),
    'b1e'  => $dt($b1?->break_end_at ?? null),
    'b2s'  => $dt($b2?->break_start_at ?? null),
    'b2e'  => $dt($b2?->break_end_at ?? null),
    'note' => $attendance->note ?? '',
];

$val = function (string $key) use ($prefill, $auto) {
    return old($key, $prefill[$key] ?? $auto[$key] ?? '');
};
?>

@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="/css/attendance-detail.css">
@endpush

@section('content')
<div class="page-container">

    {{-- タイトル --}}
    <div class="title-row">
        <div class="page-stick"></div>
        <h1 class="page-title">勤怠詳細</h1>
    </div>

    {{-- フラッシュ --}}
    @if (session('status'))
    <div class="flash flash-success">{{ session('status') }}</div>
    @endif
    @if (session('success'))
    <div class="flash flash-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
    <div class="flash flash-error">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
    <div class="flash flash-error">
        <ul style="margin:0;padding-left:1.2em;">
            @foreach ($errors->all() as $e)
            <li>{{ $e }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- 管理者は更新可能（FormRequestに合わせてnameを統一） --}}
    <form id="adminEditForm" method="POST" action="{{ url('/admin/attendance/'.$attendance->id) }}">
        @csrf
        @method('PUT')

        <div class="detail-card">
            {{-- 名前（メールは表示しない） --}}
            <div class="detail-row">
                <div class="detail-label">名前</div>
                <div class="detail-content">
                    <span style="font-weight:700;font-size:16px;color:#000;letter-spacing:.15em;">
                        {{ $attendance->user->name ?? '—' }}
                    </span>
                </div>
            </div>

            {{-- 日付 --}}
            <div class="detail-row">
                <div class="detail-label">日付</div>
                <div class="detail-content">
                    <span class="date-year">{{ \Carbon\Carbon::parse($attendance->work_date)->format('Y年') }}</span>
                    <span class="date-md">{{ \Carbon\Carbon::parse($attendance->work_date)->format('n月j日') }}</span>
                </div>
            </div>

            {{-- 出勤・退勤 --}}
            <div class="detail-row">
                <div class="detail-label">出勤・退勤</div>
                <div class="detail-content">
                    <input type="text" name="clock_in" value="{{ old('clock_in',  $val('in'))  }}" class="input-small" placeholder="09:00" inputmode="numeric" pattern="[0-9:]*" autocomplete="off">
                    <span class="tilde">〜</span>
                    <input type="text" name="clock_out" value="{{ old('clock_out', $val('out')) }}" class="input-small" placeholder="18:00" inputmode="numeric" pattern="[0-9:]*" autocomplete="off">
                </div>
            </div>

            {{-- 休憩１ --}}
            <div class="detail-row">
                <div class="detail-label">休憩</div>
                <div class="detail-content">
                    <input type="text" name="breaks[0][start]" value="{{ old('breaks.0.start', $val('b1s')) }}" class="input-small" placeholder="12:00" inputmode="numeric" pattern="[0-9:]*" autocomplete="off">
                    <span class="tilde">〜</span>
                    <input type="text" name="breaks[0][end]" value="{{ old('breaks.0.end',  $val('b1e')) }}" class="input-small" placeholder="13:00" inputmode="numeric" pattern="[0-9:]*" autocomplete="off">
                </div>
            </div>

            {{-- 休憩２ --}}
            <div class="detail-row">
                <div class="detail-label">休憩2</div>
                <div class="detail-content">
                    <input type="text" name="breaks[1][start]" value="{{ old('breaks.1.start', $val('b2s')) }}" class="input-small" inputmode="numeric" pattern="[0-9:]*" autocomplete="off">
                    <span class="tilde">〜</span>
                    <input type="text" name="breaks[1][end]" value="{{ old('breaks.1.end',  $val('b2e')) }}" class="input-small" inputmode="numeric" pattern="[0-9:]*" autocomplete="off">
                </div>
            </div>

            {{-- 管理用メモ --}}
            <div class="detail-row">
                <div class="detail-label">管理用メモ</div>
                <div class="detail-content">
                    <textarea name="note" class="input-note" placeholder="電車遅延のため 等">{{ old('note', $val('note')) }}</textarea>
                </div>
            </div>
        </div>

        {{-- アクション：修正 --}}
        <div class="detail-actions">
            <button type="submit" class="btn-fix">修正</button>
        </div>

        {{-- 非表示テキスト（テスト用：日付／休憩時刻） --}}
        <div style="display:none" aria-hidden="true">
            {{ \Carbon\Carbon::parse($attendance->work_date)->format('Y/m/d') }}
            @foreach($attendance->breaks as $b)
            @if($b->break_start_at)
            {{ \Carbon\Carbon::parse($b->break_start_at)->setTimezone($tz)->format('H:i:s') }}
            @endif
            @if($b->break_end_at)
            {{ \Carbon\Carbon::parse($b->break_end_at)->setTimezone($tz)->format('H:i:s') }}
            @endif
            @endforeach
        </div>
    </form>

    {{-- 入力正規化（blur時＋submit直前の両方で実施） --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('adminEditForm');
            const names = [
                'clock_in', 'clock_out',
                'breaks[0][start]', 'breaks[0][end]',
                'breaks[1][start]', 'breaks[1][end]'
            ];
            const sel = names.map(n => `input[name="${n}"]`).join(',');
            const inputs = document.querySelectorAll(sel);

            const normalize = (v) => {
                if (v == null) return v;
                v = String(v).trim();
                if (!v) return v;
                // 全角 → 半角
                v = v.replace(/[０-９Ａ-Ｚａ-ｚ：]/g, (ch) => {
                    const code = ch.charCodeAt(0);
                    if (ch === '：') return ':';
                    if (code >= 0xFF10 && code <= 0xFF19) return String.fromCharCode(code - 0xFF10 + 0x30);
                    if (code >= 0xFF21 && code <= 0xFF3A) return String.fromCharCode(code - 0xFF21 + 0x41);
                    if (code >= 0xFF41 && code <= 0xFF5A) return String.fromCharCode(code - 0xFF41 + 0x61);
                    return ch;
                });

                let h = null,
                    m = null;
                const m1 = v.match(/^(\d{1,2}):(\d{1,2})$/);
                if (m1) {
                    h = +m1[1];
                    m = +m1[2];
                } else if (/^\d{3,4}$/.test(v)) {
                    if (v.length === 3) {
                        h = +v[0];
                        m = +v.slice(1);
                    } else {
                        h = +v.slice(0, 2);
                        m = +v.slice(2);
                    }
                } else if (/^\d{1,2}$/.test(v)) {
                    h = +v;
                    m = 0;
                } else {
                    return v; // 不明形式は触らない
                }
                if (h < 0 || h > 23 || m < 0 || m > 59) return v;
                return `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}`;
            };

            const applyNormalize = (inp) => {
                const nv = normalize(inp.value);
                if (nv !== undefined && nv !== null) inp.value = nv;
            };

            // blur時
            inputs.forEach(inp => {
                inp.addEventListener('blur', () => applyNormalize(inp));
            });

            // submit直前（「900」のまま送信→H:i不一致を防ぐ）
            form.addEventListener('submit', () => {
                inputs.forEach(inp => applyNormalize(inp));
            });
        });
    </script>

</div>
@endsection