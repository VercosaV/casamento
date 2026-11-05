@extends('layouts.admin')

@section('title', 'Meu perfil')

@section('content')
<div style="max-width: 560px;">
    <h1 class="mb-4">Meu perfil</h1>

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">@foreach ($errors->all() as $erro)<li>{{ $erro }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('perfil.update') }}" class="card card-body">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label for="nome" class="form-label">Nome</label>
            <input type="text" id="nome" name="nome" class="form-control" required value="{{ old('nome', $usuario->name) }}">
        </div>
        <div class="mb-4">
            <label for="email" class="form-label">E-mail (usado para entrar)</label>
            <input type="email" id="email" name="email" class="form-control" required autocomplete="username"
                value="{{ old('email', $usuario->email) }}">
        </div>

        <h2 class="h5">Trocar senha (opcional)</h2>
        <p class="small text-muted">Deixe em branco para manter a senha atual.</p>
        <div class="mb-3">
            <label for="senha_atual" class="form-label">Senha atual</label>
            <input type="password" id="senha_atual" name="senha_atual" class="form-control" autocomplete="current-password">
        </div>
        <div class="mb-3">
            <label for="senha" class="form-label">Nova senha (mínimo 8 caracteres)</label>
            <input type="password" id="senha" name="senha" class="form-control" autocomplete="new-password">
        </div>
        <div class="mb-4">
            <label for="senha_confirmation" class="form-label">Repita a nova senha</label>
            <input type="password" id="senha_confirmation" name="senha_confirmation" class="form-control" autocomplete="new-password">
        </div>

        <div><button type="submit" class="btn btn-ouro">Salvar perfil</button></div>
    </form>
</div>
@endsection
