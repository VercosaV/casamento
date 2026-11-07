@extends('layouts.admin')

@section('title', 'Quem presenteou')

@section('content')
<div class="py-2">
    <h1 class="mb-3">Quem presenteou</h1>

    <div class="table-responsive">
    <table class="table table-striped align-middle">
        <thead>
            <tr><th>Quando</th><th>Código</th><th>Convidado</th><th>Presentes</th><th>Total</th><th>Forma</th><th>Situação</th><th>Mensagem</th><th>Ações</th></tr>
        </thead>
        <tbody>
            @forelse ($pedidos as $p)
                @php
                    $formaRotulo = ['stripe' => 'Pagamento online', 'pix' => 'Pix direto', 'comprar' => 'Comprar o presente'][$p->forma] ?? $p->forma;
                    [$rotulo, $cor] = match ($p->status_efetivo) {
                        'paga'      => ['Paga', 'success'],
                        'pendente'  => [$p->forma === 'pix' ? 'Aguardando o Pix' : 'Aguardando pagamento', 'warning text-dark'],
                        'reservada' => ['Reservado, falta chegar', 'info text-dark'],
                        'entregue'  => ['Recebido', 'success'],
                        'expirada'  => ['Expirada', 'secondary'],
                        'cancelada' => ['Cancelada', 'secondary'],
                        default     => ['Falhou', 'danger'],
                    };
                @endphp
                <tr>
                    <td>{{ $p->created_at->format('d/m/Y H:i') }}</td>
                    <td><code>{{ $p->codigo }}</code></td>
                    <td>{{ $p->convidado->nome ?? 'Convidado removido' }}</td>
                    <td>
                        @foreach ($p->itens as $i)
                            <div>{{ $i->presente->nome }}@if ($i->presente->cotas_total > 1) <span class="text-muted">· {{ $i->quantidade_cotas }} cota(s)</span>@endif</div>
                        @endforeach
                    </td>
                    <td>R$ {{ number_format($p->total_reais, 2, ',', '.') }}</td>
                    <td>{{ $formaRotulo }}</td>
                    <td><span class="badge bg-{{ $cor }}">{{ $rotulo }}</span></td>
                    <td>{{ $p->mensagem }}</td>
                    <td>
                        <div class="d-flex flex-wrap gap-1">
                            @if ($p->forma === 'pix' && $p->status !== 'paga')
                                <form method="POST" action="{{ route('pedidos.confirmar', $p) }}"
                                    onsubmit="return confirm('Confirmar que o Pix de R$ {{ number_format($p->total_reais, 2, ',', '.') }} chegou?')">
                                    @csrf <button class="btn btn-success btn-sm" type="submit">Pix chegou</button>
                                </form>
                            @endif
                            @if ($p->forma === 'comprar' && $p->status === 'reservada')
                                <form method="POST" action="{{ route('pedidos.entregar', $p) }}">
                                    @csrf <button class="btn btn-success btn-sm" type="submit">Recebi</button>
                                </form>
                            @endif
                            @if (in_array($p->forma, ['pix', 'comprar']) && in_array($p->status, ['pendente', 'reservada']))
                                <form method="POST" action="{{ route('pedidos.cancelar', $p) }}"
                                    onsubmit="return confirm('Cancelar este pedido e devolver as cotas para a lista?')">
                                    @csrf <button class="btn btn-outline-danger btn-sm" type="submit">Cancelar</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9">Nenhum pedido ainda.</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>

    {{ $pedidos->links() }}
</div>
@endsection
