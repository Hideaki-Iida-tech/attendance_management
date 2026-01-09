<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceChangeRequestItem extends Model
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
        'new_clock_in_at',
        'new_clock_out_at',
        'old_clock_in_at',
        'old_clock_out_at',
    ];

    /**
     * Cast attributes to native types.
     * 
     * new_clock_in_at / new_clock_out_at / old_clock_in_at / old_clock_out_at
     * を datetime（Carbon）として扱うことで、
     * 時刻の比較・フォーマット（format / diff / translatedFormat 等）を
     * 安全かつ直感的に行えるようにする。
     *
     * @var array<string, string>
     */
    protected $casts = [
        'new_clock_in_at'  => 'datetime',
        'new_clock_out_at' => 'datetime',
        'old_clock_in_at' => 'datetime',
        'old_clock_out_at' => 'datetime',
    ];

    /**
     * 出勤時刻を表示用の文字列（H:i）として取得する。
     *
     * new_clock_in_at が存在する場合は「08:00」のような形式で返し、
     * 未打刻（null）の場合は null を返す。
     *
     * 一覧画面や勤怠詳細画面など、ビュー層での表示用途を想定した
     * アクセサ。
     *
     * @return string|null
     */
    public function getClockInTimeAttribute(): ?string
    {
        return $this->new_clock_in_at
            ? $this->new_clock_in_at->format('H:i')
            : null;
    }

    /**
     * 退勤時刻を表示用の文字列（H:i）として取得する。
     *
     * new_clock_out_at が存在する場合は「17:30」のような形式で返し、
     * 未打刻（null）の場合は null を返す。
     *
     * 勤怠一覧画面や詳細画面など、ビュー層での表示用途を想定した
     * アクセサ。
     *
     * @return string|null
     */
    public function getClockOutTimeAttribute(): ?string
    {
        return $this->new_clock_out_at
            ? $this->new_clock_out_at->format('H:i')
            : null;
    }
}
