@extends('layouts.app')

@section('title', 'Entrar · Área dos noivos')

@section('content')
<div class="pagina">
    <div class="mx-auto px-3" style="max-width: 420px;">
        <h1 class="mb-4">Área dos noivos</h1>

        @if ($errors->any())
            <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="cartao-form">
            @csrf
            <div class="mb-3">
                <label for="email" class="form-label">E-mail</label>
                <input type="email" id="email" name="email" class="form-control" required autofocus
                    autocomplete="username" value="{{ old('email') }}">
            </div>
            <div class="mb-4">
                <label for="password" class="form-label">Senha</label>
                <input type="password" id="password" name="password" class="form-control" required
                    autocomplete="current-password">
            </div>
            <button type="submit" class="btn btn-ouro w-100">Entrar</button>
        </form>
    </div>
</div>
@endsection
