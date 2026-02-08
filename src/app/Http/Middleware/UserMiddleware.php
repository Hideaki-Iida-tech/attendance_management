<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class UserMiddleware
{
    /**
     * 一般ユーザー向けルートへのアクセスを制御するミドルウェア。
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse) $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {

        // 未ログインの場合
        if (!auth()->check()) {
            return redirect('/login'); // ログイン画面（一般ユーザー）へ
        }

        /** @var \App\Models\User $user */
        $user = auth()->user();

        // 一般ユーザーではなく管理者でログイン済みの場合
        if ($user->isAdmin()) {
            return redirect('/admin/attendance/list'); // 勤怠一覧画面（管理者）へ
        }

        // 一般ユーザーでログインの場合
        return $next($request); // 処理続行
    }
}
