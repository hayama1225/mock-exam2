<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Models\Admin;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceBreak;

class DummyDataSeeder extends Seeder
{
    public function run(): void
    {
        // 管理者（既にいれば更新）
        Admin::updateOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => '管理者', 'password' => Hash::make('password123')]
        );

        // 一般ユーザー 8名
        $users = User::factory()->count(8)->create();

        // 当月の1日〜末日（翌日を含めない）
        $start = Carbon::now()->startOfMonth();
        $end   = Carbon::now()->endOfMonth();
        $period = CarbonPeriod::create($start, $end); // ← ここをCarbonPeriodに

        foreach ($users as $user) {
            foreach ($period as $day) {
                /** @var Carbon $day */
                if ($day->isWeekend()) {
                    continue;
                }

                // 7割の確率で出勤
                if (mt_rand(1, 100) > 70) {
                    continue;
                }

                // 出勤 9:00〜10:00 のどこか
                $in  = $day->copy()->setTime(9, 0)->addMinutes(mt_rand(0, 60));
                // 退勤 17:30〜20:00 のどこか
                $out = $day->copy()->setTime(18, 0)->addMinutes(mt_rand(-30, 120));

                // 休憩（ランチ45〜60分）
                $b1s = $day->copy()->setTime(12, 0)->addMinutes(mt_rand(-10, 10));
                $b1e = $b1s->copy()->addMinutes(mt_rand(45, 60));

                // たまに午後の小休憩（0〜1回）
                $hasB2 = mt_rand(1, 100) <= 35;
                $b2s = $hasB2 ? $day->copy()->setTime(15, 0)->addMinutes(mt_rand(-10, 10)) : null;
                $b2e = $hasB2 ? $b2s->copy()->addMinutes(mt_rand(10, 20)) : null;

                $totalBreak =
                    $b1e->diffInSeconds($b1s) +
                    ($hasB2 ? $b2e->diffInSeconds($b2s) : 0);

                $workSeconds = max(0, $out->diffInSeconds($in) - $totalBreak);

                // 勤怠レコード
                $attendance = Attendance::create([
                    'user_id'             => $user->id,
                    'work_date'           => $day->toDateString(),
                    'clock_in_at'         => $in,
                    'clock_out_at'        => $out,
                    'status'              => 1,
                    'total_break_seconds' => $totalBreak,
                    'work_seconds'        => $workSeconds,
                ]);

                // 休憩レコード（あなたのFactoryに合わせてカラム名は break_start_at / break_end_at）
                AttendanceBreak::create([
                    'attendance_id'  => $attendance->id,
                    'break_start_at' => $b1s,
                    'break_end_at'   => $b1e,
                ]);

                if ($hasB2) {
                    AttendanceBreak::create([
                        'attendance_id'  => $attendance->id,
                        'break_start_at' => $b2s,
                        'break_end_at'   => $b2e,
                    ]);
                }
            }
        }

        $this->command?->info('Dummy data created: admins, users, attendances, breaks (current month).');
    }
}
