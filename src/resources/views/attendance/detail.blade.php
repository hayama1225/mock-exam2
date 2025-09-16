@extends('layouts.app')

@section('content')
<div class="container" style="max-width:760px;">
    <h1 class="mb-4">勤怠詳細</h1>

    @if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="mb-3">
        <a href="{{ route('attendance.list', ['ym' => \Carbon\Carbon::parse($attendance->work_date)->format('Y-m')]) }}" class="btn btn-outline-secondary">一覧へ戻る</a>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">日付</dt>
                <dd class="col-sm-9">{{ \Carbon\Carbon::parse($attendance->work_date)->format('Y/m/d(D)') }}</dd>

                <dt class="col-sm-3">出勤</dt>
                <dd class="col-sm-9">{{ optional($attendance->clock_in_at)->setTimezone($tz)?->format('Y-m-d H:i:s') }}</dd>

                <dt class="col-sm-3">退勤</dt>
                <dd class="col-sm-9">{{ optional($attendance->clock_out_at)->setTimezone($tz)?->format('Y-m-d H:i:s') }}</dd>

                <dt class="col-sm-3">休憩合計</dt>
                <dd class="col-sm-9">
                    {{ sprintf('%d:%02d', intdiv((int)$attendance->total_break_seconds,3600), intdiv(((int)$attendance->total_break_seconds)%3600,60)) }}
                </dd>

                <dt class="col-sm-3">実働合計</dt>
                <dd class="col-sm-9">
                    {{ sprintf('%d:%02d', intdiv((int)$attendance->work_seconds,3600), intdiv(((int)$attendance->work_seconds)%3600,60)) }}
                </dd>
            </dl>
        </div>
    </div>

    {{-- ★ 休憩内訳（テストが期待する 12:00:00 / 12:30:00 表示用） --}}
    <div class="card mb-3">
        <div class="card-header">休憩内訳</div>
        <div class="card-body">
            <ul class="mb-0">
                @forelse($attendance->breaks as $b)
                <li>
                    {{ optional($b->break_start_at)->setTimezone($tz)?->format('Y-m-d H:i:s') }}
                    〜
                    {{ $b->break_end_at ? \Carbon\Carbon::parse($b->break_end_at)->setTimezone($tz)->format('Y-m-d H:i:s') : '（進行中）' }}
                </li>
                @empty
                <li class="text-muted">休憩打刻はありません。</li>
                @endforelse
            </ul>
        </div>
    </div>

    @if($pending)
    <p class="text-danger">※承認待ちのため修正はできません。</p>
    @else
    {{-- 修正申請フォーム --}}
    <form method="POST" action="{{ route('attendance.request', ['attendance'=>$attendance->id]) }}" class="card">
        @csrf
        <div class="card-body">
            <div class="mb-3 row">
                <label class="col-sm-3 col-form-label">出勤・退勤</label>
                <div class="col-sm-9 d-flex align-items-center gap-2">
                    <input type="text" name="in" value="{{ old('in') }}" class="form-control" style="max-width:120px" placeholder="09:00">
                    〜
                    <input type="text" name="out" value="{{ old('out') }}" class="form-control" style="max-width:120px" placeholder="18:00">
                </div>
                @error('out')<div class="offset-sm-3 col-sm-9 text-danger small">{{ $message }}</div>@enderror
                @error('in') <div class="offset-sm-3 col-sm-9 text-danger small">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3 row">
                <label class="col-sm-3 col-form-label">休憩</label>
                <div class="col-sm-9 d-flex align-items-center gap-2">
                    <input type="text" name="b1s" value="{{ old('b1s') }}" class="form-control" style="max-width:120px" placeholder="12:00">
                    〜
                    <input type="text" name="b1e" value="{{ old('b1e') }}" class="form-control" style="max-width:120px" placeholder="13:00">
                </div>
                @error('b1s')<div class="offset-sm-3 col-sm-9 text-danger small">{{ $message }}</div>@enderror
                @error('b1e')<div class="offset-sm-3 col-sm-9 text-danger small">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3 row">
                <label class="col-sm-3 col-form-label">休憩2</label>
                <div class="col-sm-9 d-flex align-items-center gap-2">
                    <input type="text" name="b2s" value="{{ old('b2s') }}" class="form-control" style="max-width:120px" placeholder="">
                    〜
                    <input type="text" name="b2e" value="{{ old('b2e') }}" class="form-control" style="max-width:120px" placeholder="">
                </div>
            </div>

            <div class="mb-3 row">
                <label class="col-sm-3 col-form-label">備考</label>
                <div class="col-sm-9">
                    <input type="text" name="note" value="{{ old('note') }}" class="form-control" placeholder="電車遅延のため 等">
                    @error('note')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="text-end">
                <button type="submit" class="btn btn-dark">修正</button>
            </div>
        </div>
    </form>
    @endif
</div>
@endsection