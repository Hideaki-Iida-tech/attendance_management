<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AdminsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 管理者情報を生成するシーダーファイル
     * @return void
     */
    public function run()
    {
        $param = [
            'name' => '管理者1',
            'email' => 'admin1@example.com',
            'password' => Hash::make('password'),
        ];

        DB::table('admins')->insert($param);
    }
}
