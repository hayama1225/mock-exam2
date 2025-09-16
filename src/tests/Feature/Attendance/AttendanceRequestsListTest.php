<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceRequestsListTest extends TestCase
{
    use RefreshDatabase;

    public function test_requests_index_shows_pending_and_approved_tabs()
    {
        $user = User::factory()->create();

        // 自分のデータ
        $a = Attendance::factory()->create(['user_id' => $user->id, 'work_date' => '2025-09-16']);
        AttendanceCorrection::factory()->create([
            'user_id' => $user->id,
            'attendance_id' => $a->id,
            'work_date' => '2025-09-16',
            'status' => 'pending',
            'note' => '遅延のため',
        ]);
        AttendanceCorrection::factory()->approved()->create([
            'user_id' => $user->id,
            'attendance_id' => $a->id,
            'work_date' => '2025-09-15',
            'note' => '私用',
        ]);

        $this->actingAs($user)
            ->get(route('requests.index'))
            ->assertOk()
            ->assertSee('承認待ち')
            ->assertSee('承認済み')
            ->assertSee('遅延のため')
            ->assertSee('私用');
    }

    public function test_requests_detail_is_owned_only()
    {
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();
        $a2 = Attendance::factory()->create(['user_id' => $u2->id, 'work_date' => '2025-09-10']);
        $r2 = AttendanceCorrection::factory()->create(['user_id' => $u2->id, 'attendance_id' => $a2->id, 'work_date' => '2025-09-10']);

        $this->actingAs($u1)
            ->get(route('requests.show', $r2->id))
            ->assertForbidden();
    }

    public function test_requests_detail_shows_fields()
    {
        $u = User::factory()->create();
        $a = Attendance::factory()->create(['user_id' => $u->id, 'work_date' => '2025-09-12']);
        $r = AttendanceCorrection::factory()->create([
            'user_id' => $u->id,
            'attendance_id' => $a->id,
            'work_date' => '2025-09-12',
            'note' => 'テスト理由'
        ]);

        $this->actingAs($u)
            ->get(route('requests.show', $r->id))
            ->assertOk()
            ->assertSee('申請詳細')
            ->assertSee('テスト理由')
            ->assertSee('09:00')   // 出勤
            ->assertSee('12:00');  // 休憩開始
    }
}
