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

            // attendancesテーブルのidを外部キーとするattendance_id
            // 申請の対象となる出退勤レコードを特定
            $table->foreignId('attendance_id')->constrained('attendances')->cascadeOnDelete();
            // 申請の対象となる勤務の勤務日
            $table->date('work_date');

            // user_id + work_date にユニーク制約を付与し、同一日の重複申請を防止
            $table->unique(
                ['user_id', 'work_date'],
            );

            // attendance_id にユニーク制約を付与
            $table->unique('attendance_id');

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
