<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Admin;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use Carbon\Carbon;

class AdminAttendanceShowTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 勤怠詳細画面が表示される(): void
    {
        $admin = Admin::factory()->create();
        $user  = User::factory()->create(['name' => '山田太郎', 'email' => 'taro@example.com']);

        $date = '2025-09-10';
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => $date,
            'clock_in_at' => "$date 09:15:00",
            'clock_out_at' => "$date 18:05:00",
            'status' => 1,
            'total_break_seconds' => 3600,
            'work_seconds' => (8 * 3600 - 55 * 60),
        ]);

        AttendanceBreak::create([
            'attendance_id'  => $attendance->id,
            'break_start_at' => "$date 12:00:00",
            'break_end_at'   => "$date 13:00:00",
        ]);

        $res = $this->actingAs($admin, 'admin')
            ->get(route('admin.attendance.show', $attendance));

        $res->assertOk()
            ->assertSee('勤怠詳細')
            ->assertSee('山田太郎')
            ->assertSee('taro@example.com', false) // 念のため
            ->assertSee('09:15')
            ->assertSee('18:05')
            ->assertSee('12:00')
            ->assertSee('13:00');
    }
}
