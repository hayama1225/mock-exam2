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

    /** @test */
    public function 申請一覧は承認待ち承認済みタブを表示する()
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

    /** @test */
    public function 申請詳細は本人のみ閲覧できる()
    {
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();
        $a2 = Attendance::factory()->create(['user_id' => $u2->id, 'work_date' => '2025-09-10']);
        $r2 = AttendanceCorrection::factory()->create(['user_id' => $u2->id, 'attendance_id' => $a2->id, 'work_date' => '2025-09-10']);

        $this->actingAs($u1)
            ->get(route('requests.show', $r2->id))
            ->assertForbidden();
    }

    /** @test */
    public function 申請詳細は必要な項目が表示される()
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
