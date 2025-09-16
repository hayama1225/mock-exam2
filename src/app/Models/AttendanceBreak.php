<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceBreak extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'attendance_id',
        'break_start_at',
        'break_end_at',
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
}
