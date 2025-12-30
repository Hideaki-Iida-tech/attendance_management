<?php

namespace App\Enums;

/**
 * 休憩レコードに対する操作種別を表す Enum。
 *
 * 勤怠管理システムにおいて、休憩修正申請の明細（break_change_request_items）における操作種別
 * 実行された操作内容を区別するために使用する。
 * backed enum（int）として定義されており、
 * データベースの action カラムと 1 対 1 で対応する。
 *
 * - ADD    : 休憩レコード追加
 * - UPDATE : 休憩レコード更新
 * - DELETE : 休憩レコード削除
 */
enum ActionType: int
{
    case ADD = 0; //休憩レコード追加
    case UPDATE = 1; // 休憩レコード更新
    case DELETE = 2; // 休憩レコード削除
}
