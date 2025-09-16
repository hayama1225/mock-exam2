<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
}
