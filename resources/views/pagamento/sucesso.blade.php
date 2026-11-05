@extends('layouts.app')

@section('title', 'Obrigado!')

@section('content')
<div class="pagina">
    <div class="mx-auto px-3 text-center" style="max-width: 560px;">
        @if ($contribuicao->status === 'paga')
            <h1 class="mb-3">Pagamento confirmado!</h1>
            <p class="fs-5">Muito obrigado pelo presente: <strong>{{ $contribuicao->presente->nome }}</strong>.</p>
            <p class="text-muted">Os noivos já foram avisados. Foi um prazer ter você neste momento.</p>
        @elseif ($contribuicao->status_efetivo === 'pendente')
            <h1 class="mb-3">Estamos confirmando seu pagamento</h1>
            <p class="fs-5">Recebemos o seu retorno e estamos aguardando o aviso do Stripe.</p>
            <p class="text-muted">Isso costuma levar poucos segundos. <strong>Não precisa pagar de novo.</strong>
                Se você pagou com Pix, a confirmação pode levar um pouco mais. Atualize esta página em instantes.</p>
        @else
            <h1 class="mb-3">Este pagamento não foi concluído</h1>
            <p class="text-muted">Nenhuma cobrança foi confirmada. Você pode escolher o presente de novo na lista.</p>
        @endif

        <a href="{{ route('presentes.index') }}" class="btn btn-ouro mt-3">Voltar para a lista de presentes</a>
    </div>
</div>
@endsection
