<?php

namespace App\Services;

use App\Models\Pedido;
use Stripe\Checkout\Session;
use Stripe\Event;
use Stripe\StripeClient;
use Stripe\Webhook;

// ÚNICO ponto do sistema que conversa com o Stripe.
// Vantagens: (1) o resto do código não conhece o SDK; (2) nos testes este objeto é
// trocado por um falso, então nenhum teste faz chamada de rede nem usa chave de verdade.
class StripeGateway
{
    private ?StripeClient $cliente = null;

    private function cliente(): StripeClient
    {
        return $this->cliente ??= new StripeClient(config('stripe.sk'));
    }

    /** Cria a página de pagamento (Checkout) no Stripe para este pedido: uma linha por presente. */
    public function criarCheckout(Pedido $pedido): Session
    {
        // loadMissing: usa o que já veio carregado e só consulta o banco o que faltar.
        $pedido->loadMissing('itens.presente', 'convidado');

        $linhas = $pedido->itens->map(fn ($item) => [
            'quantity' => $item->quantidade_cotas,
            'price_data' => [
                'currency' => 'brl',
                // Tudo calculado AQUI no servidor, a partir do banco. Nunca do formulário.
                'unit_amount' => intdiv($item->valor_centavos, $item->quantidade_cotas),
                'product_data' => [
                    'name' => $item->presente->cotas_total > 1 ? $item->presente->nome.' (cota)' : $item->presente->nome,
                ],
            ],
        ])->all();

        $parametros = [
            'mode' => 'payment',
            'line_items' => $linhas,
            // Amarram o pagamento ao NOSSO pedido: é assim que o webhook sabe de quem é.
            'client_reference_id' => (string) $pedido->id,
            'metadata' => ['pedido_id' => (string) $pedido->id, 'codigo' => $pedido->codigo],
            'payment_intent_data' => [
                'description' => 'Presentes de casamento (pedido '.$pedido->codigo.')',
                'metadata' => ['pedido_id' => (string) $pedido->id, 'codigo' => $pedido->codigo],
            ],
            'expires_at' => $pedido->expira_em->timestamp,
            'locale' => 'pt-BR',
            'success_url' => route('pagamento.sucesso', $pedido->token),
            'cancel_url' => route('pagamento.cancelado', $pedido->token),
        ];

        // Só envia o e-mail se existir. O SDK transforma null em "customer_email=" (vazio),
        // e o Stripe pode recusar a sessão por e-mail inválido.
        if (filled($pedido->convidado?->email)) {
            $parametros['customer_email'] = $pedido->convidado->email;
        }

        return $this->cliente()->checkout->sessions->create($parametros, [
            // Se a requisição for repetida (queda de rede, duplo clique), o Stripe devolve a MESMA sessão.
            'idempotency_key' => 'pedido-'.$pedido->id,
        ]);
    }

    /** Consulta o Stripe (com a nossa chave secreta) para saber a situação real da sessão. */
    public function buscarCheckout(string $sessionId): Session
    {
        return $this->cliente()->checkout->sessions->retrieve($sessionId);
    }

    /** Fecha a página de pagamento, para quem desistiu não ficar com cotas seguradas. */
    public function expirarCheckout(string $sessionId): void
    {
        $this->cliente()->checkout->sessions->expire($sessionId);
    }

    /**
     * Confere a assinatura do aviso (webhook) e devolve o evento.
     * Lança exceção se a assinatura não bater: qualquer um pode fazer um POST na nossa URL,
     * só o Stripe sabe assinar com o segredo.
     */
    public function eventoDoWebhook(string $corpo, ?string $assinatura): Event
    {
        return Webhook::constructEvent($corpo, (string) $assinatura, (string) config('stripe.webhook_secret'));
    }
}
