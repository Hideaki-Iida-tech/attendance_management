<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Admin extends Authenticatable
{
    use Notifiable;

    // 一括代入（mass assignment）を許可するカラムのホワイトリスト
    protected $fillable = ['name', 'email', 'password'];

    // モデルを配列やJSONに変換するときに、password を隠す
    protected $hidden = ['password'];
}
