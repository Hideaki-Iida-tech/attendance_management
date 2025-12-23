@extends('layouts.app')

@push('css')
<link rel="stylesheet" href="{{ asset('css/layouts/user-menu.css') }}">
@endpush

@section('button')
<div class="header-button">

    <a href="/attendance" class="header-button-attendance-register">勤怠</a>
    <a href="/attendance/list" class="header-button-attendance-list">勤怠一覧</a>
    <a href="/stamp_correction_request/list" class="header-button-request">申請</a>
    <form action="/logout" method="post">
        @csrf
        <button class="header-button-logout">ログアウト</button>
    </form>

</div>
@endsection