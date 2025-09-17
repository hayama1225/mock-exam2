<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\Admin;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminAttendanceUpdateNoteTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 備考が保存されて再表示される()
    {
        $admin = Admin::factory()->create();
        $user  = User::factory()->create();
        $a = Attendance::factory()
            ->clockedOut('2025-09-12', '09:00:00', '18:00:00', 3600)
            ->create(['user_id' => $user->id, 'note' => null]);

        $this->actingAs($admin, 'admin')
            ->put(route('admin.attendance.update', $a), [
                'clock_in'  => '09:10',
                'clock_out' => '18:20',
                'breaks'    => [
                    ['start' => '12:00', 'end' => '13:00'],
                ],
                'note'      => '管理用メモ',
            ])
            ->assertRedirect(route('admin.attendance.show', $a));

        $a->refresh();
        $this->assertSame('管理用メモ', $a->note);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.attendance.show', $a))
            ->assertOk()
            ->assertSee('管理用メモ');
    }
}
