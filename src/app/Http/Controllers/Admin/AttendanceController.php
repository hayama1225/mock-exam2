<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use Carbon\Carbon;
use App\Http\Requests\Admin\Attendance\UpdateAttendanceRequest;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        // 月指定（YYYY-MM）。未指定は今月
        $month = $request->string('month')->toString();
        try {
            $cursor = $month
                ? Carbon::createFromFormat('Y-m', $month)->startOfMonth()
                : Carbon::now()->startOfMonth();
        } catch (\Throwable $e) {
            $cursor = Carbon::now()->startOfMonth();
        }
        $start = $cursor->copy();
        $end   = $cursor->copy()->endOfMonth();

        // キーワード（ユーザー名/メール）
        $q = trim((string)$request->input('q', ''));

        $attendances = Attendance::with(['user'])
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->when($q !== '', function ($query) use ($q) {
                $query->whereHas('user', function ($uq) use ($q) {
                    $uq->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('work_date')
            ->orderBy('user_id')
            ->paginate(20)
            ->withQueryString();

        // 前月/翌月
        $prevMonth = $start->copy()->subMonth()->format('Y-m');
        $nextMonth = $start->copy()->addMonth()->format('Y-m');

        return view('admin.attendance.index', compact('attendances', 'start', 'prevMonth', 'nextMonth', 'q'));
    }

    public function show(Attendance $attendance)
    {
        $attendance->load(['user', 'breaks' => function ($q) {
            $q->orderBy('break_start_at');
        }]);

        // 前日/翌日の同ユーザー勤怠（存在すればリンクに使う）
        $prev = Attendance::where('user_id', $attendance->user_id)
            ->where('work_date', '<', $attendance->work_date)
            ->orderBy('work_date', 'desc')
            ->first();

        $next = Attendance::where('user_id', $attendance->user_id)
            ->where('work_date', '>', $attendance->work_date)
            ->orderBy('work_date', 'asc')
            ->first();

        return view('admin.attendance.show', compact('attendance', 'prev', 'next'));
    }

    public function update(UpdateAttendanceRequest $request, Attendance $attendance)
    {
        $attendance->load('breaks');

        $date = Carbon::parse($attendance->work_date)->format('Y-m-d');

        // 入退勤
        $in  = Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $request->input('clock_in'));
        $out = Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $request->input('clock_out'));

        // 休憩（配列を正規化：空行は無視）
        $breaksInput = collect($request->input('breaks', []))
            ->filter(function ($row) {
                $s = $row['start'] ?? null;
                $e = $row['end'] ?? null;
                return $s && $e;
            })
            ->map(function ($row) use ($date) {
                return [
                    'start' => Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $row['start']),
                    'end'   => Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $row['end']),
                ];
            })
            ->values();

        // 追加の整合チェック：各休憩は出勤以降・退勤以前
        foreach ($breaksInput as $b) {
            if ($b['start']->lessThan($in) || $b['end']->greaterThan($out)) {
                return back()
                    ->withErrors(['breaks' => '休憩時間が不適切な値です'])
                    ->withInput();
            }
        }

        // 休憩合計秒
        $totalBreakSeconds = $breaksInput->reduce(function ($carry, $b) {
            return $carry + $b['end']->diffInSeconds($b['start']);
        }, 0);

        // 勤務秒（マイナスにならないようガード）
        $workSeconds = max(0, $out->diffInSeconds($in) - $totalBreakSeconds);

        // 保存：勤怠本体
        $attendance->update([
            'clock_in_at'         => $in,
            'clock_out_at'        => $out,
            'status'              => 1,
            'total_break_seconds' => $totalBreakSeconds,
            'work_seconds'        => $workSeconds,
        ]);

        // 既存休憩は一旦削除して登録し直す（少数なので単純化）
        AttendanceBreak::where('attendance_id', $attendance->id)->delete();
        foreach ($breaksInput as $b) {
            AttendanceBreak::create([
                'attendance_id'  => $attendance->id,
                'break_start_at' => $b['start'],
                'break_end_at'   => $b['end'],
            ]);
        }

        // 備考は今回は未保存（DBにカラムが無いため）。後続でカラム追加予定。

        return redirect()
            ->route('admin.attendance.show', $attendance)
            ->with('status', '勤怠を修正しました');
    }
}
