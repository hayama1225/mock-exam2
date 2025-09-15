<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                  => ['required', 'string', 'max:255'],
            'email'                 => ['required', 'string', 'email:filter', 'max:255', 'unique:users,email'],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        // ★評価対象なので絶対にこの文言に固定
        return [
            'name.required'     => 'お名前を入力してください',
            'email.required'    => 'メールアドレスを入力してください',
            'password.required' => 'パスワードを入力してください',

            'password.min'      => 'パスワードは8文字以上で入力してください',

            'password.confirmed' => 'パスワードと一致しません',
        ];
    }
}
