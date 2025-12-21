@extends('layouts.app')

@push('css')
<link rel="stylesheet" href="{{ asset('css/auth/admin/login.css') }}">
@endpush

@section('title')
ログイン画面（管理者）
@endsection

@section('content')
<div class="login-form">
    <form action="/login" class="login-form-inner" method="post" novalidate>
        @csrf
        <table class="login-form-table">

            <tr class="login-form-row-first">
                <td>
                    <h1>管理者ログイン</h1>
                </td>
            </tr>

            <tr class="login-form-row">
                <td>
                    <input type="hidden" name="login_context" value="admin" />
                    <label class="login-form-label">メールアドレス<br />
                        <input type="email" class="login-input" name="email" value="{{ old('email') }}" />
                    </label>
                    @if ($errors->has('email'))
                    <div class="login-alert-danger">
                        <ul>
                            @foreach ($errors->get('email') as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                </td>
            </tr>

            <tr class="login-form-row">
                <td>
                    <label class="login-form-label">パスワード<br /><input type="password" class="login-input" name="password" /></label>
                    @if ($errors->has('password'))
                    <div class="login-alert-danger">
                        <ul>
                            @foreach ($errors->get('password') as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                </td>
            </tr>

            <tr class="login-form-row">
                <td class="login-form-col-button">
                    <button type="submit" class="login-button">管理者ログインする<br /></button>
                </td>
            </tr>

        </table>
    </form>
</div>
@endsection