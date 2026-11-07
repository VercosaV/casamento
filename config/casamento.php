<?php

return [
    // Forma "Enviar Pix direto para os noivos". Sem a chave, essa opção não aparece para o convidado.
    'pix' => [
        'chave'         => env('PIX_CHAVE'),
        'beneficiario'  => env('PIX_BENEFICIARIO'),
        // Quantas horas as cotas ficam seguradas esperando o Pix (os noivos confirmam à mão).
        'horas_reserva' => max(1, (int) env('PIX_HORAS_RESERVA', 48)),
    ],

    // Forma "Comprar o presente": para onde o convidado leva ou envia a compra.
    'entrega' => [
        'endereco' => env('ENDERECO_ENTREGA'),
    ],
];
