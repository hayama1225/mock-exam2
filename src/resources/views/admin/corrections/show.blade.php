@extends('layouts.app')

@section('title', '勤怠修正申請詳細（管理者）')

@section('content')
<div class="container" style="max-width:720px;">
    <h1 class="mb-3" style="font-weight:700;">勤怠修正申請詳細</h1>

    {{-- メッセージ --}}
    @if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    {{-- 詳細 --}}
    <table class="table table-bordered">
        <tbody>
            <tr>
                <th style="width:30%;">申請ID</th>
                <td>{{ $correction->id }}</td>
            </tr>
            <tr>
                <th>ユーザー</th>
                <td>{{ $correction->attendance->user->name ?? '-' }}</td>
            </tr>
            <tr>
                <th>日付</th>
                <td>{{ $correction->attendance->work_date ?? '-' }}</td>
            </tr>
            <tr>
                <th>申請内容</th>
                <td>{{ $correction->memo ?? '-' }}</td>
            </tr>
            <tr>
                <th>状態</th>
                <td>
                    @if($correction->status === 'pending')
                    <span class="badge bg-warning text-dark">承認待ち</span>
                    @else
                    <span class="badge bg-success">承認済み</span>
                    @endif
                </td>
            </tr>
        </tbody>
    </table>

    {{-- 承認ボタン --}}
    @if($correction->status === 'pending')
    <form method="POST" action="{{ route('admin.corrections.approve', $correction->id) }}">
        @csrf
        <button type="submit" class="btn btn-success">承認する</button>
    </form>
    @endif

    <a href="{{ route('admin.corrections.list', ['status' => 'pending']) }}"
        class="btn btn-outline-dark mt-3">申請一覧へ戻る</a>
</div>
@endsection