<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition(): array
    {
        // デフォルトは日付のみ（時刻はnull）。テストで必要に応じて上書き。
        return [
            'user_id' => User::factory(),
            'work_date' => $this->faker->date('Y-m-d'),
            'clock_in_at' => null,
            'clock_out_at' => null,
            'status' => 0,
            'total_break_seconds' => 0,
            'work_seconds' => 0,
        ];
    }

    // 便利ステート（任意）：出勤済
    public function clockedIn(string $date = null, string $time = '09:00:00'): static
    {
        $date = $date ?? $this->faker->date('Y-m-d');
        return $this->state(fn() => [
            'work_date' => $date,
            'clock_in_at' => "$date $time",
        ]);
    }

    // 退勤済
    public function clockedOut(string $date = null, string $in = '09:00:00', string $out = '18:00:00', int $breakSec = 3600): static
    {
        $date = $date ?? $this->faker->date('Y-m-d');
        $work = max(0, 9 * 3600 - $breakSec);
        return $this->state(fn() => [
            'work_date' => $date,
            'clock_in_at' => "$date $in",
            'clock_out_at' => "$date $out",
            'total_break_seconds' => $breakSec,
            'work_seconds' => $work,
        ]);
    }
}
