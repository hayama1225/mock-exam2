@extends('layouts.app')

@section('title', '申請一覧')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/requests-list.css') }}">
@endpush

@section('content')
@php
$userName = auth()->user()->name ?? '';
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
                    @forelse ($pending as $r)
                    <tr>
                        <td>承認待ち</td>
                        <td>{{ $userName }}</td>
                        <td>{{ \Carbon\Carbon::parse($r->work_date)->format('Y/m/d') }}</td>
                        <td>{{ $r->note }}</td>
                        <td>{{ optional($r->created_at)->setTimezone('Asia/Tokyo')?->format('Y/m/d') }}</td>
                        <td class="col-detail">
                            {{-- req を付けて申請内容をプレビュー --}}
                            <a href="{{ route('attendance.detail', ['attendance' => ($r->attendance_id ?? $r->attendance?->id), 'req' => $r->id]) }}"
                                class="detail-link">詳細</a>
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
                    @forelse ($approved as $r)
                    <tr>
                        <td>承認済み</td>
                        <td>{{ $userName }}</td>
                        <td>{{ \Carbon\Carbon::parse($r->work_date)->format('Y/m/d') }}</td>
                        <td>{{ $r->note }}</td>
                        <td>{{ optional($r->created_at)->setTimezone('Asia/Tokyo')?->format('Y/m/d') }}</td>
                        <td class="col-detail">
                            {{-- req を付けて遷移。detail側で approved はメッセージ非表示にする --}}
                            <a href="{{ route('attendance.detail', ['attendance' => ($r->attendance_id ?? $r->attendance?->id), 'req' => $r->id]) }}"
                                class="detail-link">詳細</a>
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