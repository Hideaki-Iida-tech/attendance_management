# attendance_management

## Docker ビルド

### git clone SSH

`git clone git@github.com:Hideaki-Iida-tech/attendance_management.git`

### git clone HTTPS

`git clone https://github.com/Hideaki-Iida-tech/attendance_management.git`

＊SSH か HTTPS かどちらか一方でクローンしてください。

### Docker ビルド・起動

`cd attendance_management`<br>
`docker-compose up -d --build`

＊MySQL は、OS によって起動しない場合があるのでそれぞれの PC に合わせて docker-compose.yml ファイルを編集してください。

## Laravel 環境構築

### 1.PHP コンテナに入る

`docker-compose exec php bash`

### 2.composer install

`composer install`

### 3.`.env`と`.env.testing`作成 ＆ 環境変数を設定

目的：Laravel が DB・メールなどのサービスに接続できるようにする

#### `.env`を作成編集

`cp .env.example .env`<br>
`chown `ユーザー名`:`グループ名` .env`

＊各環境変数<br>
DB_DATABASE=laravel_db<br>
DB_USERNAME=laravel_user<br>
DB_PASSWORD=laravel_pass<br>
<br>
MAIL_MAILER=smtp<br>
MAIL_HOST=mailhog<br>
MAIL_PORT=1025<br>
<br>
MAIL_FROM_ADDRESS="no-reply@example.test"<br>
MAIL_FROM_NAME="Example"<br>
<br>
上記の値につき`.env`ファイルに未設定の値がある場合には、上記の値を設定してください。<br>

#### アプリケーションキー生成と設定キャッシュのクリア、値の生成の確認

`php artisan key:generate`<br>
`php artisan config:clear`<br>
`.env`の APP_KEY=に値が入っていることを確認<br>

#### .env.testing を作成編集

`cp .env.testing.example .env.testing`<br>
＊各環境変数<br>
DB_CONNECTION=mysql_test<br>
<br>
TEST_DB_DATABASE=demo_test<br>
TEST_DB_USERNAME=root<br>
TEST_DB_PASSWORD=root<br>
<br>
MAIL_MAILER=smtp<br>
MAIL_HOST=mailhog<br>
MAIL_PORT=1025<br>
<br>
MAIL_FROM_ADDRESS="no-reply@example.test"<br>
MAIL_FROM_NAME="Example"<br>
<br>
上記の値につき`.env.testing`ファイルに未設定の値がある場合には、上記の値を設定してください。<br>

#### アプリケーションキー生成と設定キャッシュのクリア、値の生成の確認

`php artisan key:generate --env=testing`<br>
`php artisan config:clear`<br>
`.env.testing`の APP_KEY=に値が入っていることを確認<br>

## マイグレーション及びシーディングの実行

`php artisan migrate`<br>
`php artisan db:seed`<br>
＊シーダーで一般ユーザー<br>
メールアドレス：test1@example.com パスワード：password<br>
メールアドレス：test2@example.com パスワード：password<br>
メールアドレス：test3@example.com パスワード：password<br>
メールアドレス：test4@example.com パスワード：password<br>
メールアドレス：test5@example.com パスワード：password<br>
メールアドレス：test6@example.com パスワード：password<br>
メールアドレス：test7@example.com パスワード：password<br>
メールアドレス：test8@example.com パスワード：password<br>
メールアドレス：test9@example.com パスワード：password<br>
メールアドレス：test10@example.com パスワード：password<br>
の 10 名を作成<br>
*シーダーで管理者ユーザー<br>
メールアドレス：admin@example.com パスワード：password<br>
を作成<br>
*シーダーでテストユーザー1（test1@example.com）について1月分の勤怠データを作成。
PHP コンテナからログアウト<br>

## データベースおよびテーブルが作成され、シーディングが成功していることを確認

### 1. MySQL コンテナにログイン

`docker-compose exec mysql mysql -u root -p`<br>
Enter password：root<br>

### 2.データベースを表示、選択

`SHOW DATABASES;`<br>
ここで、laravel_db が作成されていることを確認してください。<br>
`USE laravel_db;`<br>

### 3.テーブルを表示

`SHOW TABLES;`<br>
ここで、attendances、<br>
attendance_change_requests、<br>
attendance_change_request_items、<br>
breaks、<br>
break_change_request_items、<br>
users <br>
の各テーブルが作成されていることを確認してください。<br>

### 4.各テーブルのレコード数を表示（シーディングが実行されていることを確認）

`SELECT COUNT(*) FROM attendances;`→19 件<br>
`SELECT COUNT(*) FROM attendance_change_requets;`→0 件<br>
`SELECT COUNT(*) FROM attendance_change_request_items;`→0 件<br>
`SELECT COUNT(*) FROM breaks;`→ 57件<br>
`SELECT COUNT(*) FROM break_change_requests;`→0 件<br>
`SELECT COUNT(*) FROM users;`→11 件<br>
＊件数は手動でユーザー登録、出退勤処理、修正申請処理、修正承認処理を行わず、一度だけシーダーを走らせた場合です。<br>
MySQL コンテナからログアウト

## Storage ディレクトリと bootstrap/cache の所有及びパーミッションの変更

`chown -R www-data:www-data storage bootstrap/cache`<br>
`chmod -R 775 storage bootstrap/cache`<br>
※環境によっては、所有権限及びパーミッション関係のエラーが発生することがあります。<br>
エラーが発生した場合は、PHP コンテナ内でこれらのコマンドを実行してください<br>
PHP コンテナからログアウト

## テストの実施

### 1.テストコード用データベースの作成と確認

`docker-compose exec mysql mysql -u root -p`<br>
Enter password：root<br>
`CREATE DATABASE demo_test;`<br>
`SHOW DATABASES;`→demo_test が表示されれば OK<br>
MySQL コンテナからログアウト<br>

### 2.テストの実行

`docker-compose exec php bash`<br>
`php artisan test --testsuite=Feature`または、<br>
`php artisan test --filter=AdminAttendanceIndexTest`＊<br>
＊は tests/Feature ディレクトリ下の各テストクラスのクラス名を指定

## 使用技術

- PHP 8.1

- Laravel 8.83

- MySQL 8.0

- Fortify

- MailHog

## ER 図

このアプリの主要なテーブル構造は以下の通りです。<br>
![ER 図](/docs/er/attendance_management_er.png)

## URL 一覧

- 開発環境：
  http://localhost

- phpMyAdmin：
  http://localhost:8080

- MailHog:
  http://localhost:8025
