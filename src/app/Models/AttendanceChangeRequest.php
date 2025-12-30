<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\ApplicationStatus;

class AttendanceChangeRequest extends Model
{
    use HasFactory;

    /**
     * マスアサインメントを許可する属性。
     *
     * フォーム入力やリクエストデータから
     * 一括代入（create / update）してよいカラムを定義する。
     */
    protected $fillable = [
        'user_id',
        'attendance_id',
        'work_date',
        'status',
        'reason',
        'reviewed_by',
        'reviewed_at',
        'review_comment',
    ];

    /**
     * モデル属性の型キャスト定義。
     *
     * attendance_change_requests テーブルの status カラムを
     * ApplicationStatus（enum）として扱うための設定。
     */
    protected $casts = [
        'status' => ApplicationStatus::class,
    ];
}
