@extends('layouts.app')

@section('title', 'Minha lista')

@section('content')
<div class="pagina">
    <div class="container" style="max-width: 860px;">
        <h1 class="mb-2">Minha lista</h1>

        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <ul class="mb-0">@foreach ($errors->all() as $erro)<li>{{ $erro }}</li>@endforeach</ul>
            </div>
        @endif

        @if ($linhas->isEmpty())
            <p class="text-center text-muted my-5">Sua lista está vazia.</p>
            <p class="text-center"><a href="{{ route('presentes.index') }}" class="btn btn-ouro">Escolher presentes</a></p>
        @else
            <p class="text-center text-muted mb-4">Confira os presentes e escolha como quer enviá-los.</p>

            <div class="cartao-form mb-4">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Presente</th><th class="text-center">Cotas</th><th class="text-end">Valor</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($linhas as $l)
                            <tr>
                                <td>
                                    {{ $l['presente']->nome }}
                                    @if ($l['indisponivel'])
                                        <div class="small text-danger">Restam só {{ $l['presente']->cotas_restantes }} cota(s). Ajuste na lista de presentes ou tire da lista.</div>
                                    @endif
                                </td>
                                <td class="text-center">{{ $l['cotas'] }}</td>
                                <td class="text-end">R$ {{ number_format($l['valor_centavos'] / 100, 2, ',', '.') }}</td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('carrinho.remover', $l['presente']) }}">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-link btn-sm text-danger p-0" type="submit">Tirar</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr><th colspan="2" class="text-end">Total</th><th class="text-end">R$ {{ number_format($total / 100, 2, ',', '.') }}</th><th></th></tr>
                    </tfoot>
                </table>
                <div class="mt-3"><a href="{{ route('presentes.index') }}">← Escolher mais presentes</a></div>
            </div>

            <form method="POST" action="{{ route('pedido.store') }}" class="cartao-form">
                @csrf
                <h2 class="h4 mb-3">Como você quer enviar?</h2>

                @php
                    $opcoes = [
                        'comprar' => ['Comprar o presente', 'Você mesmo compra na loja que preferir e entrega aos noivos. Nós reservamos o presente para ninguém comprar o mesmo.'],
                        'stripe' => ['Pagar online (cartão ou Pix)', 'Você paga na página segura do Stripe e os noivos compram depois. Se o Pix estiver ativo, o QR Code aparece lá. Não guardamos dados do cartão.'],
                        'pix' => ['Enviar um Pix direto para os noivos', 'Mostramos a chave Pix e o valor exato. Os noivos confirmam quando o dinheiro chegar.'],
                    ];
                    $marcada = old('forma') ?: collect($opcoes)->keys()->first(fn ($k) => $formas[$k]['ativa']);
                @endphp

                @foreach ($opcoes as $chave => [$titulo, $texto])
                    <div class="opcao-envio {{ $formas[$chave]['ativa'] ? '' : 'desativada' }}">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="forma" id="forma-{{ $chave }}" value="{{ $chave }}"
                                @checked($marcada === $chave) @disabled(! $formas[$chave]['ativa'])>
                            <label class="form-check-label" for="forma-{{ $chave }}">
                                <strong>{{ $titulo }}</strong>
                                <span class="d-block small text-muted">{{ $formas[$chave]['ativa'] ? $texto : $formas[$chave]['motivo'] }}</span>
                            </label>
                        </div>
                    </div>
                @endforeach

                @unless (session('convidado_id'))
                    <div class="row mt-3">
                        <div class="col-md-6 mb-3">
                            <label for="nome" class="form-label">Seu nome</label>
                            <input type="text" id="nome" name="nome" class="form-control" required value="{{ old('nome') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Seu e-mail</label>
                            <input type="email" id="email" name="email" class="form-control" required value="{{ old('email') }}">
                        </div>
                    </div>
                @endunless

                <div class="mb-3 mt-3">
                    <label for="mensagem" class="form-label">Mensagem para os noivos (opcional)</label>
                    <textarea id="mensagem" name="mensagem" class="form-control" rows="2" maxlength="400">{{ old('mensagem') }}</textarea>
                </div>

                <button type="submit" class="btn btn-ouro w-100" @disabled($temIndisponivel)>Finalizar</button>
                @if ($temIndisponivel)
                    <div class="form-text text-center text-danger">Resolva os avisos em vermelho acima para finalizar.</div>
                @endif
            </form>
        @endif
    </div>
</div>
@endsection
