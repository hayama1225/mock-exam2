<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AttendanceCorrection; // ← 正しいモデル名に統一

class CorrectionRequestController extends Controller
{
    /**
     * 申請一覧（承認待ち / 承認済み）
     * GET: /stamp_correction_request/list
     */
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending'); // デフォルトは承認待ち

        $query = AttendanceCorrection::with(['attendance', 'attendance.user'])
            ->orderBy('id', 'desc');

        if ($status === 'approved') {
            $query->where('status', 'approved');
        } else {
            // それ以外（未指定含む）は pending を表示
            $query->where('status', 'pending');
        }

        $requests = $query->paginate(10);

        return view('admin.corrections.index', [
            'requests' => $requests,
            'status'   => $status,
        ]);
    }

    /**
     * 修正申請詳細（承認画面）
     * GET: /stamp_correction_request/approve/{correction}
     */
    public function show(AttendanceCorrection $correction)
    {
        $correction->load(['attendance', 'attendance.user']);

        return view('admin.corrections.show', [
            'correction' => $correction,
        ]);
    }

    /**
     * 承認実行
     * POST: /stamp_correction_request/approve/{correction}
     */
    public function approve(Request $request, AttendanceCorrection $correction)
    {
        // ステータス更新（承認日時も記録）
        $correction->status = 'approved';
        $correction->approved_at = now();
        $correction->save();

        return redirect()
            ->route('admin.corrections.list', ['status' => 'approved'])
            ->with('status', '修正申請を承認しました。');
    }
}
