<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use App\Models\User;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'    => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'    => 'メールアドレスを入力してください',
            'password.required' => 'パスワードを入力してください',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $email    = (string) $this->input('email');
            $password = (string) $this->input('password');

            $emailEmpty = ($email === '');
            $passEmpty  = ($password === '');

            if (!$emailEmpty && $passEmpty) {
                $user = User::where('email', $email)->first();
                if (!$user) {
                    $validator->errors()->add('email', 'ログイン情報が登録されていません');
                }
                return;
            }

            if ($emailEmpty && !$passEmpty) {
                $validator->errors()->add('email', 'ログイン情報が登録されていません');
                return;
            }
        });
    }
}
