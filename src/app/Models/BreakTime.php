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
}
