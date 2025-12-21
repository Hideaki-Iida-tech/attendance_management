<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Enums\UserRole;

class AdminUserSeeder extends Seeder
{
    /**
     * 管理者ユーザーを初期データとして作成（または更新）する。
     *
     * 指定したメールアドレスのユーザーが存在しない場合は新規作成し、
     * 既に存在する場合は管理者ロール（UserRole::ADMIN）を付与する。
     *
     * updateOrCreate を使用することで、Seeder を複数回実行しても
     * 重複データが作成されないようにしている。
     * @return void
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin1',
                'password' => Hash::make('password'),
                'role' => UserRole::ADMIN->value,
            ]
        );
    }
}
