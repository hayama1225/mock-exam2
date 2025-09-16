<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Attendance extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'work_date',
        'clock_in_at',
        'clock_out_at',
        'status',
        'total_break_seconds',
        'work_seconds',
    ];

    protected $casts = [
        'clock_in_at' => 'datetime',
        'clock_out_at' => 'datetime',
        'total_break_seconds' => 'integer',
        'work_seconds' => 'integer',
    ];

    // 状態定義
    public const STATUS_OFF     = 0; // 勤務外
    public const STATUS_WORKING = 1; // 出勤中
    public const STATUS_BREAK   = 2; // 休憩中
    public const STATUS_DONE    = 3; // 退勤済

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function breaks()
    {
        return $this->hasMany(AttendanceBreak::class);
    }

    // 今日のレコード取得用
    public static function forToday(int $userId): ?self
    {
        return static::where('user_id', $userId)
            ->where('work_date', today()->toDateString())
            ->first();
    }
}
