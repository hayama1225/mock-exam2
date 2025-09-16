<?php

namespace Database\Factories;

use App\Models\AttendanceCorrection;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceCorrectionFactory extends Factory
{
    protected $model = AttendanceCorrection::class;

    public function definition(): array
    {
        $date = $this->faker->date('Y-m-d');
        return [
            'user_id' => User::factory(),
            'attendance_id' => Attendance::factory(),
            'work_date' => $date,
            'clock_in_at' => "$date 09:00:00",
            'clock_out_at' => "$date 18:00:00",
            'break1_start_at' => "$date 12:00:00",
            'break1_end_at'   => "$date 13:00:00",
            'break2_start_at' => null,
            'break2_end_at'   => null,
            'note' => '遅延のため',
            'status' => 'pending',
        ];
    }

    public function approved(): static
    {
        return $this->state(fn() => ['status' => 'approved']);
    }
}
