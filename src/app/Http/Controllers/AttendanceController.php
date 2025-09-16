<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\AttendanceCorrection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\StoreAttendanceCorrectionRequest;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $tz = 'Asia/Tokyo';
        $today = Carbon::now($tz)->toDateString();

        $attendance = Attendance::with(['breaks' => function ($q) {
            $q->orderBy('break_start_at');
        }])->where('user_id', Auth::id())
            ->where('work_date', $today)
            ->first();

        [$status, $flags] = $this->deriveStatusAndFlags($attendance);

        return view('attendance.index', [
            'attendance' => $attendance,
            'status' => $status, // 'not_working' | 'working' | 'on_break' | 'completed'
            'flags' => $flags,   // ['can_clock_in'=>bool, 'can_start_break'=>bool, 'can_end_break'=>bool, 'can_clock_out'=>bool]
            'tz' => $tz,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'action' => 'required|in:clock_in,start_break,end_break,clock_out',
        ], [
            'action.required' => '不正な操作です。',
            'action.in' => '不正な操作です。',
        ]);

        $tz = 'Asia/Tokyo';
        $now = Carbon::now($tz);
        $today = $now->toDateString();

        return DB::transaction(function () use ($request, $today, $now) {
            $userId = Auth::id();

            // その日の勤怠を取得（存在しなければ clock_in 以外は不可）
            $attendance = Attendance::with(['breaks' => function ($q) {
                $q->orderByDesc('id');
            }])->where('user_id', $userId)
                ->where('work_date', $today)
                ->lockForUpdate()
                ->first();

            $action = $request->input('action');

            if ($action === 'clock_in') {
                if ($attendance && $attendance->clock_in_at) {
                    return back()->with('error', '本日は既に出勤済みです。');
                }
                if (!$attendance) {
                    $attendance = new Attendance();
                    $attendance->user_id = $userId;
                    $attendance->work_date = $today;
                }
                $attendance->clock_in_at = $now;
                $attendance->save();

                return back()->with('success', '出勤しました。');
            }

            // 以降の操作は attendance が必須
            if (!$attendance || !$attendance->clock_in_at) {
                return back()->with('error', '本日はまだ出勤していません。');
            }
            if ($attendance->clock_out_at) {
                return back()->with('error', '本日は既に退勤済みです。');
            }

            // 直近の休憩レコード（未終了があればそれが open break）
            $openBreak = $attendance->breaks->firstWhere('break_end_at', null);

            if ($action === 'start_break') {
                if ($openBreak) {
                    return back()->with('error', 'すでに休憩中です。');
                }
                AttendanceBreak::create([
                    'attendance_id' => $attendance->id,
                    'break_start_at' => $now,
                ]);
                return back()->with('success', '休憩を開始しました。');
            }

            if ($action === 'end_break') {
                if (!$openBreak) {
                    return back()->with('error', '開始中の休憩がありません。');
                }
                $openBreak->break_end_at = $now;
                $openBreak->save();
                return back()->with('success', '休憩を終了しました。');
            }

            if ($action === 'clock_out') {
                if ($openBreak) {
                    return back()->with('error', '休憩中は退勤できません。先に休憩を終了してください。');
                }
                $attendance->clock_out_at = $now;

                // 合計休憩秒を再計算（安全のため都度集計）
                $totalBreak = AttendanceBreak::where('attendance_id', $attendance->id)
                    ->whereNotNull('break_start_at')
                    ->whereNotNull('break_end_at')
                    ->get()
                    ->reduce(function ($carry, $b) {
                        return $carry + Carbon::parse($b->break_end_at)->diffInSeconds(Carbon::parse($b->break_start_at));
                    }, 0);

                $attendance->total_break_seconds = $totalBreak;

                // 実働時間（秒）= 出勤〜退勤 - 休憩合計
                if ($attendance->clock_in_at && $attendance->clock_out_at) {
                    $worked = Carbon::parse($attendance->clock_out_at)->diffInSeconds(Carbon::parse($attendance->clock_in_at));
                    $attendance->work_seconds = max(0, $worked - $totalBreak);
                }

                $attendance->save();

                return back()->with('success', '退勤しました。お疲れさまでした！');
            }

            return back()->with('error', '不正な操作です。');
        });
    }

    /**
     * 状態とボタン可否フラグを導出
     */
    private function deriveStatusAndFlags(?Attendance $attendance): array
    {
        if (!$attendance || !$attendance->clock_in_at) {
            return [
                'not_working',
                [
                    'can_clock_in' => true,
                    'can_start_break' => false,
                    'can_end_break' => false,
                    'can_clock_out' => false,
                ],
            ];
        }

        if ($attendance->clock_out_at) {
            return [
                'completed',
                [
                    'can_clock_in' => false,
                    'can_start_break' => false,
                    'can_end_break' => false,
                    'can_clock_out' => false,
                ],
            ];
        }

        $openBreak = $attendance->breaks->firstWhere('break_end_at', null);

        if ($openBreak) {
            return [
                'on_break',
                [
                    'can_clock_in' => false,
                    'can_start_break' => false,
                    'can_end_break' => true,
                    'can_clock_out' => false,
                ],
            ];
        }

        return [
            'working',
            [
                'can_clock_in' => false,
                'can_start_break' => true,
                'can_end_break' => false,
                'can_clock_out' => true,
            ],
        ];
    }

    public function list(Request $request)
    {
        $tz = 'Asia/Tokyo';
        $now = \Carbon\Carbon::now($tz);
        $ym = $request->query('ym', $now->format('Y-m'));  // 例: 2025-09

        // ymバリデーション（雑にフォーマットだけ見る）
        if (!preg_match('/^\d{4}-\d{2}$/', $ym)) {
            $ym = $now->format('Y-m');
        }

        $first = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', "{$ym}-01 00:00:00", $tz)->startOfDay();
        $start = $first->copy()->startOfMonth();
        $end   = $first->copy()->endOfMonth();

        // 当月の自分の勤怠を取得
        $attendances = \App\Models\Attendance::with('breaks')
            ->where('user_id', \Illuminate\Support\Facades\Auth::id())
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('work_date')
            ->get()
            ->keyBy('work_date'); // 'Y-m-d' をキーに

        // カレンダー行用（1日〜末日）
        $days = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $dateStr = $cursor->toDateString(); // Y-m-d
            $row = [
                'date' => $cursor->copy(), // Carbon
                'attendance' => $attendances->get($dateStr),
            ];
            $days[] = $row;
            $cursor->addDay();
        }

        $prevYm = $start->copy()->subMonth()->format('Y-m');
        $nextYm = $start->copy()->addMonth()->format('Y-m');

        return view('attendance.list', [
            'tz' => $tz,
            'ym' => $ym,
            'days' => $days,
            'prevYm' => $prevYm,
            'nextYm' => $nextYm,
        ]);
    }

    public function detail(\App\Models\Attendance $attendance)
    {
        // 所有権チェック（他人のレコードは不可）
        if ($attendance->user_id !== \Illuminate\Support\Facades\Auth::id()) {
            abort(403);
        }
        $attendance->load(['breaks' => function ($q) {
            $q->orderBy('break_start_at');
        }]);

        $pending = \App\Models\AttendanceCorrection::where('attendance_id', $attendance->id)
            ->where('status', 'pending')->exists();

        $tz = 'Asia/Tokyo';
        return view('attendance.detail', [
            'attendance' => $attendance,
            'tz' => $tz,
            'pending' => $pending,
        ]);
    }

    // ----- 以下をクラス末尾あたりに追加（秒→H:MM表示用） -----
    private function secondsToHm(?int $seconds): string
    {
        $seconds = (int)($seconds ?? 0);
        $h = intdiv($seconds, 3600);
        $m = intdiv($seconds % 3600, 60);
        return sprintf('%d:%02d', $h, $m);
    }

    public function requestCorrection(StoreAttendanceCorrectionRequest $request, Attendance $attendance)
    {
        if ($attendance->user_id !== \Illuminate\Support\Facades\Auth::id()) {
            abort(403);
        }

        // 既に承認待ちがあれば申請不可（FN033）
        $hasPending = AttendanceCorrection::where('attendance_id', $attendance->id)
            ->where('status', 'pending')->exists();
        if ($hasPending) {
            return back()->with('error', '承認待ちのため修正はできません。');
        }

        $tz = 'Asia/Tokyo';
        $date = $attendance->work_date;

        $toDT = function ($hm) use ($date, $tz) {
            if (!$hm) return null;
            return \Carbon\Carbon::createFromFormat('Y-m-d H:i', "$date $hm", $tz);
        };

        AttendanceCorrection::create([
            'user_id' => auth()->id(),
            'attendance_id' => $attendance->id,
            'work_date' => $attendance->work_date,
            'clock_in_at'  => $toDT($request->input('in')),
            'clock_out_at' => $toDT($request->input('out')),
            'break1_start_at' => $toDT($request->input('b1s')),
            'break1_end_at'   => $toDT($request->input('b1e')),
            'break2_start_at' => $toDT($request->input('b2s')),
            'break2_end_at'   => $toDT($request->input('b2e')),
            'note' => $request->input('note'),
            'status' => 'pending',
        ]);

        return redirect()->route('attendance.detail', ['attendance' => $attendance->id])
            ->with('success', '修正申請を受け付けました（承認待ち）。');
    }

    // 申請一覧
    public function requestsIndex()
    {
        $userId = Auth::id();

        $pending = AttendanceCorrection::where('user_id', $userId)
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->get();

        $approved = AttendanceCorrection::where('user_id', $userId)
            ->where('status', 'approved')
            ->orderByDesc('created_at')
            ->get();

        return view('requests.index', compact('pending', 'approved'));
    }

    // 申請詳細
    public function requestsShow(AttendanceCorrection $correction)
    {
        if ($correction->user_id !== Auth::id()) {
            abort(403);
        }

        $correction->load('attendance');
        $tz = 'Asia/Tokyo';

        return view('requests.show', [
            'correction' => $correction,
            'tz' => $tz,
        ]);
    }
}
