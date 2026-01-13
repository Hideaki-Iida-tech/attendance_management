@extends($layout)
@push('css')
<link rel="stylesheet" href="{{ asset('css/staff/admin/index.css') }}">
@endpush

@section('title')
スタッフ一覧画面（管理者）
@endsection

@section('content')

<div class="staff-list">
    <div class="staff-list-title">
        <h1 class="staff-list-title-inner">スタッフ一覧</h1>
    </div>

    <div class="staff-list-content">
        <table class="staff-list-table">
            <tr class="staff-list-table-title">
                <th class="staff-list-col-name">名前</th>
                <th class="staff-list-col-email">メールアドレス</th>
                <th class="staff-list-col-detail">月次勤怠</th>
            </tr>
            @foreach($users as $user)
            <tr class="staff-list-table-row">
                <td class="staff-list-col-name">{{ $user->name }}</td>
                <td class="staff-list-col-email">{{ $user->email }}</td>
                <td class="staff-list-col-detail">
                    <a href="/admin/attendance/staff/{{ $user->id }}" class="detail-link">詳細</a>
                </td>
            </tr>
            @endforeach
        </table>
    </div>
</div>
@endsection