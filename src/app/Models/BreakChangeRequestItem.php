<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\ActionType;

class BreakChangeRequestItem extends Model
{
    use HasFactory;

    /**
     * マスアサインメントを許可する属性。
     *
     * フォーム入力やリクエストデータから
     * 一括代入（create / update）してよいカラムを定義する。
     */
    protected $fiiable = [
        'request_id',
        'action',
        'target_break_id',
        'new_break_start_at',
        'new_break_end_at',
        'old_break_start_at',
        'old_break_end_at',
    ];

    /**
     * 属性の型キャスト定義。
     *
     * break_change_request_items テーブルの action カラムを
     * ActionType（enum）として扱うための設定。
     */
    protected $casts = [
        'action' => ActionType::class
    ];
}
