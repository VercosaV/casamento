<?php

return [
    // Chave SECRETA (sk_test_... em testes, sk_live_... em produção). Só no .env, nunca no código.
    // Obs.: a chave pública (pk_) não é usada: o Checkout hospedado pelo Stripe não precisa dela.
    'sk' => env('STRIPE_SK'),

    // Segredo de assinatura do webhook (whsec_...). Prova que o aviso veio mesmo do Stripe.
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),

    // Sem chave, o site continua funcionando no modo "reservar presente" (sem pagamento online).
    'habilitado' => filled(env('STRIPE_SK')),

    // Por quanto tempo as cotas ficam seguradas enquanto a pessoa paga.
    // O Stripe exige no mínimo 30 minutos para expirar uma sessão; por isso o piso é 31.
    'minutos_reserva' => max(31, (int) env('STRIPE_MINUTOS_RESERVA', 35)),

    // Regra nossa (não é do Stripe): evita cobranças minúsculas, em que a taxa pesa mais que o presente.
    'valor_minimo_centavos' => (int) env('STRIPE_VALOR_MINIMO_CENTAVOS', 500),
];
