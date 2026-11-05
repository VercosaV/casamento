<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', $secaoInicio->titulo ?? 'Convite de casamento')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Lato:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/site.css') }}">
    @stack('head')
</head>

<body>
    @php
        // "Victor & Kamilly" -> "V & K" para o logotipo do menu
        $iniciais = collect(explode('&', $secaoInicio->titulo ?? 'V & K'))
            ->map(fn ($nome) => mb_strtoupper(mb_substr(trim($nome), 0, 1)))
            ->implode(' & ');
    @endphp

    <nav class="navbar navbar-expand-lg fixed-top site-nav">
        <div class="container-fluid px-3 px-lg-4">
            <a class="navbar-brand" href="{{ route('home') }}">{{ $iniciais }}</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu"
                aria-controls="menu" aria-expanded="false" aria-label="Abrir menu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="menu">
                <ul class="navbar-nav">
                    {{-- Links das seções: vêm do painel (Página do convite) --}}
                    @foreach ($menuSecoes as $item)
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('home') }}#{{ $item->slug }}">{{ $item->rotulo }}</a>
                        </li>
                    @endforeach
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('presentes.index') ? 'active' : '' }}"
                            href="{{ route('presentes.index') }}">Lista de presentes</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('rsvp.create') ? 'active' : '' }}"
                            href="{{ route('rsvp.create') }}">Confirme sua presença</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    @include('partials.mensagens')

    <main>
        @yield('content')
    </main>

    <footer class="rodape">
        <div class="rodape-nomes">{{ $secaoInicio->titulo ?? '' }}</div>
        @if (! empty($secaoInicio?->data_evento))
            <div class="mb-2">{{ $secaoInicio->data_evento->locale('pt_BR')->isoFormat('D [de] MMMM [de] YYYY') }}</div>
        @endif
        <a href="{{ route('privacidade') }}">Privacidade</a> ·
        <a href="{{ route('login') }}">Área dos noivos</a>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous">
    </script>
    @stack('scripts')
</body>

</html>
