@extends('layouts.admin')

@section('title', 'Gerenciar presentes')

@section('content')
<div class="py-2">
    <h1 class="mb-3">Gerenciar presentes</h1>
    <a href="{{ route('presentes.create') }}" class="btn btn-success mb-3">Novo presente</a>

    <table class="table table-hover table-striped align-middle">
        <thead>
            <tr>
                <th>Presente</th>
                <th>Categoria</th>
                <th>Valor</th>
                <th>Cotas</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($presentes as $p)
                <tr>
                    <td>{{ $p->nome }}</td>
                    <td>{{ $p->categoria ?? '—' }}</td>
                    <td>R$ {{ number_format($p->valor, 2, ',', '.') }}</td>
                    <td>{{ $p->cotas_reservadas }} / {{ $p->cotas_total }}</td>
                    <td class="d-flex gap-2">
                        <a href="{{ route('presentes.edit', $p->id) }}" class="btn btn-sm btn-warning">Editar</a>
                        <form method="POST" action="{{ route('presentes.destroy', $p->id) }}"
                              onsubmit="return confirm('Excluir este presente e as escolhas já feitas nele?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">Excluir</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection