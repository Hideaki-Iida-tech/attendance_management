<?php

namespace App\Enums;

/**
 * ユーザーの権限（ロール）を表す列挙型。
 *
 * users テーブルの role カラムと対応し、
 * アプリケーション内で「一般ユーザー / 管理者」を
 * 型安全に判定するために使用する。
 *
 * @see \App\Models\User
 */
enum UserRole: int
{
    /**
     * 一般ユーザー
     *
     * 通常の勤怠登録・閲覧などの操作が可能。
     */
    case USER = 0;

    /**
     * 管理者ユーザー
     *
     * 全ユーザーの勤怠管理、承認処理、管理画面へのアクセスが可能。
     */
    case ADMIN = 1;
}
