@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth/register.css') }}">
@endsection

@section('title')
会員登録画面
@endsection

@section('content')
<div class="register-form">
    <form action="/register" class="register-form-inner" method="post" novalidate>
        @csrf
        <table class="register-form-table">

            <tr class="register-form-row-first">
                <td>
                    <h1>会員登録</h1>
                </td>
            </tr>

            <tr class="register-form-row">
                <td>
                    <label class="register-form-label">
                        名前<br />
                        <input type="text" class="register-input" name="name" value="{{ old('name') }}" />
                    </label>
                    @if ($errors->has('name'))
                    <div class="register-alert-danger">
                        <ul>
                            @foreach ($errors->get('name') as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                </td>
            </tr>

            <tr class="register-form-row">
                <td>
                    <label class="register-form-label">メールアドレス<br /><input type="email" class="register-input" name="email" value="{{ old('email') }}" />
                    </label>
                    @if ($errors->has('email'))
                    <div class="register-alert-danger">
                        <ul>
                            @foreach ($errors->get('email') as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                </td>
            </tr>

            <tr class="register-form-row">
                <td>
                    <label class="register-form-label">パスワード<br /><input type="password" class="register-input" name="password" /></label>
                    @if ($errors->has('password'))
                    <div class="register-alert-danger">
                        <ul>
                            @foreach ($errors->get('password') as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                </td>
            </tr>

            <tr class="register-form-row">
                <td>
                    <label class="register-form-label">パスワード確認<br /><input type="password" class="register-input" name="password_confirmation" /></label>
                    @if ($errors->has('password_confirmation'))
                    <div class="register-alert-danger">
                        <ul>
                            @foreach ($errors->get('password_confirmation') as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                </td>
            </tr>

            <tr class="register-form-row">
                <td class="register-form-col-button">
                    <button type="submit" class="register-button">登録する<br /></button>
                </td>
            </tr>

            <tr class="register-form-row-last">
                <td class="register-form-col-link">
                    <a href="/login" class="register-form-link">ログインはこちら</a>
                </td>
            </tr>

        </table>
    </form>
</div>
@endsection