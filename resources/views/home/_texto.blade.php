@php
    // Parágrafos separados por linha em branco; quebra simples vira <br>.
    $paragrafos = preg_split('/\R{2,}/', trim((string) $secao->conteudo), -1, PREG_SPLIT_NO_EMPTY);
@endphp
<section id="{{ $secao->slug }}" class="secao {{ $alt ? 'alt' : '' }}">
    <div class="secao-miolo">
        <h2 class="secao-titulo">{{ $secao->titulo }}</h2>
        <div class="divisor" aria-hidden="true"><span></span></div>

        <div class="row align-items-center g-4">
            @if ($secao->imagem_url)
                <div class="col-md-5">
                    <img src="{{ $secao->imagem_url }}" alt="{{ $secao->titulo }}" class="secao-foto" loading="lazy">
                </div>
            @endif
            <div class="{{ $secao->imagem_url ? 'col-md-7' : 'col-12' }} secao-texto">
                @foreach ($paragrafos as $paragrafo)
                    <p>{!! nl2br(e($paragrafo)) !!}</p>
                @endforeach
            </div>
        </div>
    </div>
</section>
