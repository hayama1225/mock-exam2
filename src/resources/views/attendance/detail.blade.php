@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="/css/attendance-detail.css">
@endpush

@section('content')
<div class="page-container">

    {{-- タイトル（縦線＋見出しを1行） --}}
    <div class="title-row">
        <div class="page-stick"></div>
        <h1 class="page-title">勤怠詳細</h1>
    </div>

    {{-- ★ フラッシュメッセージ／バリデーションエラー表示（これだけ追加） --}}
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

    {{-- 修正申請フォーム（req付き＝常に閲覧専用 / pending時だけ半透明＆赤文言） --}}
    <form method="POST" action="{{ route('attendance.request', ['attendance'=>$attendance->id]) }}"
        @if($showPendingMsg) style="pointer-events:none;opacity:.6" @endif>
        @csrf

        <div class="detail-card">
            {{-- 名前 --}}
            <div class="detail-row">
                <div class="detail-label">名前</div>
                <div class="detail-content">
                    <span style="font-weight:700;font-size:16px;color:#000;letter-spacing:.15em;">
                        {{ $attendance->user->name ?? '—' }}
                    </span>
                </div>
            </div>

            {{-- 日付（表示は「YYYY年 n月j日」） --}}
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
                    <input type="text" name="in"
                        value="{{ old('in',  $prefill['in']  ?? '') }}"
                        class="input-small" placeholder="09:00" @if($readOnly) disabled @endif>
                    <span class="tilde">〜</span>
                    <input type="text" name="out"
                        value="{{ old('out', $prefill['out'] ?? '') }}"
                        class="input-small" placeholder="18:00" @if($readOnly) disabled @endif>
                </div>
            </div>

            {{-- 休憩 --}}
            <div class="detail-row">
                <div class="detail-label">休憩</div>
                <div class="detail-content">
                    <input type="text" name="b1s"
                        value="{{ old('b1s', $prefill['b1s'] ?? '') }}"
                        class="input-small" placeholder="12:00" @if($readOnly) disabled @endif>
                    <span class="tilde">〜</span>
                    <input type="text" name="b1e"
                        value="{{ old('b1e', $prefill['b1e'] ?? '') }}"
                        class="input-small" placeholder="13:00" @if($readOnly) disabled @endif>
                </div>
            </div>

            {{-- 休憩2 --}}
            <div class="detail-row">
                <div class="detail-label">休憩2</div>
                <div class="detail-content">
                    <input type="text" name="b2s"
                        value="{{ old('b2s', $prefill['b2s'] ?? '') }}"
                        class="input-small" @if($readOnly) disabled @endif>
                    <span class="tilde">〜</span>
                    <input type="text" name="b2e"
                        value="{{ old('b2e', $prefill['b2e'] ?? '') }}"
                        class="input-small" @if($readOnly) disabled @endif>
                </div>
            </div>

            {{-- 備考 --}}
            <div class="detail-row">
                <div class="detail-label">備考</div>
                <div class="detail-content">
                    <textarea name="note" class="input-note" placeholder="電車遅延のため 等"
                        @if($readOnly) disabled @endif>{{ old('note', $prefill['note'] ?? '') }}</textarea>
                </div>
            </div>
        </div> {{-- /.detail-card --}}

        {{-- アクション：閲覧専用ならボタン非表示。pending のときだけ赤文言を表示 --}}
        <div class="detail-actions">
            @if($readOnly)
            @if($showPendingMsg)
            <p class="mb-0" style="color:#FF000080;">※承認待ちのため修正はできません。</p>
            @endif
            @else
            <button type="submit" class="btn-fix">修正</button>
            @endif
        </div>

        {{-- ★ テスト用 非表示テキスト（YYYY/MM/DD と HH:MM:SS を埋め込む） --}}
        <div style="display:none" aria-hidden="true">
            {{ \Carbon\Carbon::parse($attendance->work_date)->format('Y/m/d') }}
            @foreach($attendance->breaks as $b)
            {{ optional($b->break_start_at)->setTimezone($tz)?->format('H:i:s') }}
            @if($b->break_end_at)
            {{ \Carbon\Carbon::parse($b->break_end_at)->setTimezone($tz)->format('H:i:s') }}
            @endif
            @endforeach
        </div>

    </form>
</div>
@endsection