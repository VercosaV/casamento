@extends('layouts.app')

@section('title', 'Victor e Kamilly — 10 de julho de 2027')

@section('content')
<section id="home" class="text-center py-5">
    <div class="container-main">
        <h1>
            Victor
            <span class="amp">&amp;</span>
            Kamilly
        </h1>
    </div>
    <hr class="hr-bdata">
    <h2 class="container-data">10 de Julho de 2027</h2>

    <div class="container-contagem">
        <h3>Contagem Regressiva</h3>
        <div class="countdown-content" id="countdown" data-target="2027-07-10T16:00:00-03:00">
            <div class="item">
                <span id="days" class="number">00</span>
                <span class="label">DIAS</span>
            </div>
            <div class="item">
                <span id="hours" class="number">00</span>
                <span class="label">HORAS</span>
            </div>
            <div class="item">
                <span id="minutes" class="number">00</span>
                <span class="label">MINUTOS</span>
            </div>
            <div class="item">
                <span id="seconds" class="number">00</span>
                <span class="label">SEGUNDOS</span>
            </div>
        </div>
    </div>

    <p class="mx-auto mt-4 px-3" style="max-width: 640px;">
        Escreva aqui a mensagem de boas-vindas aos convidados.
    </p>

    <div class="d-flex justify-content-center gap-2 mt-3">
        <a href="{{ route('rsvp.create') }}" class="btn btn-dark">Confirme sua presença</a>
        <a href="{{ route('presentes.index') }}" class="btn btn-outline-dark">Lista de presentes</a>
    </div>
</section>

<section id="osnoivos" class="py-5">
    <div class="mx-auto px-3" style="max-width: 800px;">
        <h2 class="text-center mb-4">O casal</h2>
        <p>Conte aqui a história de vocês.</p>

        @php
            // Fotos em public/img/casal/ (troque pelos nomes reais dos arquivos)
            $fotos = ['foto1.jpg', 'foto2.jpg', 'foto3.jpg'];
        @endphp

        <div id="galeria" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-inner">
                @foreach ($fotos as $i => $foto)
                    <div class="carousel-item {{ $i === 0 ? 'active' : '' }}">
                        <img src="{{ asset('img/casal/' . $foto) }}" class="d-block w-100"
                             alt="Foto do casal {{ $i + 1 }}" loading="lazy">
                    </div>
                @endforeach
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#galeria" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Anterior</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#galeria" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Próximo</span>
            </button>
        </div>
    </div>
</section>
@endsection

@push('scripts')
    <script src="{{ asset('js/contagem.js') }}" defer></script>
@endpush