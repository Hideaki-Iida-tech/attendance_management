@extends('layouts.app')

@push('css')
<link rel="stylesheet" href="{{ asset('css/auth/verify-email.css') }}">
@endpush

@section('title')
メール認証誘導画面
@endsection

@section('content')
<div class="verify-email-form">
    <div class="verify-email-form-inner">

        <div class="verify-email-message">
            登録いただいたメールアドレスに認証メールを送付しました。<br />
            メール認証を完了してください。
        </div>

        <div class="verify-email-exec">
            <form action="{{ url('http://localhost:8025') }}" method="get">
                <button class="verify-email-exec-button">認証はこちらから</button>
            </form>
        </div>

        <div class="verify-email-resend">
            <form action="{{-- route('verification.send') --}}" method="get">
                <button class="verify-email-resend-button">認証メールを再送する</button>
            </form>
        </div>

    </div>
</div>
@endsection