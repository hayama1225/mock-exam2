<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;

/**
 * @method bool hasVerifiedEmail()
 * @method void sendEmailVerificationNotification()
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, Notifiable, HasFactory;
    // ★ 追加：メール認証の実装（これが無いとメソッド未定義）
    use MustVerifyEmailTrait;

    // timestampsは使わない
    public $timestamps = false;

    protected $fillable = [
        'name',
        'email',
        'password',
        // 将来の管理者判定用。今は未使用でもOK（ダミーデータ要件に備える）
        'is_admin',
        'email_verified_at',
        'remember_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
}
