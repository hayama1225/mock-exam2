<?php

namespace App\Http\Requests\Admin\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // adminミドルウェアで保護済み
    }

    public function rules(): array
    {
        return [
            'clock_in'  => ['required', 'date_format:H:i'],
            'clock_out' => ['required', 'date_format:H:i', 'after:clock_in'],

            'breaks'         => ['array'],
            'breaks.*.start' => ['nullable', 'date_format:H:i'],
            'breaks.*.end'   => ['nullable', 'date_format:H:i'],

            'note' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'clock_in.required'      => '出勤時刻を入力してください',
            'clock_in.date_format'   => '出勤時刻はHH:MM形式で入力してください',
            'clock_out.required'     => '退勤時刻を入力してください',
            'clock_out.date_format'  => '退勤時刻はHH:MM形式で入力してください',
            'clock_out.after'        => '出勤時刻より後の時間を設定してください',

            'breaks.*.start.date_format' => '休憩開始はHH:MM形式で入力してください',
            'breaks.*.end.date_format'   => '休憩終了はHH:MM形式で入力してください',
        ];
    }
}
