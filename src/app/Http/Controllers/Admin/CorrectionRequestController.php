<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AttendanceCorrection;
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
     * 承認実行
     * POST: /stamp_correction_request/approve/{correction}
     */
    public function approve(Request $request, AttendanceCorrection $correction)
    {
        $correction->status = 'approved';
        $correction->approved_at = now();
        $correction->save();

        // AJAXなら同画面で「承認済み」表示に置換、通常は一覧へ
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['ok' => true, 'status' => 'approved']);
        }

        return redirect()
            ->route('admin.corrections.list', ['status' => 'approved'])
            ->with('status', '修正申請を承認しました。');
    }
}
