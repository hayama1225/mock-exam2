<?php

namespace App\Http\Requests\Admin\Attendance;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class UpdateAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // adminミドルウェアで保護済み
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
            'clock_out.after'        => '出勤時刻より後の時間を設定してください',
            'breaks.*.start.date_format' => '休憩開始はHH:MM形式で入力してください',
            'breaks.*.end.date_format'   => '休憩終了はHH:MM形式で入力してください',
            'note.required'          => '備考を記入してください',
        ];
    }

    /**
     * 追加バリデーション：各休憩は出勤以降・退勤以前、かつ開始 < 終了。
     * エラーは 'breaks' キーに集約（テストの期待に合わせる）。
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $in  = $this->input('clock_in');
            $out = $this->input('clock_out');
            if (!$in || !$out) return;

            // 対象日（ルートのモデルから）
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
                if (!$s || !$e) continue;

                try {
                    $sAt = Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $s);
                    $eAt = Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $e);
                } catch (\Throwable $e) {
                    continue; // 形式は rules() 側で検出
                }

                // 開始<終了、かつ 出勤≦開始、終了≦退勤
                if ($eAt->lte($sAt) || $sAt->lt($inAt) || $eAt->gt($outAt)) {
                    $v->errors()->add('breaks', '休憩時間が不適切な値です');
                    break;
                }
            }
        });
    }
}
