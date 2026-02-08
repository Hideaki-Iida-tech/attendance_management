<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleViewSwitchMiddleware
{
    /**
     * ユーザーの権限（管理者／一般）に応じて表示する画面を切り替えるミドルウェア。
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $path = $request->path();

        if (
            $path === "attendance/{$request->route('id')}" ||
            $path === "stamp_correction_request/list"
        ) {
            if ($user->isAdmin()) {
                $request->attributes->set('is_admin_context', true);
            } else {
                $request->attributes->set('is_admin_context', false);
            }
            return $next($request);
        }

        abort(500, 'Invalid Request');
    }
}
