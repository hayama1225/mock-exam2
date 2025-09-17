<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Admin;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use Carbon\Carbon;

class AdminAttendanceUpdateTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 勤怠と休憩が上書き保存される(): void
    {
        $admin = Admin::factory()->create();
        $user  = User::factory()->create();
        $date  = '2025-09-14';

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => $date,
            'clock_in_at' => "$date 09:00:00",
            'clock_out_at' => "$date 18:00:00",
            'status' => 1,
            'total_break_seconds' => 0,
            'work_seconds' => 9 * 3600,
        ]);

        AttendanceBreak::create([
            'attendance_id'  => $attendance->id,
            'break_start_at' => "$date 12:00:00",
            'break_end_at'   => "$date 12:15:00",
        ]);

        // 更新：勤務 09:30〜19:00 / 休憩2本（60分 + 15分）
        $payload = [
            'clock_in'  => '09:30',
            'clock_out' => '19:00',
            'breaks' => [
                ['start' => '12:00', 'end' => '13:00'],
                ['start' => '16:30', 'end' => '16:45'],
            ],
            'note'      => '管理者修正',
        ];

        $this->actingAs($admin, 'admin')
            ->put(route('admin.attendance.update', $attendance), $payload)
            ->assertRedirect(route('admin.attendance.show', $attendance));

        $attendance->refresh();

        $this->assertEquals("$date 09:30:00", Carbon::parse($attendance->clock_in_at)->format('Y-m-d H:i:s'));
        $this->assertEquals("$date 19:00:00", Carbon::parse($attendance->clock_out_at)->format('Y-m-d H:i:s'));

        // 休憩合計：1時間 + 15分 = 4500秒
        $this->assertSame(4500, (int)$attendance->total_break_seconds);

        // 勤務時間：9:30〜19:00 は 9.5時間=34200秒 → 34200 - 4500 = 29700秒
        $this->assertSame(29700, (int)$attendance->work_seconds);

        $this->assertCount(2, $attendance->breaks); // 置き換え済み
        $this->assertDatabaseHas('attendance_breaks', [
            'attendance_id' => $attendance->id,
            'break_start_at' => "$date 12:00:00",
            'break_end_at'   => "$date 13:00:00",
        ]);
        $this->assertDatabaseHas('attendance_breaks', [
            'attendance_id' => $attendance->id,
            'break_start_at' => "$date 16:30:00",
            'break_end_at'   => "$date 16:45:00",
        ]);
    }
}
