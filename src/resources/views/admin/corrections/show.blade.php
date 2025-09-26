@extends('layouts.app')

@section('title', '勤怠詳細')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/attendance-detail.css') }}">
@endpush

@section('content')
@php
// 管理者ヘッダーを有効化（既存の4ボタンナビ）
$isAdminHeader = true;
$headerLogoUrl = url('/admin/login');
@endphp

<div class="page-container">
    {{-- タイトル --}}
    <div class="title-row">
        <div class="page-stick"></div>
        <h1 class="page-title">勤怠詳細</h1>
    </div>

    {{-- フラッシュ（一覧から戻ったとき用） --}}
    @if (session('status'))
    <div class="flash flash-success">{{ session('status') }}</div>
    @endif

    {{-- 詳細カード（閲覧専用） --}}
    <div class="detail-card">
        {{-- 状態 --}}
        <div class="detail-row">
            <div class="detail-label">状態</div>
            <div class="detail-content">
                <span id="statusText">{{ $correction->status === 'approved' ? '承認済み' : '承認待ち' }}</span>
            </div>
        </div>

        {{-- 名前 --}}
        <div class="detail-row">
            <div class="detail-label">名前</div>
            <div class="detail-content">
                <span style="font-weight:700;font-size:16px;color:#000;letter-spacing:.15em;">
                    {{ $correction->attendance->user->name ?? '—' }}
                </span>
            </div>
        </div>

        {{-- 日付 --}}
        <div class="detail-row">
            <div class="detail-label">日付</div>
            <div class="detail-content">
                @php $d=\Carbon\Carbon::parse($correction->attendance->work_date); @endphp
                <span class="date-year">{{ $d->format('Y年') }}</span>
                <span class="date-md">{{ $d->format('n月j日') }}</span>
            </div>
        </div>

        {{-- 出勤・退勤（申請値を表示／読み取り専用） --}}
        <div class="detail-row">
            <div class="detail-label">出勤・退勤</div>
            <div class="detail-content">
                <input type="text" class="input-small" value="{{ $prefill['in'] ?? '' }}" readonly>
                <span class="tilde">〜</span>
                <input type="text" class="input-small" value="{{ $prefill['out'] ?? '' }}" readonly>
            </div>
        </div>

        {{-- 休憩１（読み取り専用） --}}
        <div class="detail-row">
            <div class="detail-label">休憩</div>
            <div class="detail-content">
                <input type="text" class="input-small" value="{{ $prefill['b1s'] ?? '' }}" readonly>
                <span class="tilde">〜</span>
                <input type="text" class="input-small" value="{{ $prefill['b1e'] ?? '' }}" readonly>
            </div>
        </div>

        {{-- 休憩２（読み取り専用） --}}
        <div class="detail-row">
            <div class="detail-label">休憩2</div>
            <div class="detail-content">
                <input type="text" class="input-small" value="{{ $prefill['b2s'] ?? '' }}" readonly>
                <span class="tilde">〜</span>
                <input type="text" class="input-small" value="{{ $prefill['b2e'] ?? '' }}" readonly>
            </div>
        </div>

        {{-- 申請内容（読み取り専用） --}}
        <div class="detail-row">
            <div class="detail-label">申請内容</div>
            <div class="detail-content">
                <textarea class="input-note" readonly placeholder="申請内容">{{ $prefill['note'] ?? '' }}</textarea>
            </div>
        </div>
    </div>

    {{-- アクション：承認 or 承認済み（グレー） --}}
    <div class="detail-actions">
        @if($correction->status === 'approved')
        <button type="button" class="btn-fix" disabled
            style="background:#696969;color:#FFFFFF;border:none;">承認済み</button>
        @else
        <form id="approveForm" method="POST"
            action="{{ route('admin.corrections.approve', ['correction' => $correction->id]) }}"
            style="margin:0;">
            @csrf
            <button type="submit" id="approveBtn" class="btn-fix">承認</button>
        </form>
        @endif
    </div>
</div>

{{-- 承認：ページ遷移なし（AJAX）。コントローラ側は AJAX のとき JSON を返す実装でOK --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('approveForm');
        if (!form) return;

        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = document.getElementById('approveBtn');
            btn.disabled = true;

            try {
                const res = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value,
                        'Accept': 'application/json'
                    },
                    body: new FormData(form)
                });

                if (!res.ok) throw new Error('承認に失敗しました');

                // 状態文言を差し替え
                const st = document.getElementById('statusText');
                if (st) st.textContent = '承認済み';

                // ボタン置き換え（グレー＋白文字）
                form.outerHTML =
                    '<button type="button" class="btn-fix" disabled ' +
                    'style="background:#696969;color:#FFFFFF;border:none;">承認済み</button>';

            } catch (err) {
                alert(err.message || '承認に失敗しました');
                btn.disabled = false;
            }
        });
    });
</script>
@endsection