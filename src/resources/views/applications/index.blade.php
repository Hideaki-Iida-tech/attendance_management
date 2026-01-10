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
        <a href="/stamp_correction_request/list/?page={{ App\Enums\ApplicationStatus::PENDING->name }}" class="applications-tab-pending">承認待ち</a>
        <a href="/stamp_correction_request/list/?page={{ App\Enums\ApplicationStatus::APPROVED->name }}" class="applications-tab-approved">承認済み</a>
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
            @foreach($attendanceChangeRequests as $request)
            <tr class="applications-list-table-row">
                @if($status === App\Enums\ApplicationStatus::PENDING->value)
                <td class="list-state-content">承認待ち</td>
                @elseif($status === App\Enums\ApplicationStatus::APPROVED->value)
                <td class="list-state-content">承認済み</td>
                @endif
                <td>{{ $request->user?->name}}</td>
                <td>{{ $request->work_date?->format('Y/m/d') }}</td>
                <td>{{ $request?->reason }}</td>
                <td>{{ $request->created_at?->format('Y/m/d') }}</td>
                <td><a href="/attendance/{{ $request->attendance_id }}/?request_id={{ $request->id }}" class="detail-link">詳細</a></td>
            </tr>
            @endforeach
        </table>-
    </div>
</div>
@endsection