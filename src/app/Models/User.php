<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Enums\UserRole;

/**
 * User モデル
 *
 * このモデルは Laravel のメールアドレス確認機能を有効にするため、
 * Illuminate\Contracts\Auth\MustVerifyEmail インターフェイスを実装しています。
 *
 * MustVerifyEmail を実装することで、ユーザー登録後にメールアドレスの検証が
 * 必須となり、未確認ユーザーはメール認証が完了するまで特定の機能に
 * アクセスできないようフレームワーク側が自動的に制御します。
 *
 * また、メール認証通知の送信、認証状態の判定、認証済みユーザーのみが
 * 利用できるルート保護などが、Laravel 標準の仕組みによって提供されます。
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * マスアサインメントを許可する属性。
     *
     * フォーム入力やリクエストデータから
     * 一括代入（create / update）してよいカラムを定義する。
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * 配列化・JSON変換時に非表示とする属性。
     *
     * パスワードやトークンなどの機密情報が
     * APIレスポンスやログに含まれないようにする。
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * モデル属性のキャスト設定。
     *
     * role カラムは PHP 8.1 の enum（App\Enums\UserRole）として
     * 自動的にキャストされ、型安全なロール判定を可能にする。
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'role' => UserRole::class,
    ];

    /**
     * 管理者ユーザーかどうかを判定する。
     *
     * users.role が UserRole::ADMIN の場合に true を返す。
     *
     * @return bool 管理者の場合 true、一般ユーザーの場合 false
     */
    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }
}
