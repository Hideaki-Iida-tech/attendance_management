<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendanceChangeRequestItems extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // attendance_change_request_itemsテーブル
        // 申請内容のうち、出退勤時刻を記録するテーブル
        Schema::create('attendance_change_request_items', function (Blueprint $table) {
            // 主キー
            $table->id();
            // attendance_change_requestsテーブルのidを外部キーとするrequest_id
            // 申請ヘッダーを特定
            $table->foreignId('request_id')->constrained('attendance_change_requests')->cascadeOnDelete();
            // 修正後の出勤時刻（null許容）
            $table->datetime('new_clock_in_at')->nullable();
            // 修正後の退勤時刻（null許容）
            $table->datetime('new_clock_out_at')->nullable();
            // 修正前の出勤時刻（null許容）
            $table->datetime('old_clock_in_at')->nullable();
            // 修正前の退勤時刻（null許容）
            $table->datetime('old_clock_out_at')->nullable();
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
        Schema::dropIfExists('attendance_change_request_items');
    }
}
