<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        // ★評価対象なので必ずこの文言
        return [
            'email.required'    => 'メールアドレスを入力してください',
            'password.required' => 'パスワードを入力してください',
        ];
    }
}
