<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleViewSwitchMiddleware
{
    /**
     * ユーザーの権限（管理者／一般）に応じて表示する画面を切り替えるミドルウェア。
     *
     * 同一URLに対して、ログインユーザーの権限（isAdmin）を判定し、
     * 管理者には管理者用画面、一般ユーザーには一般ユーザー用画面を返す。
     *
     * 本ミドルウェアは以下のURLを対象とする：
     * - /attendance/{id}
     * - /stamp_correction_request/list
     *
     * ルート処理本体は実行されず、本ミドルウェア内で
     * 適切な View を直接返却する。
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $path = $request->path();

        if ($path === "attendance/{$request->route('id')}") {

            if ($user->isAdmin()) {
                return response()->view(
                    'attendance.admin.show',
                    [
                        'layout' => 'layouts.admin-menu',
                        'id' => $request->route('id'),
                    ]
                );
            }

            return response()->view(
                'attendance.show',
                [
                    'layout' => 'layouts.user-menu',
                    'id' => $request->route('id'),
                ]
            );
        }

        if ($path === 'stamp_correction_request/list') {

            if ($user->isAdmin()) {
                return response()->view(
                    'applications.admin.index',
                    [
                        'layout' => 'layouts.admin-menu',
                    ]
                );
            }

            return response()->view(
                'applications.index',
                [
                    'layout' => 'layouts.user-menu',
                ]
            );
        }
        return $next($request);
    }
}
