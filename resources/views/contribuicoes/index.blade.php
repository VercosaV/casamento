@extends('layouts.admin')

@section('title', 'Quem presenteou')

@section('content')
<div class="py-2">
    <h1 class="mb-3">Quem presenteou</h1>

    <table class="table table-striped align-middle">
        <thead>
            <tr><th>Quando</th><th>Convidado</th><th>Presente</th><th>Cotas</th><th>Valor</th><th>Situação</th><th>Mensagem</th></tr>
        </thead>
        <tbody>
            @forelse ($contribuicoes as $c)
                <tr>
                    <td>{{ $c->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $c->convidado->nome ?? 'Convidado removido' }}</td>
                    <td>{{ $c->presente->nome }}</td>
                    <td>{{ $c->quantidade_cotas }}</td>
                    <td>{{ $c->valor_reais !== null ? 'R$ '.number_format($c->valor_reais, 2, ',', '.') : '—' }}</td>
                    <td>
                        @php
                            [$rotulo, $cor] = match ($c->status_efetivo) {
                                'paga'      => ['Paga', 'success'],
                                'pendente'  => ['Aguardando pagamento', 'warning text-dark'],
                                'reservada' => ['Reservada (sem pagamento)', 'info text-dark'],
                                'expirada'  => ['Expirada', 'secondary'],
                                'cancelada' => ['Cancelada', 'secondary'],
                                default     => ['Falhou', 'danger'],
                            };
                        @endphp
                        <span class="badge bg-{{ $cor }}">{{ $rotulo }}</span>
                    </td>
                    <td>{{ $c->mensagem }}</td>
                </tr>
            @empty
                <tr><td colspan="7">Nenhuma escolha ainda.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{ $contribuicoes->links() }}
</div>
@endsection