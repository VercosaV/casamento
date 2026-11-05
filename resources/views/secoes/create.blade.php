@extends('layouts.admin')

@section('title', 'Nova seção')

@section('content')
<div style="max-width: 640px;">
    <h1 class="mb-4">Nova seção</h1>

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">@foreach ($errors->all() as $erro)<li>{{ $erro }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('secoes.store') }}" class="card card-body">
        @csrf
        <div class="mb-3">
            <label for="titulo" class="form-label">Título</label>
            <input type="text" id="titulo" name="titulo" class="form-control" required maxlength="120"
                value="{{ old('titulo') }}" placeholder="Ex.: Local da cerimônia">
        </div>
        <div class="mb-3">
            <label for="tipo" class="form-label">Tipo de seção</label>
            <select id="tipo" name="tipo" class="form-select">
                <option value="texto" @selected(old('tipo', 'texto') === 'texto')>Texto (com foto opcional)</option>
                <option value="galeria" @selected(old('tipo') === 'galeria')>Galeria de fotos (carrossel)</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="conteudo" class="form-label">Texto (opcional agora, dá para editar depois)</label>
            <textarea id="conteudo" name="conteudo" class="form-control" rows="5" maxlength="5000">{{ old('conteudo') }}</textarea>
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-ouro">Criar seção</button>
            <a href="{{ route('secoes.index') }}" class="btn btn-outline-secondary">Cancelar</a>
        </div>
    </form>
</div>
@endsection
