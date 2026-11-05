@extends('layouts.admin')

@section('title', 'Editar seção')

@section('content')
<div class="mb-3"><a href="{{ route('secoes.index') }}">← Voltar para a lista</a></div>
<h1 class="mb-4">Editar: {{ $secao->titulo }}</h1>

@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        <ul class="mb-0">@foreach ($errors->all() as $erro)<li>{{ $erro }}</li>@endforeach</ul>
    </div>
@endif

<form method="POST" action="{{ route('secoes.update', $secao) }}" enctype="multipart/form-data" class="card card-body mb-4" style="max-width: 760px;">
    @csrf
    @method('PUT')

    <div class="mb-3">
        <label for="titulo" class="form-label">{{ $secao->ehInicio() ? 'Nomes do casal' : 'Título da seção' }}</label>
        <input type="text" id="titulo" name="titulo" class="form-control" required maxlength="120"
            value="{{ old('titulo', $secao->titulo) }}">
        @if ($secao->ehInicio())
            <div class="form-text">O símbolo & ganha a cor dourada automaticamente. Ex.: Victor &amp; Kamilly</div>
        @endif
    </div>

    @if ($secao->ehInicio())
        <div class="mb-3">
            <label for="data_evento" class="form-label">Data e hora do casamento</label>
            <input type="datetime-local" id="data_evento" name="data_evento" class="form-control" required
                value="{{ old('data_evento', $secao->data_evento?->format('Y-m-d\TH:i')) }}">
            <div class="form-text">Horário de Brasília. A contagem regressiva usa esta data.</div>
        </div>
    @endif

    <div class="mb-3">
        <label for="conteudo" class="form-label">{{ $secao->ehInicio() ? 'Mensagem de boas-vindas' : 'Texto' }}</label>
        <textarea id="conteudo" name="conteudo" class="form-control" rows="8" maxlength="5000">{{ old('conteudo', $secao->conteudo) }}</textarea>
        <div class="form-text">Deixe uma linha em branco para começar um novo parágrafo.</div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <label for="rotulo_menu" class="form-label">Nome no menu (opcional)</label>
            <input type="text" id="rotulo_menu" name="rotulo_menu" class="form-control" maxlength="40"
                value="{{ old('rotulo_menu', $secao->rotulo_menu) }}">
            <div class="form-text">Se ficar vazio, usa o título.</div>
        </div>
        <div class="col-md-6 d-flex align-items-end">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="no_menu" name="no_menu" value="1"
                    @checked(old('no_menu', $secao->no_menu))>
                <label class="form-check-label" for="no_menu">Mostrar link desta seção no menu do topo</label>
            </div>
        </div>
    </div>

    @if ($secao->tipo !== 'galeria')
        <div class="mb-3">
            <label for="imagem" class="form-label">{{ $secao->ehInicio() ? 'Foto de fundo do topo (opcional)' : 'Foto ao lado do texto (opcional)' }}</label>
            <input type="file" id="imagem" name="imagem" class="form-control" accept="image/jpeg,image/png,image/webp">
            <div class="form-text">jpg, png ou webp, até 4 MB.</div>
            @if ($secao->imagem_url)
                <div class="d-flex align-items-center gap-3 mt-2">
                    <img src="{{ $secao->imagem_url }}" alt="Imagem atual" class="img-thumbnail" style="max-width: 160px;">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="remover_imagem" name="remover_imagem" value="1">
                        <label class="form-check-label" for="remover_imagem">Remover esta imagem</label>
                    </div>
                </div>
            @endif
        </div>
    @endif

    <div><button type="submit" class="btn btn-ouro">Salvar seção</button></div>
</form>

@if ($secao->tipo === 'galeria')
    <h2 class="h3 mb-3">Fotos da galeria</h2>

    <form method="POST" action="{{ route('secoes.fotos.store', $secao) }}" enctype="multipart/form-data"
        class="card card-body mb-4" style="max-width: 760px;">
        @csrf
        <label for="fotos" class="form-label">Enviar fotos (até 12 por vez, 4 MB cada)</label>
        <div class="d-flex flex-wrap gap-2">
            <input type="file" id="fotos" name="fotos[]" class="form-control" style="max-width: 420px;"
                accept="image/jpeg,image/png,image/webp" multiple required>
            <button type="submit" class="btn btn-dark">Enviar</button>
        </div>
    </form>

    <div class="row g-3">
        @forelse ($secao->fotos as $foto)
            <div class="col-6 col-md-3">
                <div class="card h-100"><div class="card-body p-2">
                    <img src="{{ $foto->url }}" alt="Foto {{ $loop->iteration }}" class="foto-miniatura mb-2" loading="lazy">
                    <form method="POST" action="{{ route('fotos.destroy', $foto) }}" onsubmit="return confirm('Remover esta foto?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-sm w-100">Remover</button>
                    </form>
                </div></div>
            </div>
        @empty
            <p class="text-muted">Nenhuma foto ainda. Enquanto estiver vazio, o convite mostra um aviso no lugar do carrossel.</p>
        @endforelse
    </div>
@endif
@endsection
