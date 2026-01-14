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
     * work_date を date（Carbon）として扱うことで、
     * 勤務日の年月日操作（format / comparison / CarbonPeriod 等）を
     * 安全かつ直感的に行えるようにする。
     * 
     * attendance_change_requests テーブルの status カラムを
     * ApplicationStatus（enum）として扱うための設定。
     */
    protected $casts = [
        'status' => ApplicationStatus::class,
        'work_date' => 'date',
    ];

    /**
     * 勤怠変更申請に紐づく出退勤時刻の変更内容を取得する。
     *
     * 勤怠変更申請（attendance_change_requests）1件に対して、
     * 出退勤時刻の変更明細（attendance_change_request_items）は
     * 原則として1件のみ存在する想定のため、
     * hasOne リレーションとして定義する。
     *
     * 外部キーには request_id を使用する。
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function attendance()
    {
        return $this->hasOne(AttendanceChangeRequestItem::class, 'request_id');
    }

    /**
     * 勤怠変更申請に紐づく休憩時間の変更内容を取得する。
     *
     * 勤怠変更申請（attendance_change_requests）1件に対して、
     * 休憩時間の変更明細（break_change_request_items）は
     * 複数件存在し得るため、hasMany リレーションとして定義する。
     *
     * 外部キーには request_id を使用する。
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function breaks()
    {
        return $this->hasMany(BreakChangeRequestItem::class, 'request_id');
    }

    /**
     * 勤怠変更申請を行ったユーザーを取得する。
     *
     * AttendanceChangeRequest は user_id を外部キーとして
     * users テーブルのレコードと関連付けられる。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, self>
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 指定した勤怠IDに対して、申請中（pending）の
     * 勤怠変更申請が存在するかを判定する。
     *
     * @param  int  $attendance_id  対象となる勤怠ID
     * @return bool 申請中のレコードが存在する場合は true
     */
    public static function existsPending(int $attendance_id): bool
    {
        return static::where('attendance_id', $attendance_id)
            ->where('status', ApplicationStatus::PENDING->value)
            ->exists();
    }

    /**
     * 勤務日の年を表示用の文字列（Y年）として取得する。
     *
     * work_date が存在する場合は「2026年」のような形式で返し、
     * 未設定（null）の場合は null を返す。
     *
     * 勤怠詳細画面など、ビュー層での表示用途を想定した
     * アクセサ。
     *
     * @return string|null
     */
    public function getFormatedYearAttribute(): ?string
    {
        return $this->work_date?->format('Y年');
    }

    /**
     * 勤務日の月日を表示用の文字列（n月j日）として取得する。
     *
     * work_date が存在する場合は「1月1日」「12月31日」のような
     * ゼロ埋めなしの月日形式で返し、
     * 未設定（null）の場合は null を返す。
     *
     * 勤怠詳細画面など、ビュー層での表示用途を想定した
     * アクセサ。
     *
     * @return string|null
     */
    public function getFormatedMonthAndDayAttribute(): ?string
    {
        return $this->work_date?->format('n月j日');
    }
}
