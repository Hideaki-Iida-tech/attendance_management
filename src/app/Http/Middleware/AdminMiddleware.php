<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    /**
     * 管理者専用ルートへのアクセスを制御するミドルウェア。
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
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

        // 管理者ではなく一般ユーザーでログイン済みの場合
        if (!$user->isAdmin()) {
            return redirect('/attendance'); // 勤怠登録画面（一般ユーザー）へ
        }

        // 管理者でログインの場合
        return $next($request); // 処理続行
    }
}
