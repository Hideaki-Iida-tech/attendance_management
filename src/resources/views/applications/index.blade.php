@extends($layout)
@push('css')
<link rel="stylesheet" href="{{ asset('css/applications/index.css') }}">
@endpush

@section('title')
申請一覧画面（一般ユーザー）
@endsection

@section('content')

<div class="applications-list">
    <div class="applications-list-title">
        <h1 class="applications-list-title-inner">申請一覧</h1>
    </div>

    <div class="applications-tab">
        <a href="" class="applications-tab-pending">承認待ち</a>
        <a href="" class="applications-tab-approved">承認済み</a>
    </div>

    <hr class="applications-separator">

    <div class="applications-list-content">
        <table class="applications-list-table">
            <tr class="applications-list-table-title">
                <th class="list-state">状態</th>
                <th>名前</th>
                <th>対象日時</th>
                <th>申請理由</th>
                <th>申請日時</th>
                <th>詳細</th>
            </tr>
            @for($i = 0; $i < 10; $i++)
                <tr class="applications-list-table-row">
                <td class="list-state-content">承認待ち</td>
                <td>西伶奈</td>
                <td>2023/06/01</td>
                <td>遅延のため</td>
                <td>2023/06/02</td>
                <td><a href="" class="detail-link">詳細</a></td>
                </tr>
                @endfor
        </table>
    </div>
</div>
@endsection