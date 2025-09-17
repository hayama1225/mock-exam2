<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;

class StaffController extends Controller
{
    /**
     * スタッフ一覧（一般ユーザーの氏名・メールを表示）
     * 要件: FN041, FN042
     */
    public function index()
    {
        // 一般ユーザー想定：adminsテーブルと分離されている前提なので users 全件でOK
        // もしロールがある環境でも、最小実装として全件表示 → 後続要件で条件付与可能
        $users = User::select('id', 'name', 'email')
            ->orderBy('id')
            ->get();

        return view('admin.staff.index', compact('users'));
    }
}
