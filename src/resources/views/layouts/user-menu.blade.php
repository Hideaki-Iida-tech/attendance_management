@section('button')
<div class="header-button">

    <a href="" class="header-button-attendance">勤怠</a>
    <a href="" class="header-button-atttendance-list">勤怠一覧</a>
    <a href="" class="header-button-request">申請</a>
    <form action="/logout" method="post">
        @csrf
        <button class="header-button-logout">ログアウト</button>
    </form>

</div>
@endsection