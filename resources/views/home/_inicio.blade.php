@php
    // e() protege contra HTML no título; depois só o "&" ganha a cor dourada.
    $nomes = str_replace('&amp;', '<span class="amp">&amp;</span>', e($secao->titulo));
    $estilo = $secao->imagem_url
        ? "background-image: linear-gradient(rgba(255,255,255,.82), rgba(255,255,255,.82)), url('{$secao->imagem_url}');"
        : '';
@endphp
<section id="{{ $secao->slug }}" class="hero" style="{{ $estilo }}">
    <div class="container">
        <h1 class="hero-nomes">{!! $nomes !!}</h1>

        @if ($secao->data_evento)
            <p class="hero-data">{{ $secao->data_evento->locale('pt_BR')->isoFormat('D [de] MMMM [de] YYYY') }}</p>
        @endif

        <div class="divisor" aria-hidden="true"><span></span></div>

        @if ($secao->data_evento)
            {{-- toIso8601String() já leva o fuso (-03:00): todo visitante conta até o mesmo instante --}}
            <div class="contagem" id="countdown" data-target="{{ $secao->data_evento->toIso8601String() }}"
                role="timer" aria-label="Contagem regressiva para o casamento">
                <div class="contagem-item"><span id="days" class="numero">00</span><span class="rotulo">DIAS</span></div>
                <div class="contagem-item"><span id="hours" class="numero">00</span><span class="rotulo">HORAS</span></div>
                <div class="contagem-item"><span id="minutes" class="numero">00</span><span class="rotulo">MINUTOS</span></div>
                <div class="contagem-item"><span id="seconds" class="numero">00</span><span class="rotulo">SEGUNDOS</span></div>
            </div>
        @endif

        @if ($secao->conteudo)
            <p class="hero-mensagem">{!! nl2br(e($secao->conteudo)) !!}</p>
        @endif

        <div class="d-flex flex-wrap justify-content-center gap-3 mt-4">
            <a href="{{ route('rsvp.create') }}" class="btn btn-ouro">Confirme sua presença</a>
            <a href="{{ route('presentes.index') }}" class="btn btn-contorno">Lista de presentes</a>
        </div>
    </div>
</section>
