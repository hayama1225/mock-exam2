<?php

namespace App\Http\Requests\Admin\Attendance;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class UpdateAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'clock_in'       => ['required', 'date_format:H:i'],
            'clock_out'      => ['required', 'date_format:H:i', 'after:clock_in'],
            'breaks'         => ['array'],
            'breaks.*.start' => ['nullable', 'date_format:H:i'],
            'breaks.*.end'   => ['nullable', 'date_format:H:i'],
            'note'           => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'clock_in.required'      => '出勤時刻を入力してください',
            'clock_in.date_format'   => '出勤時刻はHH:MM形式で入力してください',
            'clock_out.required'     => '退勤時刻を入力してください',
            'clock_out.date_format'  => '退勤時刻はHH:MM形式で入力してください',
            // 要件1
            'clock_out.after'        => '出勤時間もしくは退勤時間が不適切な値です',

            'breaks.*.start.date_format' => '休憩開始はHH:MM形式で入力してください',
            'breaks.*.end.date_format'   => '休憩終了はHH:MM形式で入力してください',

            // 要件4
            'note.required'          => '備考を記入してください',
        ];
    }

    /**
     * 追加バリデーション（要件2/3）: 各休憩は 出勤≦開始<終了≦退勤
     * エラーは 'breaks' キーへ集約（テスト期待に合わせる）
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $in  = $this->input('clock_in');
            $out = $this->input('clock_out');
            if (!$in || !$out) return;

            $attendance = $this->route('attendance');
            $date = $attendance ? (string)$attendance->work_date : now()->toDateString();

            try {
                $inAt  = Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $in);
                $outAt = Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $out);
            } catch (\Throwable $e) {
                return;
            }

            foreach ((array)$this->input('breaks', []) as $row) {
                $s = $row['start'] ?? null;
                $e = $row['end'] ?? null;

                // どちらか欠けの場合は「不適切」にまとめる
                if (($s && !$e) || (!$s && $e)) {
                    $v->errors()->add('breaks', '休憩時間が不適切な値です');
                    break;
                }
                if (!$s && !$e) continue;

                try {
                    $sAt = Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $s);
                    $eAt = Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $e);
                } catch (\Throwable $e) {
                    continue; // 形式不正はrules()で拾う
                }

                // 要件2: 開始が出勤より前 or 退勤より後、または 開始>=終了
                if ($sAt->lt($inAt) || $sAt->gt($outAt) || $eAt->lte($sAt)) {
                    $v->errors()->add('breaks', '休憩時間が不適切な値です');
                    break;
                }
                // 要件3: 終了が退勤より後
                if ($eAt->gt($outAt)) {
                    $v->errors()->add('breaks', '休憩時間もしくは退勤時間が不適切な値です');
                    break;
                }
            }
        });
    }
}
