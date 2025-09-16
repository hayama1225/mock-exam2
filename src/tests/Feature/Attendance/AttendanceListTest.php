<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    private string $tz = 'Asia/Tokyo';

    public function test_list_shows_current_month_by_default()
    {
        Carbon::setTestNow(Carbon::create(2025, 9, 16, 9, 0, 0, $this->tz));
        $user = User::factory()->create();

        // 当月のダミー1件
        $a = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2025-09-16',
            'clock_in_at' => '2025-09-16 09:00:00',
            'clock_out_at' => '2025-09-16 18:00:00',
            'total_break_seconds' => 3600,
            'work_seconds' => 8 * 3600,
        ]);

        $this->actingAs($user)
            ->get(route('attendance.list'))
            ->assertOk()
            ->assertSee('2025/09')
            ->assertSee('09/16')
            ->assertSee('09:00')
            ->assertSee('18:00')
            ->assertSee('1:00')  // 休憩
            ->assertSee('8:00'); // 合計
    }

    public function test_list_prev_and_next_month_navigation()
    {
        Carbon::setTestNow(Carbon::create(2025, 9, 1, 9, 0, 0, $this->tz));
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('attendance.list', ['ym' => '2025-08']))
            ->assertOk()
            ->assertSee('2025/08');

        $this->actingAs($user)
            ->get(route('attendance.list', ['ym' => '2025-10']))
            ->assertOk()
            ->assertSee('2025/10');
    }

    public function test_detail_page_is_accessible_from_list_and_shows_breaks()
    {
        Carbon::setTestNow(Carbon::create(2025, 9, 16, 9, 0, 0, $this->tz));
        $user = User::factory()->create();

        $a = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2025-09-16',
            'clock_in_at' => '2025-09-16 09:00:00',
            'clock_out_at' => '2025-09-16 18:00:00',
            'total_break_seconds' => 1800,
            'work_seconds' => 30600,
        ]);

        AttendanceBreak::factory()->create([
            'attendance_id' => $a->id,
            'break_start_at' => '2025-09-16 12:00:00',
            'break_end_at'   => '2025-09-16 12:30:00',
        ]);

        $this->actingAs($user)
            ->get(route('attendance.detail', ['attendance' => $a->id]))
            ->assertOk()
            ->assertSee('勤怠詳細')
            ->assertSee('2025/09/16')
            ->assertSee('12:00:00')
            ->assertSee('12:30:00');
    }

    public function test_cannot_view_others_attendance_detail()
    {
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();
        $a = Attendance::factory()->create([
            'user_id' => $u2->id,
            'work_date' => '2025-09-10',
        ]);
        $this->actingAs($u1)
            ->get(route('attendance.detail', ['attendance' => $a->id]))
            ->assertForbidden();
    }
}
