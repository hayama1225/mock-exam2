<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Admin;
use App\Models\User;
use App\Models\Attendance;

class AdminAttendanceShowTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 勤怠詳細画面が表示される()
    {
        $admin = Admin::factory()->create();

        $user = User::factory()->create([
            'name'  => '山田太郎',
            'email' => 'taro@example.com', // 画面では使わないがデータとしては保持
        ]);

        $attendance = Attendance::factory()->create([
            'user_id'      => $user->id,
            'work_date'    => '2025-09-10',
            'clock_in_at'  => '2025-09-10 09:15:00',
            'clock_out_at' => '2025-09-10 18:05:00',
        ]);

        // 休憩 12:00-13:00 を1件だけ登録
        $attendance->breaks()->create([
            'break_start_at' => '2025-09-10 12:00:00',
            'break_end_at'   => '2025-09-10 13:00:00',
        ]);

        $res = $this->actingAs($admin, 'admin')
            ->get(route('admin.attendance.show', $attendance->id));

        $res->assertOk()
            ->assertSee('勤怠詳細')
            ->assertSee('山田太郎')
            // ※メールアドレスはUI要件に無いので検証しない
            ->assertSee('09:15')
            ->assertSee('18:05')
            ->assertSee('12:00')
            ->assertSee('13:00');
    }
}
