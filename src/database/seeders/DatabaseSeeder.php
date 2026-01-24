<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();
        // 管理者を登録するシーダーAdminUesrSeederを登録
        $this->call(AdminUserSeeder::class);
        // 一般ユーザーを登録するシーダーGeneralUserSeederを登録
        $this->call(GeneralUserSeeder::class);

        // テストユーザー1の2026-01月分の勤怠データ作成seederを登録
        $this->call(AttendancesSeeder::class);

        // 2026-01月分の休憩データ作成seederを登録
        $this->call(BreaksSeeder::class);
    }
}
