<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AttendanceCorrection extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'attendance_id',
        'work_date',
        'clock_in_at',
        'clock_out_at',
        'break1_start_at',
        'break1_end_at',
        'break2_start_at',
        'break2_end_at',
        'note',
        'status',
    ];

    protected $casts = [
        'clock_in_at' => 'datetime',
        'clock_out_at' => 'datetime',
        'break1_start_at' => 'datetime',
        'break1_end_at'   => 'datetime',
        'break2_start_at' => 'datetime',
        'break2_end_at'   => 'datetime',
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
}
