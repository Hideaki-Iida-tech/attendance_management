<?php

namespace App\Enums;

/**
 * 勤怠申請の状態を表す Enum。
 *
 * Application（勤怠申請）の進行状態を定義する。
 * 数値（int）をバックエンドの永続化値として持つ backed enum であり、
 * データベースの status カラムと 1 対 1 で対応する。
 *
 * - PENDING  : 申請中（未承認）
 * - APPROVED : 承認済み
 */
enum ApplicationStatus: int
{
    case PENDING = 0; // 申請中
    case APPROVED = 1; // 承認
}
