@extends('layouts.app')

@section('title', '申請一覧')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/requests-list.css') }}">
@endpush

@section('content')
@php
// 管理者ヘッダーを有効化（レイアウト仕様）
$isAdminHeader = true;
$headerLogoUrl = url('/admin/login');

// コントローラ両対応：
// 1) $pending / $approved が渡される場合
// 2) 既存の $status / $requests（Paginator/Collection）が渡される場合
$listPending = isset($pending) ? $pending : (($status ?? null) === 'pending' ? ($requests ?? collect()) : collect());
$listApproved = isset($approved) ? $approved : (($status ?? null) === 'approved' ? ($requests ?? collect()) : collect());
@endphp

<main class="main list-main">
    <div class="list-heading">
        <span class="vbar" aria-hidden="true"></span>
        <h1 class="list-title">申請一覧</h1>
    </div>

    <div class="req-tabs" role="tablist" aria-label="申請の状態で絞り込み">
        <a href="#pending" class="tab-link" id="tab-pending" role="tab" aria-controls="pane-pending">承認待ち</a>
        <a href="#approved" class="tab-link" id="tab-approved" role="tab" aria-controls="pane-approved">承認済み</a>
    </div>

    <hr class="req-hr">

    {{-- 承認待ち --}}
    <section id="pending" class="req-pane" role="tabpanel" aria-labelledby="tab-pending">
        <div class="list-card">
            <table class="att-table" aria-label="承認待ちの申請一覧">
                <thead>
                    <tr>
                        <th class="col-state">状態</th>
                        <th class="col-name">名前</th>
                        <th class="col-date">対象日時</th>
                        <th class="col-reason">申請理由</th>
                        <th class="col-created">申請日時</th>
                        <th class="col-detail">詳細</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($listPending as $r)
                    @php
                    $attId = $r->attendance_id ?? $r->attendance?->id;
                    $workDt = $r->work_date ?? $r->attendance?->work_date;
                    $userNm = $r->user->name ?? $r->attendance?->user?->name ?? '';
                    $note = $r->note ?? $r->memo ?? '';
                    $created = optional($r->created_at)->setTimezone('Asia/Tokyo');
                    @endphp
                    <tr>
                        <td>承認待ち</td>
                        <td>{{ $userNm }}</td>
                        <td>{{ \Carbon\Carbon::parse($workDt)->format('Y/m/d') }}</td>
                        <td>{{ $note }}</td>
                        <td>{{ $created?->format('Y/m/d') }}</td>
                        <td class="col-detail">
                            {{-- 要件どおりの承認画面へ遷移 --}}
                            <a href="{{ route('admin.corrections.show', ['correction' => $r->id]) }}" class="detail-link">詳細</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="empty">承認待ちの申請はありません。</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- 承認済み --}}
    <section id="approved" class="req-pane" role="tabpanel" aria-labelledby="tab-approved">
        <div class="list-card">
            <table class="att-table" aria-label="承認済みの申請一覧">
                <thead>
                    <tr>
                        <th class="col-state">状態</th>
                        <th class="col-name">名前</th>
                        <th class="col-date">対象日時</th>
                        <th class="col-reason">申請理由</th>
                        <th class="col-created">申請日時</th>
                        <th class="col-detail">詳細</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($listApproved as $r)
                    @php
                    $attId = $r->attendance_id ?? $r->attendance?->id;
                    $workDt = $r->work_date ?? $r->attendance?->work_date;
                    $userNm = $r->user->name ?? $r->attendance?->user?->name ?? '';
                    $note = $r->note ?? $r->memo ?? '';
                    $created = optional($r->created_at)->setTimezone('Asia/Tokyo');
                    @endphp
                    <tr>
                        <td>承認済み</td>
                        <td>{{ $userNm }}</td>
                        <td>{{ \Carbon\Carbon::parse($workDt)->format('Y/m/d') }}</td>
                        <td>{{ $note }}</td>
                        <td>{{ $created?->format('Y/m/d') }}</td>
                        <td class="col-detail">
                            <a href="{{ route('admin.corrections.show', ['correction' => $r->id]) }}" class="detail-link">詳細</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="empty">承認済みの申請はありません。</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</main>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const tabs = document.querySelectorAll('.tab-link');
        const panes = document.querySelectorAll('.req-pane');

        function apply() {
            const hash = (location.hash || '#pending');
            tabs.forEach(a => a.classList.toggle('is-active', a.getAttribute('href') === hash));
            panes.forEach(p => p.classList.toggle('is-active', ('#' + p.id) === hash));
        }
        window.addEventListener('hashchange', apply, {
            passive: true
        });
        apply();
    });
</script>
@endsection