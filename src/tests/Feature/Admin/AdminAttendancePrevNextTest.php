<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendancePrevNextTest extends TestCase
{
    use RefreshDatabase;

    private function makeTriad(): array
    {
        $admin = Admin::factory()->create();
        $user  = User::factory()->create(['name' => '山田太郎', 'email' => 'taro@example.com']);

        $a1 = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2025-09-10',
            'clock_in_at' => '2025-09-10 09:00:00',
            'clock_out_at' => '2025-09-10 18:00:00',
        ]);
        $a2 = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2025-09-11',
            'clock_in_at' => '2025-09-11 09:00:00',
            'clock_out_at' => '2025-09-11 18:00:00',
        ]);
        $a3 = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2025-09-12',
            'clock_in_at' => '2025-09-12 09:00:00',
            'clock_out_at' => '2025-09-12 18:00:00',
        ]);

        return [$admin, $a1, $a2, $a3];
    }

    /** @test */
    public function 中日でも勤怠詳細が正しく表示される()
    {
        [$admin, $a1, $a2, $a3] = $this->makeTriad();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.attendance.show', $a2->id))
            ->assertOk()
            ->assertSee('勤怠詳細')
            ->assertSee('山田太郎')
            ->assertSee('2025年', false)
            ->assertSee('9月11日', false)
            ->assertSee('09:00')
            ->assertSee('18:00');
    }

    /** @test */
    public function 端の日付でも勤怠詳細が表示される()
    {
        [$admin, $a1, $a2, $a3] = $this->makeTriad();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.attendance.show', $a1->id))
            ->assertOk()
            ->assertSee('勤怠詳細');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.attendance.show', $a3->id))
            ->assertOk()
            ->assertSee('勤怠詳細');
    }
}
