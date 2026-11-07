<?php

namespace App\Support;

use App\Models\Pedido;

// Conferências que o webhook e a página de retorno fazem sobre uma sessão do Stripe.
class ConferenciaStripe
{
    /** O valor e a moeda pagos são os que NÓS calculamos? Se não forem, não confirma. */
    public static function valorConfere(Pedido $pedido, $sessao): bool
    {
        return (int) ($sessao->amount_total ?? -1) === (int) $pedido->total_centavos
            && strtolower((string) ($sessao->currency ?? '')) === 'brl';
    }

    public static function paymentIntentId($sessao): ?string
    {
        $pi = $sessao->payment_intent ?? null;

        return is_object($pi) ? $pi->id : $pi;
    }
}
