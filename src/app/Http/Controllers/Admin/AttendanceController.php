<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use Carbon\Carbon;

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
}
