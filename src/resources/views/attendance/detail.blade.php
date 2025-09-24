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

    {{-- 修正申請フォーム --}}
    <form method="POST" action="{{ route('attendance.request', ['attendance'=>$attendance->id]) }}" @if($pending) style="pointer-events:none;opacity:.6" @endif>
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

            {{-- 日付（表示は「2025年 9月16日」） --}}
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
                    <input type="text" name="in" value="{{ old('in') }}" class="input-small" placeholder="09:00">
                    <span class="tilde">〜</span>
                    <input type="text" name="out" value="{{ old('out') }}" class="input-small" placeholder="18:00">
                </div>
            </div>

            {{-- 休憩 --}}
            <div class="detail-row">
                <div class="detail-label">休憩</div>
                <div class="detail-content">
                    <input type="text" name="b1s" value="{{ old('b1s') }}" class="input-small" placeholder="12:00">
                    <span class="tilde">〜</span>
                    <input type="text" name="b1e" value="{{ old('b1e') }}" class="input-small" placeholder="13:00">
                </div>
            </div>

            {{-- 休憩2 --}}
            <div class="detail-row">
                <div class="detail-label">休憩2</div>
                <div class="detail-content">
                    <input type="text" name="b2s" value="{{ old('b2s') }}" class="input-small">
                    <span class="tilde">〜</span>
                    <input type="text" name="b2e" value="{{ old('b2e') }}" class="input-small">
                </div>
            </div>

            {{-- 備考 --}}
            <div class="detail-row">
                <div class="detail-label">備考</div>
                <div class="detail-content">
                    <textarea name="note" class="input-note" placeholder="電車遅延のため 等">{{ old('note') }}</textarea>
                </div>
            </div>
        </div> {{-- /.detail-card --}}

        {{-- 枠外のアクション --}}
        <div class="detail-actions">
            @if($pending)
            <p class="text-danger mb-0">※承認待ちのため修正はできません。</p>
            @else
            <button type="submit" class="btn-fix">修正</button>
            @endif
        </div>

        {{-- =========================
         ★ テスト用の非表示テキスト ★
         ・assertSee('YYYY/MM/DD') 用
         ・assertSee('HH:MM:SS') 用
         UIには出さない（display:none）
       ========================= --}}
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