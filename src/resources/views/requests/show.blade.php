@extends('layouts.app')

@section('content')
<div class="container" style="max-width:760px;">
    <h1 class="mb-4">申請詳細</h1>

    <div class="mb-3">
        <a href="{{ route('requests.index') }}" class="btn btn-outline-secondary">一覧へ戻る</a>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">状態</dt>
                <dd class="col-sm-9">{{ $correction->status === 'pending' ? '承認待ち' : '承認済み' }}</dd>

                <dt class="col-sm-3">対象日</dt>
                <dd class="col-sm-9">{{ \Carbon\Carbon::parse($correction->work_date)->format('Y/m/d') }}</dd>

                <dt class="col-sm-3">申請理由</dt>
                <dd class="col-sm-9">{{ $correction->note }}</dd>

                <dt class="col-sm-3">申請日時</dt>
                <dd class="col-sm-9">{{ $correction->created_at?->setTimezone($tz)->format('Y-m-d H:i:s') }}</dd>
            </dl>
        </div>
    </div>

    <div class="card">
        <div class="card-header">申請内容（希望時刻）</div>
        <div class="card-body">
            <ul class="mb-0">
                <li>出勤：{{ optional($correction->clock_in_at)->setTimezone($tz)?->format('H:i') }}</li>
                <li>退勤：{{ optional($correction->clock_out_at)->setTimezone($tz)?->format('H:i') }}</li>
                <li>休憩1：{{ optional($correction->break1_start_at)->setTimezone($tz)?->format('H:i') }} 〜 {{ optional($correction->break1_end_at)->setTimezone($tz)?->format('H:i') }}</li>
                <li>休憩2：{{ optional($correction->break2_start_at)->setTimezone($tz)?->format('H:i') }} 〜 {{ optional($correction->break2_end_at)->setTimezone($tz)?->format('H:i') }}</li>
            </ul>
        </div>
    </div>
</div>
@endsection