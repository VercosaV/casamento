<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Services\StripeGateway;
use App\Support\ConferenciaStripe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;

class PagamentoController extends Controller
{
    // A pessoa clicou em "voltar" na página do Stripe: fecha a sessão e devolve as cotas na hora.
    public function cancelado(string $token, StripeGateway $stripe)
    {
        $pedido = Pedido::where('token', $token)->where('forma', Pedido::FORMA_STRIPE)->firstOrFail();

        if ($pedido->status === Pedido::PENDENTE) {
            if ($pedido->stripe_session_id) {
                try {
                    $stripe->expirarCheckout($pedido->stripe_session_id);
                } catch (ApiErrorException $e) {
                    report($e);
                }
            }

            $pedido->liberar(Pedido::CANCELADA);
        }

        return redirect()->route('presentes.index')
            ->with('erro', 'Pagamento cancelado. Nenhuma cobrança foi feita.');
    }

    // O Stripe chama esta URL, direto do servidor dele, a cada novidade. É a fonte da verdade.
    public function webhook(Request $request, StripeGateway $stripe)
    {
        // Sem segredo configurado não há como verificar a assinatura: recusa, em vez de aceitar tudo.
        if (blank(config('stripe.webhook_secret'))) {
            Log::error('Webhook do Stripe recebido, mas STRIPE_WEBHOOK_SECRET não está configurado.');

            return response('webhook não configurado', 503);
        }

        try {
            // getContent() é o corpo CRU: a assinatura é calculada sobre os bytes exatos.
            $evento = $stripe->eventoDoWebhook($request->getContent(), $request->header('Stripe-Signature'));
        } catch (\UnexpectedValueException|SignatureVerificationException) {
            return response('assinatura inválida', 400);
        }

        $sessao = $evento->data->object;

        // Só pedidos da forma "stripe": o Pix manual e o "comprar" nunca são confirmados por aqui.
        $pedido = Pedido::where('forma', Pedido::FORMA_STRIPE)->find($sessao->client_reference_id ?? null);

        if (! $pedido) {
            // Evento de outra coisa (ou de outro sistema na mesma conta Stripe). Responde 200 para
            // o Stripe não ficar reenviando.
            return response('ignorado', 200);
        }

        switch ($evento->type) {
            case 'checkout.session.completed':
                // Cartão: já vem "paid". Pix/boleto: vem "unpaid" e o aviso de pagamento chega depois.
                if ($sessao->payment_status === 'paid') {
                    $this->confirmar($pedido, $sessao);
                }
                break;

            case 'checkout.session.async_payment_succeeded':
                $this->confirmar($pedido, $sessao);
                break;

            case 'checkout.session.async_payment_failed':
                $pedido->liberar(Pedido::FALHOU);
                break;

            case 'checkout.session.expired':
                $pedido->liberar(Pedido::EXPIRADA);
                break;
        }

        return response('ok', 200);
    }

    private function confirmar(Pedido $pedido, $sessao): void
    {
        // Confere se o valor pago é o valor que nós calculamos. Se não for, não marca como paga.
        if (! ConferenciaStripe::valorConfere($pedido, $sessao)) {
            Log::critical('Stripe: valor pago diferente do esperado. Conferir no painel do Stripe.', [
                'pedido_id' => $pedido->id,
                'esperado' => $pedido->total_centavos,
                'recebido' => $sessao->amount_total ?? null,
            ]);

            return;
        }

        $estavaLiberado = in_array($pedido->status, [Pedido::EXPIRADA, Pedido::CANCELADA, Pedido::FALHOU], true);

        $pedido->confirmar(ConferenciaStripe::paymentIntentId($sessao));

        // Pagou DEPOIS de as cotas terem sido liberadas (ex.: Pix pago após o prazo). O dinheiro
        // entrou, então o pedido vale; mas se outra pessoa já pegou as cotas, falta presente.
        if ($estavaLiberado && $pedido->presentesAcimaDoLimite()->isNotEmpty()) {
            Log::critical('Stripe: pagamento recebido depois das cotas serem liberadas, e um presente ficou acima do limite. Avaliar reembolso.', [
                'pedido_id' => $pedido->id,
                'presentes' => $pedido->presentesAcimaDoLimite()->pluck('id'),
            ]);
        }
    }
}
