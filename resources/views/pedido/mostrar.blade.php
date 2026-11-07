@extends('layouts.app')

@section('title', 'Seu pedido')

@section('content')
@php
    $reais = fn ($centavos) => 'R$ '.number_format($centavos / 100, 2, ',', '.');
    $status = $pedido->status_efetivo;
@endphp
<div class="pagina">
    <div class="mx-auto px-3" style="max-width: 640px;">
        <h1 class="mb-1">Seu pedido</h1>
        <p class="text-center text-muted mb-4">Código <strong>{{ $pedido->codigo }}</strong></p>

        <div class="cartao-form mb-4">
            <ul class="list-unstyled mb-3">
                @foreach ($pedido->itens as $item)
                    <li class="d-flex justify-content-between py-1">
                        <span>{{ $item->presente->nome }}@if ($item->presente->cotas_total > 1) · {{ $item->quantidade_cotas }} {{ $item->quantidade_cotas === 1 ? 'cota' : 'cotas' }}@endif</span>
                        <span>{{ $reais($item->valor_centavos) }}</span>
                    </li>
                @endforeach
            </ul>
            <div class="d-flex justify-content-between border-top pt-2 fw-bold"><span>Total</span><span>{{ $reais($pedido->total_centavos) }}</span></div>
        </div>

        {{-- ---------- PAGA / ENTREGUE ---------- --}}
        @if ($status === 'paga')
            <div class="alert alert-success text-center"><strong>Pagamento confirmado.</strong> Muito obrigado pelo presente!</div>
        @elseif ($status === 'entregue')
            <div class="alert alert-success text-center"><strong>Presente recebido.</strong> Muito obrigado!</div>

        {{-- ---------- COMPRAR ---------- --}}
        @elseif ($status === 'reservada')
            <div class="cartao-form">
                <h2 class="h5">Reservado para você</h2>
                <p>Nós seguramos {{ $pedido->itens->count() === 1 ? 'esse presente' : 'esses presentes' }} para ninguém comprar o mesmo. Agora é só comprar e entregar.</p>
                @if (filled(config('casamento.entrega.endereco')))
                    <p class="mb-1"><strong>Onde entregar:</strong></p>
                    <p style="white-space: pre-line;">{{ config('casamento.entrega.endereco') }}</p>
                @else
                    <p class="text-muted">Combine a entrega com os noivos.</p>
                @endif
                @foreach ($pedido->itens as $item)
                    @if ($item->presente->link)
                        <p class="mb-1"><a href="{{ $item->presente->link }}" target="_blank" rel="noopener noreferrer">Ver "{{ $item->presente->nome }}" na loja</a></p>
                    @endif
                @endforeach
                <p class="small text-muted mt-3 mb-0">Mudou de ideia? Avise os noivos e eles liberam o presente para outra pessoa.</p>
            </div>

        {{-- ---------- PIX DIRETO ---------- --}}
        @elseif ($pedido->forma === 'pix' && $status === 'pendente')
            <div class="cartao-form">
                <h2 class="h5">Envie o Pix</h2>
                <p class="mb-1">Valor exato: <strong>{{ $reais($pedido->total_centavos) }}</strong></p>
                @if (filled(config('casamento.pix.beneficiario')))
                    <p class="mb-1">Para: <strong>{{ config('casamento.pix.beneficiario') }}</strong></p>
                @endif
                <label for="chave-pix" class="form-label mt-2 mb-1">Chave Pix</label>
                <div class="input-group mb-3">
                    <input type="text" id="chave-pix" class="form-control" readonly value="{{ config('casamento.pix.chave') }}">
                    <button type="button" class="btn btn-contorno" id="copiar-pix">Copiar</button>
                </div>
                <p class="small mb-1">Se o seu banco permitir, escreva este código na mensagem do Pix: <strong>{{ $pedido->codigo }}</strong>. Ajuda os noivos a achar o seu pagamento.</p>
                <p class="small text-muted mb-0">Você não precisa avisar ninguém. Os noivos confirmam quando o dinheiro chegar. Seu pedido fica reservado até
                    {{ $pedido->expira_em->format('d/m/Y \à\s H:i') }}.</p>
            </div>
            @push('scripts')
                <script>
                    document.getElementById('copiar-pix')?.addEventListener('click', async (e) => {
                        const campo = document.getElementById('chave-pix');
                        try { await navigator.clipboard.writeText(campo.value); } catch (_) { campo.select(); document.execCommand('copy'); }
                        e.target.textContent = 'Copiado!';
                    });
                </script>
            @endpush

        {{-- ---------- STRIPE ---------- --}}
        @elseif ($pedido->forma === 'stripe' && $status === 'pendente')
            <div class="alert alert-warning text-center">
                <strong>Estamos confirmando seu pagamento.</strong><br>
                Costuma levar poucos segundos. <strong>Não precisa pagar de novo.</strong> Se pagou com Pix, pode demorar um pouco mais: atualize esta página em instantes.
            </div>

        {{-- ---------- EXPIRADO / CANCELADO / FALHOU ---------- --}}
        @else
            <div class="alert alert-secondary text-center">Este pedido não foi concluído e as cotas voltaram para a lista. Se você já enviou o dinheiro, fale com os noivos: eles conseguem confirmar.</div>
        @endif

        <p class="text-center mt-4"><a href="{{ route('presentes.index') }}" class="btn btn-ouro">Voltar para a lista de presentes</a></p>
    </div>
</div>
@endsection
