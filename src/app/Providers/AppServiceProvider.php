<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
// 追加
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // ★ これを追加
        Schema::defaultStringLength(191);
    }
}
