<?php

namespace App\Http\Controllers;

use App\Models\Contribuicao;
use App\Services\StripeGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;

class PagamentoController extends Controller
{
    // Para onde o Stripe devolve a pessoa depois de pagar.
    // ATENÇÃO: estar nesta página NÃO prova que pagou (qualquer um digita a URL).
    // Por isso perguntamos ao Stripe, com a nossa chave secreta, qual é a situação real.
    public function sucesso(string $token, StripeGateway $stripe)
    {
        $contribuicao = Contribuicao::where('token', $token)->with('presente')->firstOrFail();

        if ($contribuicao->status === Contribuicao::PENDENTE && $contribuicao->stripe_session_id) {
            try {
                $sessao = $stripe->buscarCheckout($contribuicao->stripe_session_id);

                if ($sessao->payment_status === 'paid' && $this->valorConfere($contribuicao, $sessao)) {
                    $contribuicao->marcarComoPaga($this->paymentIntentId($sessao));
                }
            } catch (ApiErrorException $e) {
                report($e); // sem problema: o webhook confirma depois
            }
        }

        return view('pagamento.sucesso', compact('contribuicao'));
    }

    // A pessoa clicou em "voltar" na página do Stripe: fecha a sessão e devolve as cotas na hora.
    public function cancelado(string $token, StripeGateway $stripe)
    {
        $contribuicao = Contribuicao::where('token', $token)->firstOrFail();

        if ($contribuicao->status === Contribuicao::PENDENTE) {
            if ($contribuicao->stripe_session_id) {
                try {
                    $stripe->expirarCheckout($contribuicao->stripe_session_id);
                } catch (ApiErrorException $e) {
                    report($e);
                }
            }

            $contribuicao->liberar(Contribuicao::CANCELADA);
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
        $contribuicao = Contribuicao::find($sessao->client_reference_id ?? null);

        if (! $contribuicao) {
            // Evento de outra coisa (ou de outro sistema na mesma conta Stripe). Responde 200 para
            // o Stripe não ficar reenviando.
            return response('ignorado', 200);
        }

        switch ($evento->type) {
            case 'checkout.session.completed':
                // Cartão: já vem "paid". Pix/boleto: vem "unpaid" e o aviso de pagamento chega depois.
                if ($sessao->payment_status === 'paid') {
                    $this->confirmar($contribuicao, $sessao);
                }
                break;

            case 'checkout.session.async_payment_succeeded':
                $this->confirmar($contribuicao, $sessao);
                break;

            case 'checkout.session.async_payment_failed':
                $contribuicao->liberar(Contribuicao::FALHOU);
                break;

            case 'checkout.session.expired':
                $contribuicao->liberar(Contribuicao::EXPIRADA);
                break;
        }

        return response('ok', 200);
    }

    private function confirmar(Contribuicao $contribuicao, $sessao): void
    {
        // Confere se o valor pago é o valor que nós calculamos. Se não for, não marca como paga.
        if (! $this->valorConfere($contribuicao, $sessao)) {
            Log::critical('Stripe: valor pago diferente do esperado. Conferir no painel do Stripe.', [
                'contribuicao_id' => $contribuicao->id,
                'esperado' => $contribuicao->valor_centavos,
                'recebido' => $sessao->amount_total ?? null,
            ]);

            return;
        }

        $estavaLiberada = in_array($contribuicao->status, [
            Contribuicao::EXPIRADA, Contribuicao::CANCELADA, Contribuicao::FALHOU,
        ], true);

        $contribuicao->marcarComoPaga($this->paymentIntentId($sessao));

        // Pagou DEPOIS de as cotas terem sido liberadas (ex.: Pix pago após o prazo). O dinheiro
        // entrou, então a contribuição vale; mas se outra pessoa já pegou as cotas, falta presente.
        if ($estavaLiberada) {
            $presente = $contribuicao->presente()->first();
            $ocupadas = (int) $presente->contribuicoesAtivas()->sum('quantidade_cotas');

            if ($ocupadas > $presente->cotas_total) {
                Log::critical('Stripe: pagamento recebido depois das cotas serem liberadas, e o presente ficou acima do limite. Avaliar reembolso.', [
                    'contribuicao_id' => $contribuicao->id,
                    'presente_id' => $presente->id,
                ]);
            }
        }
    }

    private function valorConfere(Contribuicao $contribuicao, $sessao): bool
    {
        return (int) ($sessao->amount_total ?? -1) === (int) $contribuicao->valor_centavos
            && strtolower((string) ($sessao->currency ?? '')) === 'brl';
    }

    private function paymentIntentId($sessao): ?string
    {
        $pi = $sessao->payment_intent ?? null;

        return is_object($pi) ? $pi->id : $pi;
    }
}
