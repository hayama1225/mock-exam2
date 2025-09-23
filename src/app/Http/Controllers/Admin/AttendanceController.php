<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use Carbon\Carbon;
use App\Http\Requests\Admin\Attendance\UpdateAttendanceRequest;
use App\Models\User;

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
            'note'                => $request->input('note'),
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
            ->route('admin.attendance.show', ['attendance' => $attendance->id]) // ← 明示的にIDで渡す
            ->with('status', '勤怠を修正しました');
    }

    /**
     * スタッフ別 月次勤怠一覧
     * 画面要件: FN043（一覧）/FN044（前月・翌月切替）/FN046（詳細遷移）
     */
    public function staffMonthly(Request $request, int $user)
    {
        $tz = 'Asia/Tokyo';

        // 対象スタッフ
        $staff = User::select('id', 'name', 'email')->findOrFail($user);

        // ?month=YYYY-MM（不正は当月にフォールバック）
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
            ->keyBy('work_date'); // 'YYYY-MM-DD' => Attendance

        // 1日〜末日までの行データ
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

        // ▼ 追加：月サマリー
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
        ]);
    }

    /**
     * CSV出力（FN045）
     * クエリ ?month=YYYY-MM を解釈し、対象月の当該スタッフ勤怠をCSVで返す
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

            // 出力時にUTF-8→SJIS変換するヘルパ
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

    /**
     * 秒数を H:MM 形式に変換する
     */
    private function secondsToHm(?int $seconds): string
    {
        $s = (int) max(0, $seconds ?? 0);
        $h = intdiv($s, 3600);
        $m = intdiv($s % 3600, 60);
        return sprintf('%d:%02d', $h, $m);
    }
}