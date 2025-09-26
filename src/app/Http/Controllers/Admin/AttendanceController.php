<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\User;
use Carbon\Carbon;
use App\Http\Requests\Admin\Attendance\UpdateAttendanceRequest;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $tz = 'Asia/Tokyo';

        // ① 表示基準日を決定（?date 優先、なければ ?month 月初、どちらも無ければ「今日」）
        $month = $request->string('month')->toString();
        $dateParam = $request->string('date')->toString();

        try {
            if ($dateParam !== '') {
                $cursor = Carbon::createFromFormat('Y-m-d', $dateParam, $tz)->startOfDay();
            } elseif ($month !== '') {
                $cursor = Carbon::createFromFormat('Y-m', $month, $tz)->startOfMonth();
            } else {
                $cursor = Carbon::now($tz)->startOfDay();
            }
        } catch (\Throwable $e) {
            $cursor = Carbon::now($tz)->startOfDay();
        }

        // ② 月情報（ビューが使うので維持）
        $start = $cursor->copy()->startOfMonth();
        $end   = $cursor->copy()->endOfMonth();
        $prevMonth = $start->copy()->subMonth()->format('Y-m');
        $nextMonth = $start->copy()->addMonth()->format('Y-m');

        // ③ キーワード（ユーザー名/メール）
        $q = trim((string)$request->input('q', ''));

        // ④ 一覧データは「日付で絞る」
        $attendances = Attendance::with(['user'])
            ->whereDate('work_date', $cursor->toDateString())
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

        // 入退勤（FormRequestでH:i保証済み）
        $in  = Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $request->input('clock_in'));
        $out = Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $request->input('clock_out'));

        // 休憩（空行は無視）
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

        // 休憩合計秒
        $totalBreakSeconds = $breaksInput->reduce(function ($carry, $b) {
            return $carry + $b['end']->diffInSeconds($b['start']);
        }, 0);

        // 勤務秒
        $workSeconds = max(0, $out->diffInSeconds($in) - $totalBreakSeconds);

        // 保存：勤怠本体
        $attendance->update([
            'clock_in_at'         => $in,
            'clock_out_at'        => $out,
            'status'              => 1,
            'total_break_seconds' => $totalBreakSeconds,
            'work_seconds'        => $workSeconds,
            'note'                => $request->input('note'), // 備考も保存
        ]);

        // 既存休憩リセット→再作成
        AttendanceBreak::where('attendance_id', $attendance->id)->delete();
        foreach ($breaksInput as $b) {
            AttendanceBreak::create([
                'attendance_id'  => $attendance->id,
                'break_start_at' => $b['start'],
                'break_end_at'   => $b['end'],
            ]);
        }

        return redirect()
            ->route('admin.attendance.show', ['attendance' => $attendance->id])
            ->with('status', '勤怠を修正しました');
    }

    /**
     * スタッフ別 月次勤怠一覧
     */
    public function staffMonthly(Request $request, int $user)
    {
        $tz = 'Asia/Tokyo';

        $staff = User::select('id', 'name', 'email')->findOrFail($user);

        // ?month=YYYY-MM
        $month = (string)$request->query('month', '');
        try {
            $cursor = $month
                ? Carbon::createFromFormat('Y-m', $month, $tz)->startOfMonth()
                : Carbon::now($tz)->startOfMonth();
        } catch (\Throwable $e) {
            $cursor = Carbon::now($tz)->startOfMonth();
        }

        $start = $cursor->copy()->startOfMonth();
        $end   = $cursor->copy()->endOfMonth();
        $prevMonth = $start->copy()->subMonth()->format('Y-m');
        $nextMonth = $start->copy()->addMonth()->format('Y-m');

        // 当該スタッフの当月勤怠
        $attendances = Attendance::with(['breaks'])
            ->where('user_id', $staff->id)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('work_date')
            ->get()
            ->keyBy('work_date');

        // 1日〜末日行
        $days = [];
        $c = $start->copy();
        while ($c->lte($end)) {
            $key = $c->toDateString();
            $days[] = [
                'date' => $c->copy(),
                'attendance' => $attendances->get($key),
            ];
            $c->addDay();
        }

        // 月サマリー（必要なら表示に使用）
        $workedSeconds = (int)$attendances->sum('work_seconds');
        $breakSeconds  = (int)$attendances->sum('total_break_seconds');
        $workedDays    = $attendances->filter(fn($a) => !empty($a->clock_in_at))->count();
        $summary = [
            'worked_days' => $workedDays,
            'worked_hm'   => $this->secondsToHm($workedSeconds),
            'break_hm'    => $this->secondsToHm($breakSeconds),
        ];

        return view('admin.attendance.staff', [
            'staff'     => $staff,
            'start'     => $start,
            'prevMonth' => $prevMonth,
            'nextMonth' => $nextMonth,
            'days'      => $days,
            'summary'   => $summary,
            'month'     => $start->format('Y-m'), // ← ビューの表示月に使用
        ]);
    }

    /**
     * CSV出力
     */
    public function staffMonthlyCsv(Request $request, int $user)
    {
        $tz = 'Asia/Tokyo';
        $staff = User::select('id', 'name', 'email')->findOrFail($user);

        $month = (string)$request->query('month', '');
        try {
            $cursor = $month
                ? Carbon::createFromFormat('Y-m', $month, $tz)->startOfMonth()
                : Carbon::now($tz)->startOfMonth();
        } catch (\Throwable $e) {
            $cursor = Carbon::now($tz)->startOfMonth();
        }

        $start = $cursor->copy()->startOfMonth();
        $end   = $cursor->copy()->endOfMonth();

        $records = Attendance::where('user_id', $staff->id)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('work_date')
            ->get();

        $filename = sprintf('attendance_%s_%s.csv', $staff->id, $start->format('Y-m'));

        return response()->streamDownload(function () use ($records, $staff) {
            $out = fopen('php://output', 'w');

            $fput = function ($row) use ($out) {
                fputcsv($out, array_map(function ($v) {
                    return mb_convert_encoding($v ?? '', 'SJIS-win', 'UTF-8');
                }, $row));
            };

            // ヘッダ
            $fput(['スタッフ名', $staff->name]);
            $fput(['メール', $staff->email]);
            $fput([]);
            $fput(['日付', '出勤', '退勤', '休憩(H:MM)', '実働(H:MM)', '詳細ID']);

            foreach ($records as $a) {
                $fput([
                    $a->work_date,
                    optional($a->clock_in_at)->format('H:i'),
                    optional($a->clock_out_at)->format('H:i'),
                    $this->secondsToHm($a->total_break_seconds),
                    $this->secondsToHm($a->work_seconds),
                    $a->id,
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=Shift_JIS',
        ]);
    }

    private function secondsToHm(?int $seconds): string
    {
        $s = (int) max(0, $seconds ?? 0);
        $h = intdiv($s, 3600);
        $m = intdiv($s % 3600, 60);
        return sprintf('%d:%02d', $h, $m);
    }
}
