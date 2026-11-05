@extends('layouts.admin')

@section('title', 'Convidado')

@section('content')
<div style="max-width: 640px;">
    <h1 class="mb-4">{{ $convidado->nome }}</h1>

    <div class="card card-body mb-3">
        <dl class="row mb-0">
            <dt class="col-sm-4">E-mail</dt><dd class="col-sm-8">{{ $convidado->email ?? '—' }}</dd>
            <dt class="col-sm-4">Telefone</dt><dd class="col-sm-8">{{ $convidado->telefone ?? '—' }}</dd>
            <dt class="col-sm-4">Presença</dt>
            <dd class="col-sm-8">
                @if ($convidado->confirma_presenca === true) Confirmada
                @elseif ($convidado->confirma_presenca === false) Não vai
                @else Sem resposta @endif
            </dd>
            <dt class="col-sm-4">Adultos / crianças</dt><dd class="col-sm-8">{{ $convidado->adultos }} / {{ $convidado->criancas }}</dd>
            <dt class="col-sm-4">Observações</dt><dd class="col-sm-8">{{ $convidado->observacoes ?? '—' }}</dd>
        </dl>
    </div>

    <div class="d-flex gap-2">
        <a href="{{ route('convidados.index') }}" class="btn btn-outline-secondary">Voltar</a>
        <a href="{{ route('convidados.edit', $convidado) }}" class="btn btn-dark">Editar</a>
        <form method="POST" action="{{ route('convidados.destroy', $convidado) }}"
            onsubmit="return confirm('Excluir este convidado?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger">Excluir</button>
        </form>
    </div>
</div>
@endsection
