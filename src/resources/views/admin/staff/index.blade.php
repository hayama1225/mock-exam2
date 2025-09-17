@extends('layouts.app')

@section('title', 'スタッフ一覧')

@section('content')
<div class="container" style="max-width: 1100px;">
    <h1 class="mb-4" style="font-weight:700;">スタッフ一覧</h1>

    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th style="width:35%;">名前</th>
                    <th style="width:45%;">メールアドレス</th>
                    <th style="width:20%;">月次勤怠</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>
                        {{-- 遷移要件: 「詳細」押下で /admin/attendance/staff/{id} へ --}}
                        <a href="{{ route('admin.attendance.staff', ['user' => $user->id]) }}" class="btn btn-sm btn-outline-dark">
                            詳細
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="3">ユーザーが見つかりません。</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection