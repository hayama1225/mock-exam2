<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    // timestampsは使わない（ER図・仕様に合わせる）
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
