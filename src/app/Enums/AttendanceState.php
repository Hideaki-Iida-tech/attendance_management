<?php

namespace App\Enums;

/**
 * 勤怠の現在状態を表す unit Enum。
 *
 * 打刻画面において、出勤・休憩・退勤の可否判定に使用する。
 */

enum AttendanceState
{
    case OFF_DUTY; // 勤務外（未出勤）
    case WORKING; // 出勤中
    case ON_BREAK; // 休憩中
    case FINISHED; // 退勤済
}
