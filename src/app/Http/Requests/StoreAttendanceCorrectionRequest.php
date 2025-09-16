<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttendanceCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            // HH:MM 入力（空許可）
            'in'  => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
            'out' => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
            'b1s' => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
            'b1e' => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
            'b2s' => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
            'b2e' => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
            'note' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        // 要件の日本語メッセージ
        return [
            'note.required' => '備考を記入してください',
            'in.regex' => '時刻はHH:MM形式で入力してください',
            'out.regex' => '時刻はHH:MM形式で入力してください',
            'b1s.regex' => '時刻はHH:MM形式で入力してください',
            'b1e.regex' => '時刻はHH:MM形式で入力してください',
            'b2s.regex' => '時刻はHH:MM形式で入力してください',
            'b2e.regex' => '時刻はHH:MM形式で入力してください',
        ];
    }

    /**
     * 追加の相関チェック（FN029）
     */
    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            $in  = $this->input('in');
            $out = $this->input('out');
            $b1s = $this->input('b1s');
            $b1e = $this->input('b1e');
            $b2s = $this->input('b2s');
            $b2e = $this->input('b2e');

            // 基準日（詳細対象日の 00:00）でDateTime化
            $date = $this->route('attendance')->work_date ?? now('Asia/Tokyo')->toDateString();
            $tz = 'Asia/Tokyo';
            $toDT = function ($hm) use ($date, $tz) {
                if (!$hm) return null;
                return \Carbon\Carbon::createFromFormat('Y-m-d H:i', "$date $hm", $tz);
            };

            $cin  = $toDT($in);
            $cout = $toDT($out);
            $b1sD = $toDT($b1s);
            $b1eD = $toDT($b1e);
            $b2sD = $toDT($b2s);
            $b2eD = $toDT($b2e);

            // 1) 出勤/退勤の前後
            if ($cin && $cout && $cin->gte($cout)) {
                $v->errors()->add('out', '出勤時間もしくは退勤時間が不適切な値です');
            }

            // 2) 休憩開始が出勤より前 or 退勤より後
            foreach ([['s' => $b1sD, 'e' => $b1eD], ['s' => $b2sD, 'e' => $b2eD]] as $pair) {
                if ($pair['s']) {
                    if ($cin && $pair['s']->lt($cin)) {
                        $v->errors()->add('b1s', '休憩時間が不適切な値です');
                    }
                    if ($cout && $pair['s']->gt($cout)) {
                        $v->errors()->add('b1s', '休憩時間が不適切な値です');
                    }
                }
                // 3) 休憩終了が退勤より後
                if ($pair['e'] && $cout && $pair['e']->gt($cout)) {
                    $v->errors()->add('b1e', '休憩時間もしくは退勤時間が不適切な値です');
                }
                // 開始より終了が前
                if ($pair['s'] && $pair['e'] && $pair['s']->gte($pair['e'])) {
                    $v->errors()->add('b1e', '休憩時間が不適切な値です');
                }
            }
        });
    }
}
