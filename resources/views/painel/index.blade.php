@extends('layouts.app')

@section('title', 'Painel dos noivos')

@section('content')
@php
    $cartoes = [
        ['Convidados cadastrados', $stats['convidados']],
        ['Confirmados', $stats['confirmados']],
        ['Não vão', $stats['recusados']],
        ['Sem resposta', $stats['pendentes']],
        ['Adultos confirmados', $stats['adultos']],
        ['Crianças confirmadas', $stats['criancas']],
        ['Pessoas esperadas', $stats['pessoas']],
        ['Presentes com escolha', $stats['presentes']],
        ['Cotas reservadas', $stats['cotas']],
        ['Valor reservado', 'R$ ' . number_format($stats['valor'], 2, ',', '.')],
    ];
@endphp
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">Painel dos noivos</h1>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-outline-secondary btn-sm">Sair</button>
        </form>
    </div>

    <div class="d-flex flex-wrap gap-2 mb-4">
        <a href="{{ route('convidados.index') }}" class="btn btn-dark">Convidados</a>
        <a href="{{ route('presentes.admin') }}" class="btn btn-dark">Presentes</a>
        <a href="{{ route('contribuicoes.index') }}" class="btn btn-dark">Quem presenteou</a>
        <a href="{{ route('convidados.exportar') }}" class="btn btn-outline-dark">Exportar convidados (CSV)</a>
    </div>

    <div class="row g-3 mb-5">
        @foreach ($cartoes as [$titulo, $valor])
            <div class="col-6 col-md-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted small">{{ $titulo }}</div>
                        <div class="fs-4 fw-semibold">{{ $valor }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <h2 class="h4">Últimas escolhas de presentes</h2>
    <table class="table table-striped align-middle">
        <thead>
            <tr><th>Quando</th><th>Presente</th><th>Convidado</th><th>Cotas</th><th>Mensagem</th></tr>
        </thead>
        <tbody>
            @forelse ($recentes as $c)
                <tr>
                    <td>{{ $c->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $c->presente->nome }}</td>
                    <td>{{ $c->convidado->nome ?? 'Convidado removido' }}</td>
                    <td>{{ $c->quantidade_cotas }}</td>
                    <td>{{ $c->mensagem }}</td>
                </tr>
            @empty
                <tr><td colspan="5">Nenhuma escolha ainda.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection