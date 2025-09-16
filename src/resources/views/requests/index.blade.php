@extends('layouts.app')

@section('content')
<div class="container" style="max-width:1000px;">
    <h1 class="mb-4">申請一覧</h1>

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#pending">承認待ち</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#approved">承認済み</a></li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="pending">
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-sm mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>状態</th>
                                <th>対象日</th>
                                <th>申請理由</th>
                                <th>申請日時</th>
                                <th>詳細</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pending as $r)
                            <tr>
                                <td>承認待ち</td>
                                <td>{{ \Carbon\Carbon::parse($r->work_date)->format('Y/m/d') }}</td>
                                <td>{{ $r->note }}</td>
                                <td>{{ $r->created_at?->setTimezone('Asia/Tokyo')->format('Y/m/d') }}</td>
                                <td><a href="{{ route('requests.show',$r->id) }}" class="btn btn-link btn-sm p-0">詳細</a></td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-muted">承認待ちの申請はありません。</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="approved">
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-sm mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>状態</th>
                                <th>対象日</th>
                                <th>申請理由</th>
                                <th>申請日時</th>
                                <th>詳細</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($approved as $r)
                            <tr>
                                <td>承認済み</td>
                                <td>{{ \Carbon\Carbon::parse($r->work_date)->format('Y/m/d') }}</td>
                                <td>{{ $r->note }}</td>
                                <td>{{ $r->created_at?->setTimezone('Asia/Tokyo')->format('Y/m/d') }}</td>
                                <td><a href="{{ route('requests.show',$r->id) }}" class="btn btn-link btn-sm p-0">詳細</a></td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-muted">承認済みの申請はありません。</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection