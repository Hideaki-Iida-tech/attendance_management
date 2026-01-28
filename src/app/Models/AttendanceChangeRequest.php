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
     * 指定した勤怠IDに紐づくレコードが申請中（PENDING）状態で存在するかを判定する。
     *
     * 対象レコードが存在しない場合や、
     * ステータスが申請中（PENDING）以外の場合は false を返す。
     *
     * モデルを生成せず、存在チェック（exists）のみを行うため、
     * 管理者・一般ユーザーの一覧画面などで高速に利用できる。
     *
     * @param  int  $attendance_id  判定対象の勤怠ID
     * @return bool 申請中のレコードが存在する場合 true、それ以外の場合 false
     */
    public static function existsPending(int $attendance_id): bool
    {
        return static::query()
            ->where('attendance_id', $attendance_id)
            ->where('status', ApplicationStatus::PENDING)
            ->exists();
    }

    /**
     * 指定した勤怠IDのレコードが「承認済み」状態で存在するかを判定する。
     *
     * 対象の勤怠レコードが存在しない場合や、
     * ステータスが承認済み（APPROVED）以外の場合は false を返す。
     *
     * モデルの生成を行わず、存在チェック（exists）によって
     * 高速に真偽値を返す。
     *
     * @param  int  $attendance_id  判定対象の勤怠ID
     * @return bool 承認済みの場合 true、それ以外の場合 false
     */
    public static function isApprovedByAttendance(int $attendance_id): bool
    {
        return static::query()
            ->where('attendance_id', $attendance_id)
            ->where('status', ApplicationStatus::APPROVED)
            ->exists();
    }

    /**
     * この勤怠修正申請が「承認済み」状態かどうかを判定する。
     *
     * 申請レコード自身が保持する status の値を基に判定を行い、
     * 承認済み（ApplicationStatus::APPROVED）の場合は true を返す。
     *
     * DB クエリは発行せず、取得済みモデルの状態のみを参照する。
     *
     * @return bool 承認済みの場合 true、それ以外の場合 false
     */
    public function isApproved(): bool
    {
        return $this->status === ApplicationStatus::APPROVED;
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
