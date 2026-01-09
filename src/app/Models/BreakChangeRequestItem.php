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
    protected $fillable = [
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
     * action カラムは ActionType（enum）として扱うことで、
     * 申請内容の種別（追加 / 変更 / 削除 等）を
     * 型安全かつ可読性の高い形で表現する。
     *
     * new_* / old_* の各時刻カラムは datetime（Carbon）としてキャストし、
     * 時刻の比較・差分計算・フォーマット（format / diff 等）を
     * 安全かつ直感的に行えるようにする。
     */
    protected $casts = [
        'action' => ActionType::class,
        'new_break_start_at' => 'datetime',
        'new_break_end_at' => 'datetime',
        'old_break_start_at' => 'datetime',
        'old_break_end_at' => 'datetime',
    ];

    /**
     * 休憩開始時刻を表示用の文字列（H:i）として取得する。
     *
     * new_break_start_at が存在する場合は「12:00」のような形式で返し、
     * 未設定（null）の場合は null を返す。
     *
     * 勤怠詳細画面など、ビュー層での表示用途を想定した
     * アクセサ。
     *
     * @return string|null
     */
    public function getBreakStartTimeAttribute(): ?string
    {
        return $this->new_break_start_at
            ? $this->new_break_start_at->format('H:i')
            : null;
    }

    /**
     * 休憩終了時刻を表示用の文字列（H:i）として取得する。
     *
     * new_break_end_at が存在する場合は「13:00」のような形式で返し、
     * 未設定（null）の場合は null を返す。
     *
     * 勤怠詳細画面など、ビュー層での表示用途を想定した
     * アクセサ。
     *
     * @return string|null
     */
    public function getBreakEndTimeAttribute(): ?string
    {
        return $this->new_break_end_at
            ? $this->new_break_end_at->format('H:i')
            : null;
    }
}
