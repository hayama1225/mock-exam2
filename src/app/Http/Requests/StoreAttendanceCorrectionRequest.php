<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttendanceCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * 送信値を HH:MM に正規化してから rules() を適用する
     * 例:
     *  - "5:3"   -> "05:03"
     *  - "930"   -> "09:30"
     *  - "5"     -> "05:00"
     *  - "５：３" -> "05:03"  (全角→半角)
     * 範囲外(25:00/07:99等)や不明形式は触らず返し、従来のregexで弾く
     */
    protected function prepareForValidation(): void
    {
        $fields = ['in', 'out', 'b1s', 'b1e', 'b2s', 'b2e'];
        $normalized = [];

        foreach ($fields as $f) {
            $normalized[$f] = $this->normalizeTime($this->input($f));
        }

        $this->merge($normalized);
    }

    private function normalizeTime($value): ?string
    {
        if ($value === null) return null;
        $v = trim((string)$value);
        if ($v === '') return '';

        // 全角英数・コロンを半角へ
        if (function_exists('mb_convert_kana')) {
            $v = mb_convert_kana($v, 'na'); // 数字・英字
        }
        $v = str_replace('：', ':', $v);

        $h = null;
        $m = null;

        // 1) H(:)M
        if (preg_match('/^(\d{1,2}):(\d{1,2})$/', $v, $mch)) {
            $h = (int)$mch[1];
            $m = (int)$mch[2];
        }
        // 2) 3～4桁 "930"/"0930"
        elseif (preg_match('/^\d{3,4}$/', $v)) {
            if (strlen($v) === 3) {
                $h = (int)$v[0];
                $m = (int)substr($v, 1, 2);
            } else {
                $h = (int)substr($v, 0, 2);
                $m = (int)substr($v, 2, 2);
            }
        }
        // 3) 1～2桁 "5" -> 05:00
        elseif (preg_match('/^\d{1,2}$/', $v)) {
            $h = (int)$v;
            $m = 0;
        } else {
            return $value; // 不明形式は触らない → 従来ルールが弾く
        }

        // 範囲チェック（24h）
        if ($h < 0 || $h > 23 || $m < 0 || $m > 59) {
            return $value; // 触らず返す
        }

        return sprintf('%02d:%02d', $h, $m);
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
        return [
            'note.required' => '備考を記入してください',
            'in.regex'  => '時刻はHH:MM形式で入力してください',
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

            // 2) 休憩の妥当性（出勤前/退勤後/逆転）
            foreach ([['s' => $b1sD, 'e' => $b1eD], ['s' => $b2sD, 'e' => $b2eD]] as $pair) {
                if ($pair['s']) {
                    if ($cin && $pair['s']->lt($cin)) {
                        $v->errors()->add('b1s', '休憩時間が不適切な値です');
                    }
                    if ($cout && $pair['s']->gt($cout)) {
                        $v->errors()->add('b1s', '休憩時間が不適切な値です');
                    }
                }
                if ($pair['e'] && $cout && $pair['e']->gt($cout)) {
                    $v->errors()->add('b1e', '休憩時間もしくは退勤時間が不適切な値です');
                }
                if ($pair['s'] && $pair['e'] && $pair['s']->gte($pair['e'])) {
                    $v->errors()->add('b1e', '休憩時間が不適切な値です');
                }
            }
        });
    }
}
