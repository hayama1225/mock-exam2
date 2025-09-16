<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class AttendanceCorrectionTest extends TestCase
{
    use RefreshDatabase;

    private string $tz = 'Asia/Tokyo';

    private function seedAttendance(User $user): Attendance
    {
        return Attendance::factory()->clockedOut('2025-09-16', '09:00:00', '18:00:00', 3600)->create([
            'user_id' => $user->id,
        ]);
    }

    public function test_note_is_required_message()
    {
        $user = User::factory()->create();
        $a = $this->seedAttendance($user);

        $this->actingAs($user)
            ->post(route('attendance.request', ['attendance' => $a->id]), [
                'in' => '09:00',
                'out' => '18:00',
                'b1s' => '12:00',
                'b1e' => '13:00',
                'note' => '', // 未入力
            ])->assertSessionHasErrors(['note' => '備考を記入してください']);
    }

    public function test_clock_in_after_out_message()
    {
        $user = User::factory()->create();
        $a = $this->seedAttendance($user);

        $this->actingAs($user)
            ->post(route('attendance.request', ['attendance' => $a->id]), [
                'in' => '19:00',
                'out' => '18:00',
                'note' => 'テスト',
            ])->assertSessionHasErrors()
            ->assertSessionHasErrors(['out' => '出勤時間もしくは退勤時間が不適切な値です']);
    }

    public function test_break_start_before_in_or_after_out_message()
    {
        $user = User::factory()->create();
        $a = $this->seedAttendance($user);

        // 出勤9:00 退勤18:00 に対して、休憩開始が8:00（前）→エラー
        $this->actingAs($user)
            ->post(route('attendance.request', ['attendance' => $a->id]), [
                'in' => '09:00',
                'out' => '18:00',
                'b1s' => '08:00',
                'b1e' => '08:30',
                'note' => 'テスト',
            ])->assertSessionHasErrors(['b1s' => '休憩時間が不適切な値です']);
    }

    public function test_break_end_after_out_message()
    {
        $user = User::factory()->create();
        $a = $this->seedAttendance($user);

        $this->actingAs($user)
            ->post(route('attendance.request', ['attendance' => $a->id]), [
                'in' => '09:00',
                'out' => '18:00',
                'b1s' => '17:30',
                'b1e' => '19:00', // 終了が退勤より後
                'note' => 'テスト',
            ])->assertSessionHasErrors(['b1e' => '休憩時間もしくは退勤時間が不適切な値です']);
    }

    public function test_pending_blocks_editing()
    {
        $user = User::factory()->create();
        $a = $this->seedAttendance($user);

        AttendanceCorrection::create([
            'user_id' => $user->id,
            'attendance_id' => $a->id,
            'work_date' => $a->work_date,
            'note' => '既存申請',
            'status' => 'pending',
        ]);

        // 2重申請は不可
        $this->actingAs($user)
            ->post(route('attendance.request', ['attendance' => $a->id]), [
                'in' => '09:00',
                'out' => '18:00',
                'note' => '二重申請',
            ])->assertSessionHas('error', '承認待ちのため修正はできません。');

        // 画面にもメッセージが出る
        $this->actingAs($user)
            ->get(route('attendance.detail', ['attendance' => $a->id]))
            ->assertOk()
            ->assertSee('承認待ちのため修正はできません。');
    }
}
