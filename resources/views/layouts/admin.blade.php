<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title', 'Painel') · Painel dos noivos</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Lato:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/site.css') }}">
    @stack('head')
</head>

<body class="admin-body">
    @php
        $itens = [
            ['painel', 'Painel', 'painel'],
            ['secoes.index', 'Página do convite', 'secoes.*'],
            ['presentes.admin', 'Presentes', 'presentes.*'],
            ['convidados.index', 'Convidados', 'convidados.*'],
            ['contribuicoes.index', 'Quem presenteou', 'contribuicoes.*'],
            ['perfil.edit', 'Meu perfil', 'perfil.*'],
        ];
    @endphp

    <nav class="navbar navbar-expand-lg navbar-dark admin-nav">
        <div class="container">
            <a class="navbar-brand" href="{{ route('painel') }}">Painel dos noivos</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu-admin"
                aria-controls="menu-admin" aria-expanded="false" aria-label="Abrir menu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="menu-admin">
                <ul class="navbar-nav me-auto">
                    @foreach ($itens as [$rota, $texto, $padrao])
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs($padrao) ? 'active' : '' }}"
                                href="{{ route($rota) }}">{{ $texto }}</a>
                        </li>
                    @endforeach
                </ul>
                <div class="d-flex gap-2 align-items-center">
                    <a href="{{ route('home') }}" target="_blank" rel="noopener" class="btn btn-outline-light btn-sm">Ver convite</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-light btn-sm">Sair</button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    @include('partials.mensagens')

    <main class="container admin-pagina">
        @yield('content')
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous">
    </script>
    @stack('scripts')
</body>

</html>
