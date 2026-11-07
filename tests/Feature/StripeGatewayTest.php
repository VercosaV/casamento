<?php

namespace Tests\Feature;

use App\Models\Pedido;
use App\Models\Convidado;
use App\Models\Contribuicao;
use App\Models\Presente;
use App\Services\StripeGateway;
use Tests\TestCase;

// Usa o StripeGateway REAL e o SDK real, mas com um cliente HTTP falso no lugar da rede.
// Assim conferimos o que seria ENVIADO ao Stripe, algo que um mock do gateway não mostra.
class StripeGatewayTest extends TestCase
{
    private object $http;

    protected function setUp(): void
    {
        parent::setUp();
        config(['stripe.sk' => 'sk_test_falsa']);

        $this->http = new class implements \Stripe\HttpClient\ClientInterface {
            public array $chamadas = [];

            public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null)
            {
                $this->chamadas[] = compact('method', 'absUrl', 'headers', 'params');

                return ['{"id":"cs_test_1","object":"checkout.session","url":"https://checkout.stripe.com/c/pay/cs_test_1"}', 200, []];
            }
        };
        \Stripe\ApiRequestor::setHttpClient($this->http);
    }

    protected function tearDown(): void
    {
        \Stripe\ApiRequestor::setHttpClient(\Stripe\HttpClient\CurlClient::instance());
        parent::tearDown();
    }

    // Pedido montado só em memória (sem banco): 1 presente de 3 cotas (2 cotas) + 1 presente inteiro.
    private function pedido(?string $email): Pedido
    {
        $cotas = new Presente(['nome' => 'Lua de mel', 'valor' => 100, 'cotas_total' => 3]);
        $inteiro = new Presente(['nome' => 'Jogo de panelas', 'valor' => 300, 'cotas_total' => 1]);

        $p = new Pedido(['total_centavos' => 6666 + 30000, 'token' => 'tok-123', 'codigo' => 'ABC234']);
        $p->id = 42;
        $p->expira_em = now()->addMinutes(35);
        $p->setRelation('convidado', new Convidado(['nome' => 'Ana', 'email' => $email]));
        $p->setRelation('itens', collect([
            (new Contribuicao(['quantidade_cotas' => 2, 'valor_centavos' => 6666]))->setRelation('presente', $cotas),
            (new Contribuicao(['quantidade_cotas' => 1, 'valor_centavos' => 30000]))->setRelation('presente', $inteiro),
        ]));

        return $p;
    }

    public function test_requisicao_enviada_ao_stripe_tem_o_formato_esperado(): void
    {
        $c = $this->pedido('ana@ex.com');

        $sessao = (new StripeGateway())->criarCheckout($c);

        $this->assertSame('https://checkout.stripe.com/c/pay/cs_test_1', $sessao->url);

        $x = $this->http->chamadas[0];
        $this->assertSame('post', strtolower($x['method']));
        $this->assertStringEndsWith('/v1/checkout/sessions', $x['absUrl']);
        $this->assertContains('Idempotency-Key: pedido-42', $x['headers']);

        $p = $x['params'];
        $this->assertSame('payment', $p['mode']);
        $this->assertCount(2, $p['line_items']);                                       // uma linha por presente
        $this->assertSame('brl', $p['line_items'][0]['price_data']['currency']);
        $this->assertSame(3333, $p['line_items'][0]['price_data']['unit_amount']);   // inteiro, em centavos, por cota
        $this->assertSame(2, $p['line_items'][0]['quantity']);
        $this->assertSame('Lua de mel (cota)', $p['line_items'][0]['price_data']['product_data']['name']);
        $this->assertSame(30000, $p['line_items'][1]['price_data']['unit_amount']);
        $this->assertSame('Jogo de panelas', $p['line_items'][1]['price_data']['product_data']['name']);
        $this->assertSame('42', $p['client_reference_id']);
        $this->assertSame('ana@ex.com', $p['customer_email']);
        $this->assertSame($c->expira_em->timestamp, $p['expires_at']);
        $this->assertStringEndsWith('/pagamento/sucesso/tok-123', $p['success_url']);
        $this->assertStringEndsWith('/pagamento/cancelado/tok-123', $p['cancel_url']);
    }

    public function test_convidado_sem_email_nao_envia_customer_email_vazio(): void
    {
        (new StripeGateway())->criarCheckout($this->pedido(null));

        $this->assertArrayNotHasKey('customer_email', $this->http->chamadas[0]['params']);
    }
}
