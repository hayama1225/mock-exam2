<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Admin;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use Carbon\Carbon;

class AdminCorrectionRequestTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;
    private User $user;
    private Attendance $attendance;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
        ]);

        $this->user = User::factory()->create([
            'name' => 'user1',
            'email' => 'user1@example.com',
        ]);

        $this->attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'work_date' => Carbon::parse('2025-09-23'),
        ]);
    }

    /** @test */
    public function 管理者は承認待ち一覧を確認できる()
    {
        AttendanceCorrection::factory()->create([
            'attendance_id' => $this->attendance->id,
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get('/stamp_correction_request/list?status=pending')
            ->assertOk()
            ->assertSee('承認待ち')
            ->assertSee($this->user->name);
    }

    /** @test */
    public function 管理者は承認済み一覧を確認できる()
    {
        AttendanceCorrection::factory()->create([
            'attendance_id' => $this->attendance->id,
            'status' => 'approved',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get('/stamp_correction_request/list?status=approved')
            ->assertOk()
            ->assertSee('承認済み')
            ->assertSee($this->user->name);
    }

    /** @test */
    public function 管理者は修正申請の詳細を確認できる()
    {
        $correction = AttendanceCorrection::factory()->create([
            'attendance_id' => $this->attendance->id,
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get("/stamp_correction_request/approve/{$correction->id}")
            ->assertOk()
            ->assertSee((string)$correction->id)
            ->assertSee($this->user->name)
            ->assertSee('承認待ち');
    }

    /** @test */
    public function 管理者は修正申請を承認できる()
    {
        $correction = AttendanceCorrection::factory()->create([
            'attendance_id' => $this->attendance->id,
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->post("/stamp_correction_request/approve/{$correction->id}")
            ->assertRedirect('/stamp_correction_request/list?status=approved');

        $this->assertDatabaseHas('attendance_corrections', [
            'id' => $correction->id,
            'status' => 'approved',
        ]);
    }
}
