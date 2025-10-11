<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Portal Ciudadano | IDTGB - Beni')</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">

    <!-- Styles -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --beni-green: #007A33; --beni-green-light: #00A652; --beni-yellow: #FCD116; }
        body { background-color: #f4f7fa; font-family: "Segoe UI", Arial, sans-serif; }
        .navbar-custom { background: linear-gradient(135deg, var(--beni-green), var(--beni-green-light)); }
        .navbar-custom .navbar-brand, .navbar-custom .nav-link { color: #fff !important; }
        .card-header-primary { background: linear-gradient(135deg, var(--beni-green), var(--beni-green-light)); color: #fff; }
        .btn-primary { background-color: var(--beni-green); border-color: var(--beni-green); }
        .btn-primary:hover { background-color: var(--beni-green-light); border-color: var(--beni-green-light); }
        .result-box { background: #e9f5ff; border-left: 5px solid var(--beni-green); }
        .final-amount { font-size: 1.75rem; font-weight: 700; color: var(--beni-green); }
        .icon-size { font-size: 1.2rem; margin-right: 0.4rem; }
    </style>
    @stack('styles')
</head>
<body>
    <div id="app">
        @include('partials.header')

        <main class="py-4">
            @yield('content')
        </main>

        @include('partials.footer')
    </div>

    <!-- Scripts -->
    @include('partials.scripts')
    @stack('scripts')
</body>
</html>
