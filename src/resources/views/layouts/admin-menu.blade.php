@extends('layouts.app')

@push('css')
<link rel="stylesheet" href="{{ asset('css/layouts/admin-menu.css') }}">
@endpush

@section('button')
<div class="header-button">

    <a href="" class="header-button-attendance-list">勤怠一覧</a>
    <a href="" class="header-button-staff-list">スタッフ一覧</a>
    <a href="" class="header-button-request-list">申請一覧</a>
    <form action="/admin/logout" method="post">
        @csrf
        <button class="header-button-logout">ログアウト</button>
    </form>

</div>
@endsection