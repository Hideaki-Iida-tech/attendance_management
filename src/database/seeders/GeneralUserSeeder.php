<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Enums\UserRole;

class GeneralUserSeeder extends Seeder
{
    /**
     * 一般ユーザーを初期データとして作成または更新する。
     *
     * 指定したメールアドレスのユーザーが存在しない場合は、
     * 一般ユーザーロール（UserRole::USER）を付与したユーザーを新規作成し、
     * 既に存在する場合は一般ユーザーロールを含む情報を更新する。
     *
     * updateOrCreate を使用することで、Seeder を複数回実行しても
     * 管理者ユーザーが重複して作成されないようにしている。
     *
     * @return void
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'test1@example.com'],
            [
                'name' => 'テストユーザー1',
                'password' => Hash::make('password'),
                'role' => UserRole::USER->value,
            ]
        );

        User::updateOrCreate(
            ['email' => 'test2@example.com'],
            [
                'name' => 'テストユーザー2',
                'password' => Hash::make('password'),
                'role' => UserRole::USER->value,
            ]
        );

        User::updateOrCreate(
            ['email' => 'test3@example.com'],
            [
                'name' => 'テストユーザー3',
                'password' => Hash::make('password'),
                'role' => UserRole::USER->value,
            ]
        );

        User::updateOrCreate(
            ['email' => 'test4@example.com'],
            [
                'name' => 'テストユーザー4',
                'password' => Hash::make('password'),
                'role' => UserRole::USER->value,
            ]
        );

        User::updateOrCreate(
            ['email' => 'test5@example.com'],
            [
                'name' => 'テストユーザー5',
                'password' => Hash::make('password'),
                'role' => UserRole::USER->value,
            ]
        );

        User::updateOrCreate(
            ['email' => 'test6@example.com'],
            [
                'name' => 'テストユーザー6',
                'password' => Hash::make('password'),
                'role' => UserRole::USER->value,
            ]
        );

        User::updateOrCreate(
            ['email' => 'test7@example.com'],
            [
                'name' => 'テストユーザー7',
                'password' => Hash::make('password'),
                'role' => UserRole::USER->value,
            ]
        );

        User::updateOrCreate(
            ['email' => 'test8@example.com'],
            [
                'name' => 'テストユーザー8',
                'password' => Hash::make('password'),
                'role' => UserRole::USER->value,
            ]
        );

        User::updateOrCreate(
            ['email' => 'test9@example.com'],
            [
                'name' => 'テストユーザー9',
                'password' => Hash::make('password'),
                'role' => UserRole::USER->value,
            ]
        );

        User::updateOrCreate(
            ['email' => 'test10@example.com'],
            [
                'name' => 'テストユーザー10',
                'password' => Hash::make('password'),
                'role' => UserRole::USER->value,
            ]
        );
    }
}
