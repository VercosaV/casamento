<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Victor e Kamilly')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/noivos.css') }}">
    @stack('head')
</head>
<body>
    <nav class="navbar navbar-expand-md fixed-top bg-white border-bottom">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ route('home') }}#home">V &amp; K</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#menu" aria-controls="menu" aria-expanded="false" aria-label="Abrir menu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="menu">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="{{ route('home') }}#home">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('home') }}#osnoivos">O casal</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('presentes.index') }}">Lista de presentes</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('rsvp.create') }}">Confirme sua presença</a></li>
                </ul>
            </div>
        </div>
    </nav>

    @if (session('mensagem'))
        <div class="alert alert-success m-3">{{ session('mensagem') }}</div>
    @endif

    <main>
        @yield('content')
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
    @stack('scripts')
</body>
</html>