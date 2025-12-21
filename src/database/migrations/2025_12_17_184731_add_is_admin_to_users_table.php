<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsAdminToUsersTable extends Migration
{
    /**
     * users テーブルにロール管理用のカラムを追加する。
     *
     * role カラムは App\Enums\UserRole に対応し、
     * 0: 一般ユーザー（USER）
     * 1: 管理者（ADMIN）
     * を表す。
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->tinyInteger('role')->default(\App\Enums\UserRole::USER->value)
                ->after('password');
        });
    }

    /**
     * ロール管理用カラムを削除し、マイグレーションをロールバックする。
     *
     * up() で追加した users.role カラムを削除する。
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
}
