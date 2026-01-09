<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class UserMiddleware
{
    /**
     * 一般ユーザー向けルートへのアクセスを制御するミドルウェア。
     *
     * 本ミドルウェアは、一般ユーザー専用画面に対して次の判定を行う。
     *
     * - 未ログインの場合：
     *   一般ユーザー用ログイン画面（/login）へリダイレクトする。
     *
     * - 管理者としてログイン済みの場合：
     *   一般ユーザー画面へのアクセスを禁止し、
     *   管理者用勤怠一覧画面（/admin/attendance/list）へリダイレクトする。
     *
     * - 一般ユーザーとしてログイン済みの場合：
     *   アクセスを許可し、後続の処理へ進む。
     *
     * 管理者と一般ユーザーの画面混在を防ぎ、
     * 誤操作や権限外アクセスを防止することを目的とする。
     *
     * @param  \Illuminate\Http\Request  $request
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

        // 一般ユーザーではなく管理者でログイン済みの場合
        if ($user->isAdmin()) {
            return redirect('/admin/attendance/list'); // 勤怠一覧画面（管理者）へ
        }

        // 一般ユーザーでログインの場合
        return $next($request); // 処理続行
    }
}
