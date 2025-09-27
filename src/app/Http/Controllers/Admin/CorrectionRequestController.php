<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AttendanceCorrection;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use Carbon\Carbon;

class CorrectionRequestController extends Controller
{
    /**
     * 申請一覧（承認待ち / 承認済み）
     * GET: /stamp_correction_request/list
     */
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending'); // 初期選択用（UIのタブ）

        $base = \App\Models\AttendanceCorrection::with(['attendance', 'attendance.user'])
            ->orderBy('id', 'desc');

        // ★両タブ分を常に取得（#approved はサーバに届かないため）
        $pending  = (clone $base)->where('status', 'pending')->get();
        $approved = (clone $base)->where('status', 'approved')->get();

        return view('admin.corrections.index', [
            'pending'  => $pending,
            'approved' => $approved,
            'status'   => $status, // JSで初期タブを決めるのに利用（任意）
        ]);
    }

    /**
     * 修正申請詳細（承認画面）
     * GET: /stamp_correction_request/approve/{correction}
     */
    public function show(AttendanceCorrection $correction)
    {
        // 関連ロード
        $correction->load(['attendance.breaks', 'attendance.user']);

        $tz = 'Asia/Tokyo';
        $fmt = function ($v) use ($tz) {
            if (empty($v)) return '';
            try {
                return Carbon::parse($v)->setTimezone($tz)->format('H:i');
            } catch (\Throwable $e) {
                return '';
            }
        };

        $att = $correction->attendance;
        $b1  = $att->breaks[0] ?? null;
        $b2  = $att->breaks[1] ?? null;

        // 申請値 → なければ元の勤怠値、の順で採用（カラム名の揺れも吸収）
        $prefill = [
            'in'  => $fmt($correction->in  ?? $correction->clock_in_at  ?? $att->clock_in_at ?? null),
            'out' => $fmt($correction->out ?? $correction->clock_out_at ?? $att->clock_out_at ?? null),

            'b1s' => $fmt(
                $correction->b1s
                    ?? $correction->break1_start
                    ?? $correction->break1_start_at
                    ?? ($b1 ? $b1->break_start_at : null)
            ),
            'b1e' => $fmt(
                $correction->b1e
                    ?? $correction->break1_end
                    ?? $correction->break1_end_at
                    ?? ($b1 ? $b1->break_end_at : null)
            ),
            'b2s' => $fmt(
                $correction->b2s
                    ?? $correction->break2_start
                    ?? $correction->break2_start_at
                    ?? ($b2 ? $b2->break_start_at : null)
            ),
            'b2e' => $fmt(
                $correction->b2e
                    ?? $correction->break2_end
                    ?? $correction->break2_end_at
                    ?? ($b2 ? $b2->break_end_at : null)
            ),

            // メモは申請→無ければ元の勤怠
            'note' => $correction->memo ?? $correction->note ?? ($att->note ?? ''),
        ];

        return view('admin.corrections.show', [
            'correction' => $correction,
            'prefill'    => $prefill,
            'tz'         => $tz,
        ]);
    }

    /**
     * 承認実行（承認内容を Attendance に確定反映）
     * POST: /stamp_correction_request/approve/{correction}
     */
    public function approve(Request $request, AttendanceCorrection $correction)
    {
        $tz = 'Asia/Tokyo';

        // 関連読込
        $correction->load(['attendance.breaks']);
        /** @var Attendance $att */
        $att = $correction->attendance;

        if (!$att) {
            return back()->with('status', '対象の勤怠が見つかりませんでした。');
        }

        $date = Carbon::parse($att->work_date, $tz)->format('Y-m-d');

        // === 値の取り出し（カラム名の揺れを吸収） =========================

        // 出退勤
        $cinRaw  = $correction->clock_in_at  ?? $correction->in  ?? null;
        $coutRaw = $correction->clock_out_at ?? $correction->out ?? null;

        $toCarbonDT = function ($raw) use ($tz, $date) {
            if (empty($raw)) return null;
            try {
                // H:i 文字列なら日付を合成
                if (is_string($raw) && preg_match('/^\d{1,2}:\d{2}$/', $raw)) {
                    return Carbon::createFromFormat('Y-m-d H:i', "$date $raw", $tz);
                }
                return Carbon::parse($raw, $tz);
            } catch (\Throwable $e) {
                return null;
            }
        };

        $cin  = $toCarbonDT($cinRaw)  ?: ($att->clock_in_at  ? Carbon::parse($att->clock_in_at, $tz)  : null);
        $cout = $toCarbonDT($coutRaw) ?: ($att->clock_out_at ? Carbon::parse($att->clock_out_at, $tz) : null);

        // 休憩（最大2枠）
        $b1sRaw = $correction->break1_start_at ?? $correction->break1_start ?? $correction->b1s ?? null;
        $b1eRaw = $correction->break1_end_at   ?? $correction->break1_end   ?? $correction->b1e ?? null;
        $b2sRaw = $correction->break2_start_at ?? $correction->break2_start ?? $correction->b2s ?? null;
        $b2eRaw = $correction->break2_end_at   ?? $correction->break2_end   ?? $correction->b2e ?? null;

        $b1s = $toCarbonDT($b1sRaw);
        $b1e = $toCarbonDT($b1eRaw);
        $b2s = $toCarbonDT($b2sRaw);
        $b2e = $toCarbonDT($b2eRaw);

        // 備考
        $newNote = $correction->note ?? $correction->memo ?? $att->note;

        // === 休憩配列を構築（申請で1つでも指定があれば、申請側に置き換える） ===
        $hasAnyBreakInCorr = ($b1s || $b1e || $b2s || $b2e);

        $breakPairs = [];
        if ($hasAnyBreakInCorr) {
            if ($b1s && $b1e) $breakPairs[] = ['start' => $b1s, 'end' => $b1e];
            if ($b2s && $b2e) $breakPairs[] = ['start' => $b2s, 'end' => $b2e];
        } else {
            // 申請側に休憩が無ければ、既存の休憩を維持
            $att->loadMissing('breaks');
            foreach ($att->breaks as $b) {
                if ($b->break_start_at && $b->break_end_at) {
                    $breakPairs[] = [
                        'start' => Carbon::parse($b->break_start_at, $tz),
                        'end'   => Carbon::parse($b->break_end_at, $tz),
                    ];
                }
            }
        }

        // === 合計休憩秒 ===
        $totalBreakSeconds = 0;
        foreach ($breakPairs as $p) {
            $totalBreakSeconds += $p['end']->diffInSeconds($p['start']);
        }

        // === 実働秒 ===
        $workSeconds = ($cin && $cout)
            ? max(0, $cout->diffInSeconds($cin) - $totalBreakSeconds)
            : (int)($att->work_seconds ?? 0);

        // === Attendance 本体を確定反映 ===
        $att->clock_in_at         = $cin ?: $att->clock_in_at;
        $att->clock_out_at        = $cout ?: $att->clock_out_at;
        $att->total_break_seconds = $totalBreakSeconds;
        $att->work_seconds        = $workSeconds;
        $att->note                = $newNote;
        $att->status              = 1; // 確定

        $att->save();

        // === 休憩レコードも置き換え（申請に休憩指定がある場合のみ） ===
        if ($hasAnyBreakInCorr) {
            AttendanceBreak::where('attendance_id', $att->id)->delete();
            foreach ($breakPairs as $p) {
                AttendanceBreak::create([
                    'attendance_id'  => $att->id,
                    'break_start_at' => $p['start'],
                    'break_end_at'   => $p['end'],
                ]);
            }
        }

        // === 申請ステータスを承認に確定 ===
        $correction->status = 'approved';
        $correction->approved_at = now($tz);
        $correction->save();

        // AJAXなら同画面で「承認済み」表示に置換、通常は一覧へ
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['ok' => true, 'status' => 'approved']);
        }

        return redirect()
            ->route('admin.corrections.list', ['status' => 'approved'])
            ->with('status', '修正申請を承認しました（勤怠へ反映済み）。');
    }
}
