<?php

namespace Tests\Feature\Attendance;

use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    private string $tz = 'Asia/Tokyo';

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::create(2025, 9, 16, 9, 0, 0, $this->tz)); // 09:00 固定
    }

    /** @test */
    public function 出勤前は勤務外と表示される()
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->get(route('attendance.index'))
            ->assertOk()
            ->assertSee('勤務外');
    }

    /** @test */
    public function 一日に一回だけ出勤できる()
    {
        $user = User::factory()->create();

        // 1回目出勤
        $this->actingAs($user)
            ->post(route('attendance.store'), ['action' => 'clock_in'])
            ->assertRedirect()
            ->assertSessionHas('success');

        // 2回目は拒否
        $this->actingAs($user)
            ->post(route('attendance.store'), ['action' => 'clock_in'])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseCount('attendances', 1);
        $att = Attendance::first();
        $this->assertNotNull($att->clock_in_at);
        $this->assertNull($att->clock_out_at);
    }

    /** @test */
    public function 休憩は複数回開始終了できる()
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('attendance.store'), ['action' => 'clock_in']);

        // 1回目休憩
        Carbon::setTestNow(Carbon::create(2025, 9, 16, 12, 0, 0, $this->tz)); // 12:00
        $this->actingAs($user)->post(route('attendance.store'), ['action' => 'start_break'])->assertSessionHas('success');
        Carbon::setTestNow(Carbon::create(2025, 9, 16, 12, 30, 0, $this->tz)); // 12:30
        $this->actingAs($user)->post(route('attendance.store'), ['action' => 'end_break'])->assertSessionHas('success');

        // 2回目休憩
        Carbon::setTestNow(Carbon::create(2025, 9, 16, 15, 0, 0, $this->tz)); // 15:00
        $this->actingAs($user)->post(route('attendance.store'), ['action' => 'start_break'])->assertSessionHas('success');
        Carbon::setTestNow(Carbon::create(2025, 9, 16, 15, 10, 0, $this->tz)); // 15:10
        $this->actingAs($user)->post(route('attendance.store'), ['action' => 'end_break'])->assertSessionHas('success');

        $this->assertEquals(2, AttendanceBreak::count());
    }

    /** @test */
    public function 休憩中は退勤できない()
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('attendance.store'), ['action' => 'clock_in']);

        Carbon::setTestNow(Carbon::create(2025, 9, 16, 12, 0, 0, $this->tz));
        $this->actingAs($user)->post(route('attendance.store'), ['action' => 'start_break']);

        // 休憩中の退勤は禁止
        Carbon::setTestNow(Carbon::create(2025, 9, 16, 12, 5, 0, $this->tz));
        $this->actingAs($user)->post(route('attendance.store'), ['action' => 'clock_out'])
            ->assertSessionHas('error');

        $att = Attendance::first();
        $this->assertNull($att->clock_out_at);
    }

    /** @test */
    public function 退勤時に勤務秒は総休憩秒を差し引いて計算される()
    {
        $user = User::factory()->create();

        // 09:00 出勤
        $this->actingAs($user)->post(route('attendance.store'), ['action' => 'clock_in']);

        // 12:00〜12:30 休憩
        Carbon::setTestNow(Carbon::create(2025, 9, 16, 12, 0, 0, $this->tz));
        $this->actingAs($user)->post(route('attendance.store'), ['action' => 'start_break']);
        Carbon::setTestNow(Carbon::create(2025, 9, 16, 12, 30, 0, $this->tz));
        $this->actingAs($user)->post(route('attendance.store'), ['action' => 'end_break']);

        // 18:00 退勤
        Carbon::setTestNow(Carbon::create(2025, 9, 16, 18, 0, 0, $this->tz));
        $this->actingAs($user)->post(route('attendance.store'), ['action' => 'clock_out'])
            ->assertSessionHas('success');

        $att = Attendance::first();
        $this->assertNotNull($att->clock_out_at);
        // 09:00〜18:00 = 9h = 32400s, 休憩 30m = 1800s → 実働 30600s
        $this->assertSame(1800, (int)$att->total_break_seconds);
        $this->assertSame(30600, (int)$att->work_seconds);
    }

    /** @test */
    public function 退勤後は打刻ボタンが非表示になる()
    {
        $user = User::factory()->create();

        // 出勤 → 退勤まで進める
        $this->actingAs($user)->post(route('attendance.store'), ['action' => 'clock_in']);

        Carbon::setTestNow(Carbon::create(2025, 9, 16, 18, 0, 0, $this->tz));
        $this->actingAs($user)->post(route('attendance.store'), ['action' => 'clock_out']);

        // 画面確認：退勤済バッジは見えるが、打刻ボタン（HTML要素）は出ないことを確認
        // ※ 第2引数 false で「そのままのHTML断片」を探します（エスケープ無効）
        $this->actingAs($user)
            ->get(route('attendance.index'))
            ->assertOk()
            ->assertSee('退勤済')
            ->assertDontSee('<button name="action" value="clock_in"', false)
            ->assertDontSee('<button name="action" value="start_break"', false)
            ->assertDontSee('<button name="action" value="end_break"', false)
            ->assertDontSee('<button name="action" value="clock_out"', false);
    }
}
