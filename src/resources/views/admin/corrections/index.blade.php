@extends('layouts.app')

@section('title', '勤怠修正申請一覧（管理者）')

@section('content')
<div class="container" style="max-width:1000px;">
    <h1 class="mb-3" style="font-weight:700;">勤怠修正申請一覧</h1>

    {{-- ステータス切替タブ --}}
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link {{ $status === 'pending' ? 'active' : '' }}"
                href="{{ route('admin.corrections.list', ['status' => 'pending']) }}">
                承認待ち
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $status === 'approved' ? 'active' : '' }}"
                href="{{ route('admin.corrections.list', ['status' => 'approved']) }}">
                承認済み
            </a>
        </li>
    </ul>

    {{-- メッセージ --}}
    @if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    {{-- 一覧 --}}
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead class="table-light">
                <tr>
                    <th>申請ID</th>
                    <th>ユーザー</th>
                    <th>日付</th>
                    <th>申請内容</th>
                    <th>状態</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $req)
                <tr>
                    <td>{{ $req->id }}</td>
                    <td>{{ $req->attendance->user->name ?? '-' }}</td>
                    <td>{{ $req->attendance->work_date ?? '-' }}</td>
                    <td>{{ $req->memo ?? '-' }}</td>
                    <td>
                        @if($req->status === 'pending')
                        <span class="badge bg-warning text-dark">承認待ち</span>
                        @else
                        <span class="badge bg-success">承認済み</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('admin.corrections.show', $req->id) }}"
                            class="btn btn-sm btn-outline-primary">
                            詳細
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        申請はありません。
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $requests->links() }}

    <a href="{{ route('admin.attendance.list') }}" class="btn btn-outline-dark mt-3">勤怠一覧へ戻る</a>
</div>
@endsection