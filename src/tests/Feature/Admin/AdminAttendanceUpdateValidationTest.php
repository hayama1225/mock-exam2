<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Admin;
use App\Models\User;
use App\Models\Attendance;

class AdminAttendanceUpdateValidationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 退勤が出勤より前だとバリデーションエラーになる(): void
    {
        $admin = Admin::factory()->create();
        $user  = User::factory()->create();
        $date  = '2025-09-12';

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => $date,
            'clock_in_at' => "$date 09:00:00",
            'clock_out_at' => "$date 18:00:00",
            'status' => 1,
            'total_break_seconds' => 0,
            'work_seconds' => 9 * 3600,
        ]);

        $payload = [
            'clock_in'  => '10:00',
            'clock_out' => '09:30', // ×
            'breaks' => [],
        ];

        $res = $this->actingAs($admin, 'admin')
            ->from(route('admin.attendance.show', $attendance))
            ->put(route('admin.attendance.update', $attendance), $payload);

        $res->assertRedirect(route('admin.attendance.show', $attendance));
        $res->assertSessionHasErrors(['clock_out']);
    }

    /** @test */
    public function 退勤以降の休憩はエラーになる(): void
    {
        $admin = Admin::factory()->create();
        $user  = User::factory()->create();
        $date  = '2025-09-13';

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => $date,
            'clock_in_at' => "$date 09:00:00",
            'clock_out_at' => "$date 18:00:00",
            'status' => 1,
            'total_break_seconds' => 0,
            'work_seconds' => 9 * 3600,
        ]);

        // 休憩開始が退勤より後 → コントローラ側の整合チェックに引っかかる
        $payload = [
            'clock_in'  => '09:00',
            'clock_out' => '18:00',
            'breaks' => [
                ['start' => '18:30', 'end' => '18:45'],
            ],
        ];

        $res = $this->actingAs($admin, 'admin')
            ->from(route('admin.attendance.show', $attendance))
            ->put(route('admin.attendance.update', $attendance), $payload);

        $res->assertRedirect(route('admin.attendance.show', $attendance));
        $res->assertSessionHasErrors(['breaks']);
    }
}
