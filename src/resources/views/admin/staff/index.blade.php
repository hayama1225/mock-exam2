@extends('layouts.app')

@section('title', 'スタッフ一覧')

@push('styles')
{{-- corrections と同じ見た目に統一 --}}
<link rel="stylesheet" href="{{ asset('css/requests-list.css') }}">
<link rel="stylesheet" href="{{ asset('css/staff-list.css') }}">
@endpush

@section('content')
@php
// 管理者ヘッダー（レイアウト仕様）
$isAdminHeader = true;
$headerLogoUrl = url('/admin/login');
@endphp

<main class="main list-main">
    <div class="list-heading">
        <span class="vbar" aria-hidden="true"></span>
        <h1 class="list-title">スタッフ一覧</h1>
    </div>

    <div class="list-card">
        <table class="att-table" aria-label="スタッフ一覧">
            <thead>
                <tr>
                    <th class="col-name">名前</th>
                    <th class="col-email">メールアドレス</th>
                    <th class="col-detail">月次勤怠</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td class="col-detail">
                        {{-- 「詳細」押下で /admin/attendance/staff/{id} へ --}}
                        <a href="{{ route('admin.attendance.staff', ['user' => $user->id]) }}" class="detail-link">詳細</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="empty">ユーザーが見つかりません。</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</main>
@endsection