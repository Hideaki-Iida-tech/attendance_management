<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title')</title>
    <link rel="stylesheet" href="{{ asset('css/layouts/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/layouts/common.css') }}">
    @stack('css')
</head>

<body>
    <header class="header">
        <div class="header-inner">
            <div class="header-logo">
                <img src="{{ asset('images/logo.svg') }}" alt="coachtech" />
            </div>
            <div class="header-button">
                @yield('button')
            </div>
        </div>
    </header>
    <main>
        @yield('content')
    </main>
</body>

</html>