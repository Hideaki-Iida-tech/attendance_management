<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreakTime extends Model
{
    use HasFactory;

    /**
     * マスアサインメントを許可する属性。
     *
     * フォーム入力やリクエストデータから
     * 一括代入（create / update）してよいカラムを定義する。
     */
    protected $fillable = [
        'attendance_id',
        'break_start_at',
        'break_end_at',
    ];

    /**
     * 使用するテーブル名を明示的に指定する。
     *
     * モデル名が BreakTime のため、Laravel の規約では
     * break_times テーブルが推測されるが、
     * 実際のテーブル名は breaks であるため明示的に指定している。
     */
    protected $table = 'breaks';

    /**
     * Cast attributes to native types.
     *
     * break_start_at / break_end_at を datetime（Carbon）として扱うことで、
     * 時刻の比較・フォーマット（format / diff / translatedFormat 等）を
     * 安全かつ直感的に行えるようにする。
     *
     * @var array<string, string>
     */
    protected $casts = [
        'break_start_at'  => 'datetime',
        'break_end_at' => 'datetime',
    ];

    /**
     * 休憩開始時刻を表示用の文字列（H:i）として取得する。
     *
     * break_start_at が存在する場合は「12:00」のような形式で返し、
     * 未設定（null）の場合は null を返す。
     *
     * 勤怠詳細画面など、ビュー層での表示用途を想定した
     * アクセサ。
     *
     * @return string|null
     */
    public function getBreakStartTimeAttribute(): ?string
    {
        return $this->break_start_at
            ? $this->break_start_at->format('H:i')
            : null;
    }

    /**
     * 休憩終了時刻を表示用の文字列（H:i）として取得する。
     *
     * break_end_at が存在する場合は「13:00」のような形式で返し、
     * 未設定（null）の場合は null を返す。
     *
     * 勤怠詳細画面など、ビュー層での表示用途を想定した
     * アクセサ。
     *
     * @return string|null
     */
    public function getBreakEndTimeAttribute(): ?string
    {
        return $this->break_end_at
            ? $this->break_end_at->format('H:i')
            : null;
    }
}
