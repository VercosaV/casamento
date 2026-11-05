@extends('layouts.app')

@section('title', 'Quem presenteou')

@section('content')
<div class="container py-5">
    <h1 class="mb-3">Quem presenteou</h1>

    <table class="table table-striped align-middle">
        <thead>
            <tr><th>Quando</th><th>Convidado</th><th>Presente</th><th>Cotas</th><th>Mensagem</th></tr>
        </thead>
        <tbody>
            @forelse ($contribuicoes as $c)
                <tr>
                    <td>{{ $c->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $c->convidado->nome ?? 'Convidado removido' }}</td>
                    <td>{{ $c->presente->nome }}</td>
                    <td>{{ $c->quantidade_cotas }}</td>
                    <td>{{ $c->mensagem }}</td>
                </tr>
            @empty
                <tr><td colspan="5">Nenhuma escolha ainda.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{ $contribuicoes->links() }}
</div>
@endsection