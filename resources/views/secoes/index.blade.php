@extends('layouts.admin')

@section('title', 'Página do convite')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
    <h1 class="mb-0">Página do convite</h1>
    <div class="d-flex gap-2">
        <a href="{{ route('home') }}" target="_blank" rel="noopener" class="btn btn-contorno btn-sm">Ver como o convidado vê</a>
        <a href="{{ route('secoes.create') }}" class="btn btn-ouro btn-sm">+ Nova seção</a>
    </div>
</div>
<p class="text-muted mb-4">
    As seções aparecem no convite <strong>na ordem desta lista</strong>. Use as setas para mudar a ordem,
    <em>Esconder</em> para tirar uma seção do ar sem apagar o texto e <em>Editar</em> para mudar título, texto e fotos.
</p>

<div class="d-flex flex-column gap-3">
    @foreach ($secoes as $secao)
        <div class="secao-item {{ $secao->visivel ? '' : 'escondida' }}">
            <div class="d-flex flex-wrap align-items-center gap-3">
                <div class="ordem">{{ $loop->iteration }}</div>

                <div class="flex-grow-1">
                    <div class="fw-bold">{{ $secao->titulo }}</div>
                    <div class="small text-muted">
                        @switch($secao->tipo)
                            @case('inicio') Topo: nomes, data, contagem e boas-vindas @break
                            @case('galeria') Texto + fotos ({{ $secao->fotos_count }}) @break
                            @default Título + texto
                        @endswitch
                        @unless ($secao->visivel) · <span class="text-danger">escondida do convite</span> @endunless
                        @if ($secao->visivel && $secao->no_menu) · aparece no menu @endif
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 align-items-center">
                    @unless ($secao->ehInicio())
                        <form method="POST" action="{{ route('secoes.mover', $secao) }}">
                            @csrf
                            <input type="hidden" name="direcao" value="sobe">
                            <button class="btn btn-outline-secondary btn-sm" type="submit" aria-label="Subir {{ $secao->titulo }}"
                                @disabled($loop->index <= 1)>↑</button>
                        </form>
                        <form method="POST" action="{{ route('secoes.mover', $secao) }}">
                            @csrf
                            <input type="hidden" name="direcao" value="desce">
                            <button class="btn btn-outline-secondary btn-sm" type="submit" aria-label="Descer {{ $secao->titulo }}"
                                @disabled($loop->last)>↓</button>
                        </form>
                        <form method="POST" action="{{ route('secoes.visibilidade', $secao) }}">
                            @csrf
                            <button class="btn btn-outline-secondary btn-sm" type="submit">
                                {{ $secao->visivel ? 'Esconder' : 'Mostrar' }}
                            </button>
                        </form>
                    @endunless

                    <a href="{{ route('secoes.edit', $secao) }}" class="btn btn-dark btn-sm">Editar</a>

                    @unless ($secao->ehInicio())
                        <form method="POST" action="{{ route('secoes.destroy', $secao) }}"
                            onsubmit="return confirm('Excluir a seção &quot;{{ addslashes($secao->titulo) }}&quot;? Isso apaga o texto e as fotos dela.')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-outline-danger btn-sm" type="submit">Excluir</button>
                        </form>
                    @endunless
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
