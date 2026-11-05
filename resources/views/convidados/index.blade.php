@extends('layouts.admin')

@section('title', 'Convidados')

@section('content')
<div class="py-2">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0">Convidados</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('convidados.exportar') }}" class="btn btn-outline-dark">Exportar CSV</a>
            <a href="{{ route('convidados.create') }}" class="btn btn-success">Novo convidado</a>
        </div>
    </div>

    <form method="GET" action="{{ route('convidados.index') }}" class="row g-2 mb-3">
        <div class="col-md-6">
            <input type="search" name="q" class="form-control" placeholder="Buscar por nome ou e-mail"
                   value="{{ request('q') }}">
        </div>
        <div class="col-md-4">
            <select name="presenca" class="form-select" aria-label="Presença">
                <option value="">Todas as respostas</option>
                <option value="sim" @selected(request('presenca') === 'sim')>Confirmados</option>
                <option value="nao" @selected(request('presenca') === 'nao')>Não vão</option>
                <option value="pendente" @selected(request('presenca') === 'pendente')>Sem resposta</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-dark w-100">Filtrar</button>
        </div>
    </form>

    <table class="table table-hover table-striped align-middle">
        <thead>
            <tr>
                <th>Nome</th><th>E-mail</th><th>Telefone</th><th>Presença</th>
                <th>Adultos</th><th>Crianças</th><th>Ações</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($convidados as $c)
                <tr>
                    <td>{{ $c->nome }}</td>
                    <td>{{ $c->email }}</td>
                    <td>{{ $c->telefone }}</td>
                    <td>
                        @if ($c->confirma_presenca === true)
                            <span class="badge bg-success">Vai</span>
                        @elseif ($c->confirma_presenca === false)
                            <span class="badge bg-danger">Não vai</span>
                        @else
                            <span class="badge bg-secondary">Sem resposta</span>
                        @endif
                    </td>
                    <td>{{ $c->adultos }}</td>
                    <td>{{ $c->criancas }}</td>
                    <td class="d-flex gap-2">
                        <a href="{{ route('convidados.edit', $c->id) }}" class="btn btn-sm btn-warning">Editar</a>
                        <form method="POST" action="{{ route('convidados.destroy', $c->id) }}"
                              onsubmit="return confirm('Excluir este convidado?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">Excluir</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">Nenhum convidado encontrado.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{ $convidados->links() }}
</div>
@endsection