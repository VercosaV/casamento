@php
    $paragrafos = preg_split('/\R{2,}/', trim((string) $secao->conteudo), -1, PREG_SPLIT_NO_EMPTY);
@endphp
<section id="{{ $secao->slug }}" class="secao {{ $alt ? 'alt' : '' }}">
    <div class="secao-miolo">
        <h2 class="secao-titulo">{{ $secao->titulo }}</h2>
        <div class="divisor" aria-hidden="true"><span></span></div>

        <div class="secao-texto mb-4">
            @foreach ($paragrafos as $paragrafo)
                <p>{!! nl2br(e($paragrafo)) !!}</p>
            @endforeach
        </div>

        @if ($secao->fotos->isEmpty())
            {{-- Sem fotos: mostra um aviso em vez de imagens quebradas --}}
            <div class="galeria-vazia">As fotos do casal aparecerão aqui.</div>
        @else
            <div id="galeria-{{ $secao->id }}" class="carousel slide galeria" data-bs-ride="carousel">
                <div class="carousel-inner">
                    @foreach ($secao->fotos as $foto)
                        <div class="carousel-item {{ $loop->first ? 'active' : '' }}">
                            <img src="{{ $foto->url }}" alt="{{ $foto->legenda ?: $secao->titulo.' — foto '.$loop->iteration }}" loading="lazy">
                        </div>
                    @endforeach
                </div>
                @if ($secao->fotos->count() > 1)
                    <button class="carousel-control-prev" type="button" data-bs-target="#galeria-{{ $secao->id }}" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Anterior</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#galeria-{{ $secao->id }}" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Próximo</span>
                    </button>
                @endif
            </div>
        @endif
    </div>
</section>
