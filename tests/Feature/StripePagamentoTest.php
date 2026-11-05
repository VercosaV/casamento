<?php

namespace Tests\Feature;

use App\Models\Contribuicao;
use App\Models\Convidado;
use App\Models\Presente;
use App\Services\StripeGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Mockery\MockInterface;
use Stripe\Checkout\Session;
use Stripe\WebhookSignature;
use Tests\TestCase;

// Nenhum teste aqui chama o Stripe de verdade:
// - criar/consultar/expirar sessão: o StripeGateway é trocado por um falso (mock);
// - webhook: usa o gateway REAL, mas só a parte offline (conferir assinatura), com segredo de mentira.
class StripePagamentoTest extends TestCase
{
    use RefreshDatabase;

    private const SEGREDO = 'whsec_segredo_de_teste';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'stripe.habilitado' => true,
            'stripe.sk' => 'sk_test_falsa',
            'stripe.webhook_secret' => self::SEGREDO,
        ]);
    }

    // ---------- auxiliares ----------

    private function envio(int $cotas = 1, array $extra = []): array
    {
        return array_merge([
            'quantidade_cotas' => $cotas,
            'nome' => 'Ana',
            'email' => 'ana@example.com',
            'mensagem' => 'Parabéns!',
        ], $extra);
    }

    private function sessaoFalsa(string $id = 'cs_test_123'): Session
    {
        return Session::constructFrom(['id' => $id, 'object' => 'checkout.session', 'url' => "https://checkout.stripe.com/c/pay/{$id}"]);
    }

    private function gatewayQueCria(string $id = 'cs_test_123'): MockInterface
    {
        return $this->mock(StripeGateway::class, function (MockInterface $m) use ($id) {
            $m->shouldReceive('criarCheckout')->once()->andReturn($this->sessaoFalsa($id));
        });
    }

    private function pendente(Presente $presente, int $cotas = 1, array $extra = []): Contribuicao
    {
        return Contribuicao::create(array_merge([
            'presente_id' => $presente->id,
            'convidado_id' => Convidado::factory()->create()->id,
            'quantidade_cotas' => $cotas,
            'status' => 'pendente',
            'valor_centavos' => $presente->valor_cota_centavos * $cotas,
            'token' => (string) Str::uuid(),
            'stripe_session_id' => 'cs_test_'.Str::random(6),
            'expira_em' => now()->addMinutes(35),
        ], $extra));
    }

    /** Monta um aviso do Stripe, assina como o Stripe assinaria e envia ao webhook. */
    private function webhook(string $tipo, Contribuicao $c, array $sessao = [], ?string $assinaturaFalsa = null)
    {
        $corpo = json_encode([
            'id' => 'evt_'.Str::random(8),
            'object' => 'event',
            'type' => $tipo,
            'data' => ['object' => array_merge([
                'id' => $c->stripe_session_id,
                'object' => 'checkout.session',
                'client_reference_id' => (string) $c->id,
                'payment_status' => 'paid',
                'payment_intent' => 'pi_test_999',
                'amount_total' => $c->valor_centavos,
                'currency' => 'brl',
            ], $sessao)],
        ]);

        $assinatura = $assinaturaFalsa ?? WebhookSignature::generateSignatureHeader($corpo, self::SEGREDO);

        return $this->call('POST', route('stripe.webhook'), [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => $assinatura,
            'CONTENT_TYPE' => 'application/json',
        ], $corpo);
    }

    // ---------- iniciar o pagamento ----------

    public function test_cria_contribuicao_pendente_e_leva_ao_checkout_do_stripe(): void
    {
        $presente = Presente::factory()->create(['valor' => 100.00, 'cotas_total' => 3]);
        $this->gatewayQueCria('cs_test_abc');

        $this->post(route('presentes.contribuir', $presente), $this->envio(2))
            ->assertRedirect('https://checkout.stripe.com/c/pay/cs_test_abc');

        $c = Contribuicao::firstOrFail();
        $this->assertSame('pendente', $c->status);
        $this->assertSame('cs_test_abc', $c->stripe_session_id);
        $this->assertSame(6666, $c->valor_centavos); // 2 cotas de R$ 33,33, em centavos inteiros
        $this->assertNotNull($c->token);
        $this->assertTrue($c->expira_em->isFuture());
    }

    public function test_o_valor_cobrado_vem_do_banco_e_nao_do_formulario(): void
    {
        $presente = Presente::factory()->create(['valor' => 300.00, 'cotas_total' => 1]);
        $this->gatewayQueCria();

        // Alguém tenta forjar campos de preço no formulário
        $this->post(route('presentes.contribuir', $presente), $this->envio(1, ['valor' => 1, 'valor_centavos' => 100, 'status' => 'paga']));

        $c = Contribuicao::firstOrFail();
        $this->assertSame(30000, $c->valor_centavos);
        $this->assertSame('pendente', $c->status);
    }

    public function test_valor_com_ponto_flutuante_vira_centavo_exato(): void
    {
        // 19.99 * 100 = 1998.9999999999998 em float; round() corrige.
        $this->assertSame(1999, Presente::factory()->make(['valor' => 19.99, 'cotas_total' => 1])->valor_cota_centavos);
    }

    public function test_falha_no_stripe_libera_as_cotas_e_nao_vaza_o_erro(): void
    {
        $presente = Presente::factory()->create(['cotas_total' => 1, 'valor' => 200]);

        $this->mock(StripeGateway::class, function (MockInterface $m) {
            $m->shouldReceive('criarCheckout')->once()->andThrow(new \Stripe\Exception\AuthenticationException('Invalid API Key sk_test_falsa'));
        });

        $this->post(route('presentes.contribuir', $presente), $this->envio(1))
            ->assertRedirect(route('presentes.index'))
            ->assertSessionHas('erro', 'Não foi possível iniciar o pagamento agora. Tente novamente em instantes.');

        $this->assertSame('falhou', Contribuicao::first()->status);
        $this->assertSame(1, $presente->fresh()->cotas_restantes); // liberou
    }

    public function test_abaixo_do_valor_minimo_nao_cria_pagamento(): void
    {
        $presente = Presente::factory()->create(['valor' => 3.00, 'cotas_total' => 1]);
        $this->mock(StripeGateway::class)->shouldNotReceive('criarCheckout');

        $this->post(route('presentes.contribuir', $presente), $this->envio(1))->assertSessionHas('erro');

        $this->assertDatabaseCount('contribuicoes', 0);
    }

    public function test_sem_chave_do_stripe_continua_no_modo_reservar(): void
    {
        config(['stripe.habilitado' => false]);
        $presente = Presente::factory()->create(['cotas_total' => 2]);
        $this->mock(StripeGateway::class)->shouldNotReceive('criarCheckout');

        $this->post(route('presentes.contribuir', $presente), $this->envio(1))->assertSessionHas('mensagem');

        $this->assertSame('reservada', Contribuicao::first()->status);
        $this->assertSame(1, $presente->fresh()->cotas_restantes);
    }

    // ---------- cotas seguradas ----------

    public function test_pendente_no_prazo_segura_as_cotas_e_vencida_libera(): void
    {
        $presente = Presente::factory()->create(['cotas_total' => 2]);
        $this->pendente($presente, 2);

        $this->mock(StripeGateway::class)->shouldNotReceive('criarCheckout');
        $this->post(route('presentes.contribuir', $presente), $this->envio(1, ['email' => 'outro@ex.com']))
            ->assertSessionHas('erro'); // outra pessoa já está pagando

        Contribuicao::query()->update(['expira_em' => now()->subMinute()]); // prazo vencido
        $this->assertSame(2, $presente->fresh()->cotas_restantes);
    }

    // ---------- webhook ----------

    public function test_webhook_com_assinatura_invalida_e_recusado(): void
    {
        $c = $this->pendente(Presente::factory()->create(['cotas_total' => 1]));

        $this->webhook('checkout.session.completed', $c, [], 't=1,v1=assinatura_errada')->assertStatus(400);

        $this->assertSame('pendente', $c->fresh()->status);
    }

    public function test_webhook_sem_segredo_configurado_recusa_tudo(): void
    {
        config(['stripe.webhook_secret' => null]);
        $c = $this->pendente(Presente::factory()->create(['cotas_total' => 1]));

        $this->webhook('checkout.session.completed', $c)->assertStatus(503);
        $this->assertSame('pendente', $c->fresh()->status);
    }

    public function test_webhook_nao_precisa_de_login_nem_csrf(): void
    {
        $c = $this->pendente(Presente::factory()->create(['cotas_total' => 1]));

        $this->webhook('checkout.session.completed', $c)->assertOk();
    }

    public function test_pagamento_confirmado_marca_como_paga(): void
    {
        $c = $this->pendente(Presente::factory()->create(['cotas_total' => 1, 'valor' => 150]));

        $this->webhook('checkout.session.completed', $c)->assertOk();

        $c->refresh();
        $this->assertSame('paga', $c->status);
        $this->assertSame('pi_test_999', $c->stripe_payment_intent_id);
        $this->assertNotNull($c->pago_em);
    }

    public function test_o_mesmo_aviso_repetido_nao_muda_nada(): void
    {
        $c = $this->pendente(Presente::factory()->create(['cotas_total' => 1]));

        $this->webhook('checkout.session.completed', $c)->assertOk();
        $primeiraHora = $c->fresh()->pago_em;

        $this->travel(5)->minutes();
        $this->webhook('checkout.session.completed', $c)->assertOk();

        $this->assertSame('paga', $c->fresh()->status);
        $this->assertEquals($primeiraHora, $c->fresh()->pago_em); // não regravou
    }

    public function test_pix_fica_pendente_ate_o_aviso_de_pagamento_assincrono(): void
    {
        $c = $this->pendente(Presente::factory()->create(['cotas_total' => 1]));

        $this->webhook('checkout.session.completed', $c, ['payment_status' => 'unpaid'])->assertOk();
        $this->assertSame('pendente', $c->fresh()->status);

        $this->webhook('checkout.session.async_payment_succeeded', $c)->assertOk();
        $this->assertSame('paga', $c->fresh()->status);
    }

    public function test_pagamento_assincrono_que_falha_libera_as_cotas(): void
    {
        $presente = Presente::factory()->create(['cotas_total' => 1]);
        $c = $this->pendente($presente);

        $this->webhook('checkout.session.async_payment_failed', $c, ['payment_status' => 'unpaid'])->assertOk();

        $this->assertSame('falhou', $c->fresh()->status);
        $this->assertSame(1, $presente->fresh()->cotas_restantes);
    }

    public function test_sessao_expirada_libera_as_cotas(): void
    {
        $presente = Presente::factory()->create(['cotas_total' => 1]);
        $c = $this->pendente($presente);

        $this->webhook('checkout.session.expired', $c, ['payment_status' => 'unpaid'])->assertOk();

        $this->assertSame('expirada', $c->fresh()->status);
        $this->assertSame(1, $presente->fresh()->cotas_restantes);
    }

    public function test_expirar_nunca_desfaz_um_pagamento_ja_confirmado(): void
    {
        $c = $this->pendente(Presente::factory()->create(['cotas_total' => 1]));
        $this->webhook('checkout.session.completed', $c)->assertOk();

        $this->webhook('checkout.session.expired', $c, ['payment_status' => 'unpaid'])->assertOk();

        $this->assertSame('paga', $c->fresh()->status);
    }

    public function test_valor_pago_diferente_do_esperado_nao_confirma(): void
    {
        Log::spy();
        $c = $this->pendente(Presente::factory()->create(['cotas_total' => 1, 'valor' => 200]));

        $this->webhook('checkout.session.completed', $c, ['amount_total' => 100])->assertOk();

        $this->assertSame('pendente', $c->fresh()->status);
        Log::shouldHaveReceived('critical')->once();
    }

    public function test_pagamento_que_chega_depois_de_expirar_ainda_vale(): void
    {
        $presente = Presente::factory()->create(['cotas_total' => 1]);
        $c = $this->pendente($presente);
        $c->liberar('expirada');

        $this->webhook('checkout.session.async_payment_succeeded', $c)->assertOk();

        $this->assertSame('paga', $c->fresh()->status); // dinheiro recebido é dinheiro recebido
    }

    public function test_evento_de_outra_coisa_e_ignorado_com_200(): void
    {
        $c = $this->pendente(Presente::factory()->create(['cotas_total' => 1]));

        $this->webhook('checkout.session.completed', $c, ['client_reference_id' => '999999'])->assertOk();

        $this->assertSame('pendente', $c->fresh()->status);
    }

    // ---------- páginas de retorno ----------

    public function test_pagina_de_sucesso_nao_confia_na_url_e_pergunta_ao_stripe(): void
    {
        $c = $this->pendente(Presente::factory()->create(['cotas_total' => 1]));

        $this->mock(StripeGateway::class, function (MockInterface $m) use ($c) {
            $m->shouldReceive('buscarCheckout')->once()->andReturn(Session::constructFrom([
                'id' => $c->stripe_session_id, 'payment_status' => 'unpaid', 'amount_total' => $c->valor_centavos, 'currency' => 'brl',
            ]));
        });

        $this->get(route('pagamento.sucesso', $c->token))
            ->assertOk()->assertSee('Estamos confirmando');

        $this->assertSame('pendente', $c->fresh()->status); // visitar a URL não paga nada
    }

    public function test_pagina_de_sucesso_confirma_quando_o_stripe_diz_que_pagou(): void
    {
        $c = $this->pendente(Presente::factory()->create(['cotas_total' => 1]));

        $this->mock(StripeGateway::class, function (MockInterface $m) use ($c) {
            $m->shouldReceive('buscarCheckout')->once()->andReturn(Session::constructFrom([
                'id' => $c->stripe_session_id, 'payment_status' => 'paid', 'payment_intent' => 'pi_77',
                'amount_total' => $c->valor_centavos, 'currency' => 'brl',
            ]));
        });

        $this->get(route('pagamento.sucesso', $c->token))->assertOk()->assertSee('Pagamento confirmado');

        $this->assertSame('paga', $c->fresh()->status);
    }

    public function test_token_inexistente_da_404(): void
    {
        $this->get(route('pagamento.sucesso', (string) Str::uuid()))->assertNotFound();
        $this->get(route('pagamento.cancelado', (string) Str::uuid()))->assertNotFound();
    }

    public function test_cancelar_fecha_a_sessao_e_devolve_as_cotas_na_hora(): void
    {
        $presente = Presente::factory()->create(['cotas_total' => 1]);
        $c = $this->pendente($presente);

        $this->mock(StripeGateway::class, function (MockInterface $m) use ($c) {
            $m->shouldReceive('expirarCheckout')->once()->with($c->stripe_session_id);
        });

        $this->get(route('pagamento.cancelado', $c->token))
            ->assertRedirect(route('presentes.index'))->assertSessionHas('erro');

        $this->assertSame('cancelada', $c->fresh()->status);
        $this->assertSame(1, $presente->fresh()->cotas_restantes);
    }

    // ---------- painel dos noivos ----------

    public function test_painel_so_soma_como_recebido_o_que_esta_pago(): void
    {
        $this->noivoLogado();
        $presente = Presente::factory()->create(['valor' => 100, 'cotas_total' => 1]);
        $outro = Presente::factory()->create(['valor' => 50, 'cotas_total' => 1]);
        $this->pendente($presente);                                 // ainda não pagou
        $paga = $this->pendente($outro);
        $paga->marcarComoPaga('pi_1');

        $this->get(route('painel'))
            ->assertOk()
            ->assertSee('R$ 50,00')            // só a paga
            ->assertDontSee('R$ 150,00');

        $this->get(route('contribuicoes.index'))->assertOk()->assertSee('Aguardando pagamento')->assertSee('Paga');
    }
}
