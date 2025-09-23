<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Admin;
use App\Models\Attendance;
use Carbon\Carbon;

class AdminStaffMonthlyTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Admin::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
        ]);
        $this->user = User::factory()->create([
            'name' => '山田太郎',
            'email' => 'taro@example.com',
        ]);
    }

    /** @test */
    public function 管理者はスタッフの氏名とメールを確認できる()
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.attendance.staff', ['user' => $this->user->id]))
            ->assertOk()
            ->assertSee($this->user->name)
            ->assertSee($this->user->email);
    }

    /** @test */
    public function 管理者はユーザーの勤怠情報を正しく表示できる()
    {
        $date = Carbon::parse('2025-09-10');
        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'work_date' => $date->toDateString(),
            'clock_in_at' => $date->copy()->setTime(9, 0),
            'clock_out_at' => $date->copy()->setTime(18, 0),
            'total_break_seconds' => 3600,
            'work_seconds' => 8 * 3600,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.attendance.staff', ['user' => $this->user->id, 'month' => '2025-09']))
            ->assertOk()
            ->assertSee('09:00')
            ->assertSee('18:00')
            ->assertSee('1:00')
            ->assertSee('8:00');
    }

    /** @test */
    public function 前月ボタンを押すと前月が表示される()
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.attendance.staff', ['user' => $this->user->id, 'month' => '2025-09']))
            ->assertOk()
            ->assertSee(route('admin.attendance.staff', ['user' => $this->user->id, 'month' => '2025-08']));
    }

    /** @test */
    public function 翌月ボタンを押すと翌月が表示される()
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.attendance.staff', ['user' => $this->user->id, 'month' => '2025-09']))
            ->assertOk()
            ->assertSee(route('admin.attendance.staff', ['user' => $this->user->id, 'month' => '2025-10']));
    }

    /** @test */
    public function 詳細ボタンを押すと勤怠詳細画面に遷移できる()
    {
        $date = Carbon::parse('2025-09-15');
        $attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'work_date' => $date->toDateString(),
            'clock_in_at' => $date->copy()->setTime(9, 0),
            'clock_out_at' => $date->copy()->setTime(18, 0),
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.attendance.staff', ['user' => $this->user->id, 'month' => '2025-09']))
            ->assertOk()
            ->assertSee(route('admin.attendance.show', ['attendance' => $attendance->id]));
    }

    /** @test */
    public function CSV出力がダウンロードできる()
    {
        $this->actingAs($this->admin, 'admin');

        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'work_date' => '2025-09-01',
            'work_seconds' => 8 * 3600,
            'total_break_seconds' => 3600,
        ]);

        $res = $this->get(route('admin.attendance.staff.csv', [
            'user'  => $this->user->id,
            'month' => '2025-09',
        ]));

        $res->assertOk();
        $res->assertHeader('content-type', 'text/csv; charset=Shift_JIS');

        $utf8 = mb_convert_encoding($res->streamedContent(), 'UTF-8', 'SJIS-win');
        $this->assertStringContainsString('スタッフ名', $utf8);
        $this->assertStringContainsString('日付', $utf8);
    }

    /** @test */
    public function 詳細画面の一覧へ戻るリンクはスタッフ別月次勤怠へ戻る()
    {
        $date = \Carbon\Carbon::parse('2025-09-01');
        $attendance = \App\Models\Attendance::factory()->create([
            'user_id'              => $this->user->id,
            'work_date'            => $date->toDateString(),
            'clock_in_at'          => $date->copy()->setTime(9, 0),
            'clock_out_at'         => $date->copy()->setTime(18, 0),
            'work_seconds'         => 8 * 3600,
            'total_break_seconds'  => 3600,
        ]);

        $this->actingAs($this->admin, 'admin');

        $expectedUrl = route('admin.attendance.staff', [
            'user'  => $this->user->id,
            'month' => $date->format('Y-m'),
        ]);

        $this->get(route('admin.attendance.show', ['attendance' => $attendance->id]))
            ->assertOk()
            ->assertSee(e($expectedUrl));
    }
}
