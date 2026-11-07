<?php

namespace Tests\Feature;

use App\Models\Convidado;
use App\Models\Pedido;
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

    /** Põe o presente na lista e finaliza o pedido pela forma "stripe". */
    private function pagarOnline(Presente $presente, int $cotas = 1, array $extra = [])
    {
        $this->post(route('carrinho.adicionar', $presente), ['quantidade_cotas' => $cotas]);

        return $this->post(route('pedido.store'), array_merge([
            'forma' => 'stripe', 'nome' => 'Ana', 'email' => 'ana@example.com', 'mensagem' => 'Parabéns!',
        ], $extra));
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

    /** Um pedido "stripe" pendente, como o sistema o deixa depois de mandar a pessoa ao Stripe. */
    private function pendente(Presente $presente, int $cotas = 1, array $extra = []): Pedido
    {
        $total = $presente->valor_cota_centavos * $cotas;
        $convidado = Convidado::factory()->create();

        $pedido = Pedido::create(array_merge([
            'convidado_id' => $convidado->id,
            'forma' => 'stripe',
            'status' => 'pendente',
            'total_centavos' => $total,
            'token' => (string) Str::uuid(),
            'codigo' => Pedido::gerarCodigo(),
            'stripe_session_id' => 'cs_test_'.Str::random(6),
            'expira_em' => now()->addMinutes(35),
        ], $extra));

        $pedido->itens()->create([
            'presente_id' => $presente->id, 'convidado_id' => $convidado->id, 'quantidade_cotas' => $cotas,
            'valor_centavos' => $total, 'status' => $pedido->status, 'expira_em' => $pedido->expira_em,
        ]);

        return $pedido;
    }

    /** Monta um aviso do Stripe, assina como o Stripe assinaria e envia ao webhook. */
    private function webhook(string $tipo, Pedido $p, array $sessao = [], ?string $assinaturaFalsa = null)
    {
        $corpo = json_encode([
            'id' => 'evt_'.Str::random(8),
            'object' => 'event',
            'type' => $tipo,
            'data' => ['object' => array_merge([
                'id' => $p->stripe_session_id,
                'object' => 'checkout.session',
                'client_reference_id' => (string) $p->id,
                'payment_status' => 'paid',
                'payment_intent' => 'pi_test_999',
                'amount_total' => $p->total_centavos,
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

    public function test_cria_pedido_pendente_e_leva_ao_checkout_do_stripe(): void
    {
        $presente = Presente::factory()->create(['valor' => 100.00, 'cotas_total' => 3]);
        $this->gatewayQueCria('cs_test_abc');

        $this->pagarOnline($presente, 2)->assertRedirect('https://checkout.stripe.com/c/pay/cs_test_abc');

        $p = Pedido::firstOrFail();
        $this->assertSame('pendente', $p->status);
        $this->assertSame('stripe', $p->forma);
        $this->assertSame('cs_test_abc', $p->stripe_session_id);
        $this->assertSame(6666, $p->total_centavos); // 2 cotas de R$ 33,33, em centavos inteiros
        $this->assertNotNull($p->token);
        $this->assertTrue($p->expira_em->isFuture());
        $this->assertSame('pendente', $p->itens->first()->status);
    }

    public function test_o_valor_cobrado_vem_do_banco_e_nao_do_formulario(): void
    {
        $presente = Presente::factory()->create(['valor' => 300.00, 'cotas_total' => 1]);
        $this->gatewayQueCria();

        // Alguém tenta forjar campos de preço e de situação no formulário
        $this->pagarOnline($presente, 1, ['valor' => 1, 'total_centavos' => 100, 'status' => 'paga']);

        $p = Pedido::firstOrFail();
        $this->assertSame(30000, $p->total_centavos);
        $this->assertSame('pendente', $p->status);
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

        $this->pagarOnline($presente)
            ->assertRedirect(route('carrinho.mostrar'))
            ->assertSessionHas('erro', 'Não foi possível iniciar o pagamento agora. Tente de novo em instantes ou escolha outra forma de enviar.');

        $this->assertSame('falhou', Pedido::first()->status);
        $this->assertSame(1, $presente->fresh()->cotas_restantes); // liberou
        $this->get(route('carrinho.mostrar'))->assertSee($presente->nome); // a lista continua lá para tentar outra forma
    }

    public function test_abaixo_do_valor_minimo_nao_cria_pagamento(): void
    {
        $presente = Presente::factory()->create(['valor' => 3.00, 'cotas_total' => 1]);
        $this->mock(StripeGateway::class)->shouldNotReceive('criarCheckout');

        $this->pagarOnline($presente)->assertSessionHas('erro');

        $this->assertDatabaseCount('pedidos', 0);
    }

    public function test_sem_chave_do_stripe_a_forma_online_e_recusada(): void
    {
        config(['stripe.habilitado' => false]);
        $presente = Presente::factory()->create(['cotas_total' => 2]);
        $this->mock(StripeGateway::class)->shouldNotReceive('criarCheckout');

        $this->pagarOnline($presente)->assertSessionHas('erro', 'O pagamento online ainda não está disponível.');

        $this->assertDatabaseCount('pedidos', 0);
    }

    public function test_um_checkout_com_varios_presentes_e_um_unico_pedido(): void
    {
        $a = Presente::factory()->create(['valor' => 100, 'cotas_total' => 1]);
        $b = Presente::factory()->create(['valor' => 90, 'cotas_total' => 3]);

        $this->mock(StripeGateway::class, function (MockInterface $m) {
            $m->shouldReceive('criarCheckout')->once()->withArgs(fn (Pedido $p) => $p->itens()->count() === 2)
                ->andReturn($this->sessaoFalsa());
        });

        $this->post(route('carrinho.adicionar', $a), ['quantidade_cotas' => 1]);
        $this->post(route('carrinho.adicionar', $b), ['quantidade_cotas' => 2]);
        $this->post(route('pedido.store'), ['forma' => 'stripe', 'nome' => 'Ana', 'email' => 'ana@example.com'])
            ->assertRedirect('https://checkout.stripe.com/c/pay/cs_test_123');

        $this->assertDatabaseCount('pedidos', 1);
        $this->assertSame(16000, Pedido::first()->total_centavos); // 10000 + 2 x 3000
    }

    // ---------- cotas seguradas ----------

    public function test_pendente_no_prazo_segura_as_cotas_e_vencida_libera(): void
    {
        $presente = Presente::factory()->create(['cotas_total' => 2]);
        $this->pendente($presente, 2);

        $this->post(route('carrinho.adicionar', $presente), ['quantidade_cotas' => 1])
            ->assertSessionHas('erro'); // outra pessoa já está pagando

        Pedido::query()->update(['expira_em' => now()->subMinute()]);
        \App\Models\Contribuicao::query()->update(['expira_em' => now()->subMinute()]); // prazo vencido
        $this->assertSame(2, $presente->fresh()->cotas_restantes);
    }

    // ---------- webhook ----------

    public function test_webhook_com_assinatura_invalida_e_recusado(): void
    {
        $p = $this->pendente(Presente::factory()->create(['cotas_total' => 1]));

        $this->webhook('checkout.session.completed', $p, [], 't=1,v1=assinatura_errada')->assertStatus(400);

        $this->assertSame('pendente', $p->fresh()->status);
    }

    public function test_webhook_sem_segredo_configurado_recusa_tudo(): void
    {
        config(['stripe.webhook_secret' => null]);
        $p = $this->pendente(Presente::factory()->create(['cotas_total' => 1]));

        $this->webhook('checkout.session.completed', $p)->assertStatus(503);
        $this->assertSame('pendente', $p->fresh()->status);
    }

    public function test_webhook_nao_precisa_de_login_nem_csrf(): void
    {
        $p = $this->pendente(Presente::factory()->create(['cotas_total' => 1]));

        $this->webhook('checkout.session.completed', $p)->assertOk();
    }

    public function test_pagamento_confirmado_marca_o_pedido_e_os_itens_como_pagos(): void
    {
        $p = $this->pendente(Presente::factory()->create(['cotas_total' => 1, 'valor' => 150]));

        $this->webhook('checkout.session.completed', $p)->assertOk();

        $p->refresh();
        $this->assertSame('paga', $p->status);
        $this->assertSame('paga', $p->itens->first()->status);
        $this->assertSame('pi_test_999', $p->stripe_payment_intent_id);
        $this->assertNotNull($p->pago_em);
    }

    public function test_o_mesmo_aviso_repetido_nao_muda_nada(): void
    {
        $p = $this->pendente(Presente::factory()->create(['cotas_total' => 1]));

        $this->webhook('checkout.session.completed', $p)->assertOk();
        $primeiraHora = $p->fresh()->pago_em;

        $this->travel(5)->minutes();
        $this->webhook('checkout.session.completed', $p)->assertOk();

        $this->assertSame('paga', $p->fresh()->status);
        $this->assertEquals($primeiraHora, $p->fresh()->pago_em); // não regravou
    }

    public function test_pix_fica_pendente_ate_o_aviso_de_pagamento_assincrono(): void
    {
        $p = $this->pendente(Presente::factory()->create(['cotas_total' => 1]));

        $this->webhook('checkout.session.completed', $p, ['payment_status' => 'unpaid'])->assertOk();
        $this->assertSame('pendente', $p->fresh()->status);

        $this->webhook('checkout.session.async_payment_succeeded', $p)->assertOk();
        $this->assertSame('paga', $p->fresh()->status);
    }

    public function test_pagamento_assincrono_que_falha_libera_as_cotas(): void
    {
        $presente = Presente::factory()->create(['cotas_total' => 1]);
        $p = $this->pendente($presente);

        $this->webhook('checkout.session.async_payment_failed', $p, ['payment_status' => 'unpaid'])->assertOk();

        $this->assertSame('falhou', $p->fresh()->status);
        $this->assertSame(1, $presente->fresh()->cotas_restantes);
    }

    public function test_sessao_expirada_libera_as_cotas(): void
    {
        $presente = Presente::factory()->create(['cotas_total' => 1]);
        $p = $this->pendente($presente);

        $this->webhook('checkout.session.expired', $p, ['payment_status' => 'unpaid'])->assertOk();

        $this->assertSame('expirada', $p->fresh()->status);
        $this->assertSame(1, $presente->fresh()->cotas_restantes);
    }

    public function test_expirar_nunca_desfaz_um_pagamento_ja_confirmado(): void
    {
        $p = $this->pendente(Presente::factory()->create(['cotas_total' => 1]));
        $this->webhook('checkout.session.completed', $p)->assertOk();

        $this->webhook('checkout.session.expired', $p, ['payment_status' => 'unpaid'])->assertOk();

        $this->assertSame('paga', $p->fresh()->status);
        $this->assertSame('paga', $p->fresh()->itens->first()->status);
    }

    public function test_valor_pago_diferente_do_esperado_nao_confirma(): void
    {
        Log::spy();
        $p = $this->pendente(Presente::factory()->create(['cotas_total' => 1, 'valor' => 200]));

        $this->webhook('checkout.session.completed', $p, ['amount_total' => 100])->assertOk();

        $this->assertSame('pendente', $p->fresh()->status);
        Log::shouldHaveReceived('critical')->once();
    }

    public function test_pagamento_que_chega_depois_de_expirar_ainda_vale(): void
    {
        $p = $this->pendente(Presente::factory()->create(['cotas_total' => 1]));
        $p->liberar('expirada');

        $this->webhook('checkout.session.async_payment_succeeded', $p)->assertOk();

        $this->assertSame('paga', $p->fresh()->status); // dinheiro recebido é dinheiro recebido
    }

    public function test_webhook_nunca_confirma_pedido_que_nao_e_do_stripe(): void
    {
        $p = $this->pendente(Presente::factory()->create(['cotas_total' => 1]), 1, ['forma' => 'pix', 'stripe_session_id' => null]);

        $this->webhook('checkout.session.completed', $p, ['id' => 'cs_x'])->assertOk();

        $this->assertSame('pendente', $p->fresh()->status);
    }

    public function test_evento_de_outra_coisa_e_ignorado_com_200(): void
    {
        $p = $this->pendente(Presente::factory()->create(['cotas_total' => 1]));

        $this->webhook('checkout.session.completed', $p, ['client_reference_id' => '999999'])->assertOk();

        $this->assertSame('pendente', $p->fresh()->status);
    }

    // ---------- páginas de retorno ----------

    public function test_pagina_de_sucesso_nao_confia_na_url_e_pergunta_ao_stripe(): void
    {
        $p = $this->pendente(Presente::factory()->create(['cotas_total' => 1]));

        $this->mock(StripeGateway::class, function (MockInterface $m) use ($p) {
            $m->shouldReceive('buscarCheckout')->once()->andReturn(Session::constructFrom([
                'id' => $p->stripe_session_id, 'payment_status' => 'unpaid', 'amount_total' => $p->total_centavos, 'currency' => 'brl',
            ]));
        });

        $this->get(route('pagamento.sucesso', $p->token))
            ->assertOk()->assertSee('Estamos confirmando');

        $this->assertSame('pendente', $p->fresh()->status); // visitar a URL não paga nada
    }

    public function test_pagina_de_sucesso_confirma_quando_o_stripe_diz_que_pagou(): void
    {
        $p = $this->pendente(Presente::factory()->create(['cotas_total' => 1]));

        $this->mock(StripeGateway::class, function (MockInterface $m) use ($p) {
            $m->shouldReceive('buscarCheckout')->once()->andReturn(Session::constructFrom([
                'id' => $p->stripe_session_id, 'payment_status' => 'paid', 'payment_intent' => 'pi_77',
                'amount_total' => $p->total_centavos, 'currency' => 'brl',
            ]));
        });

        $this->get(route('pagamento.sucesso', $p->token))->assertOk()->assertSee('Pagamento confirmado');

        $this->assertSame('paga', $p->fresh()->status);
    }

    public function test_token_inexistente_da_404(): void
    {
        $this->get(route('pagamento.sucesso', (string) Str::uuid()))->assertNotFound();
        $this->get(route('pagamento.cancelado', (string) Str::uuid()))->assertNotFound();
        $this->get(route('pedido.mostrar', (string) Str::uuid()))->assertNotFound();
    }

    public function test_cancelar_fecha_a_sessao_e_devolve_as_cotas_na_hora(): void
    {
        $presente = Presente::factory()->create(['cotas_total' => 1]);
        $p = $this->pendente($presente);

        $this->mock(StripeGateway::class, function (MockInterface $m) use ($p) {
            $m->shouldReceive('expirarCheckout')->once()->with($p->stripe_session_id);
        });

        $this->get(route('pagamento.cancelado', $p->token))
            ->assertRedirect(route('presentes.index'))->assertSessionHas('erro');

        $this->assertSame('cancelada', $p->fresh()->status);
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
        $paga->confirmar('pi_1');

        $this->get(route('painel'))
            ->assertOk()
            ->assertSee('R$ 50,00')            // só a paga
            ->assertDontSee('R$ 150,00');

        $this->get(route('pedidos.index'))->assertOk()->assertSee('Aguardando pagamento')->assertSee('Paga');
    }
}
