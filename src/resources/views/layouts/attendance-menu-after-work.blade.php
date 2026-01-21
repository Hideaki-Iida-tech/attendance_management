@extends('layouts.app')

@push('css')
<link rel="stylesheet" href="{{ asset('css/layouts/attendance-menu-after-work.css') }}">
@endpush

@section('button')
<div class="header-button">

    <a href="/attendance/list" class="header-button-attendance-list">今月の出勤一覧</a>
    <a href="/stamp_correction_request/list" class="header-button-request-list">申請一覧</a>
    <form action="/logout" method="post">
        @csrf
        <button class="header-button-logout">ログアウト</button>
    </form>

</div>
@endsection