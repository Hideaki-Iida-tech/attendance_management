<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\ApplicationStatus;

class CreateAttendanceChangeRequests extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // attendance_change_requestsテーブル
        // 申請ヘッダー情報を格納するテーブル
        Schema::create('attendance_change_requests', function (Blueprint $table) {
            // 主キー
            $table->id();
            // usersテーブルのidを外部キーとするuser_id（申請者）
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $pending = ApplicationStatus::PENDING->value;

            // 部分一意制約のために生成カラム 　pending_user_idを作成
            // statusがPENDINGのときのみuser_idをコピー、それ以外の場合はnullとしておく
            $table->unsignedBigInteger('pending_user_id')
                ->nullable()
                ->storeAs("CASE WHEN status = {$pending} THEN user_id ELSE NULL END");

            // 部分一致制約のために生成pending_work_dateを作成
            // statusがPENDINGのときのみwork_dateをコピー、それ以外の場合は、nullとしておく 
            $table->date('pending_work_date')
                ->nullable()
                ->storeAs("CASE WHEN status = {$pending} THEN work_date ELSE NULL END");

            // unique indexを追加
            // statusがペンディングの場合のみ、user_id,work_dateの組み合わせにユニーク制約をつける
            $table->unique(
                ['pending_user_id', 'pending_work_date'],
                'uniq_pending_user_work_date'
            );

            // attendancesテーブルのidを外部キーとするattendance_id
            // 申請の対象となる出退勤レコードを特定
            $table->foreignId('attendance_id')->constrained('attendances')->cascadeOnDelete();
            // 申請の対象となる勤務の勤務日
            $table->date('work_date');
            // 申請の処理状況 （0:承認待ち 1:承認済みenumで定義）
            $table->unsignedTinyInteger('status')->default(0);
            // 備考（申請理由）
            $table->string('reason', 255);
            // usersテーブルのidを外部キーとするreviewed_by（承認者を表す管理者のid）
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->cascadeOnDelete();
            // 承認日時
            $table->datetime('reviewed_at')->nullable();
            // 却下（を実装する場合の）理由等を記録する
            $table->string('review_comment', 255)->nullable();
            // タイムスタンプ
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attendance_change_requests');
    }
}
