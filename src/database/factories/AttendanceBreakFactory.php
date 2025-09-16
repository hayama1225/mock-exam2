<?php

namespace Database\Factories;

use App\Models\AttendanceBreak;
use App\Models\Attendance;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceBreakFactory extends Factory
{
    protected $model = AttendanceBreak::class;

    public function definition(): array
    {
        // ダミー：開始〜終了は連続5〜30分
        $date = $this->faker->date('Y-m-d');
        $start = $date . ' ' . $this->faker->time('H:i:s');
        $minutes = $this->faker->numberBetween(5, 30);
        $end = date('Y-m-d H:i:s', strtotime("$start +$minutes minutes"));

        return [
            'attendance_id' => Attendance::factory(),
            'break_start_at' => $start,
            'break_end_at' => $end,
        ];
    }
}
