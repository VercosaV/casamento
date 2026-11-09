<?php

namespace Tests\Feature;

use App\Models\Contribuicao;
use App\Models\Convidado;
use App\Models\Pedido;
use App\Models\Presente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

// "Minha lista" e as 3 formas de enviar: comprar, pagar online (Stripe, coberto em StripePagamentoTest) e Pix direto.
class PedidoFluxoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'stripe.habilitado' => false,
            'casamento.pix.chave' => 'noivos@pix.example',
            'casamento.pix.beneficiario' => 'Victor e Kamilly',
            'casamento.pix.horas_reserva' => 48,
            'casamento.entrega.endereco' => "Rua das Flores, 10\nPresidente Prudente",
        ]);
    }

    private function naLista(Presente $presente, int $cotas = 1)
    {
        return $this->post(route('carrinho.adicionar', $presente), ['quantidade_cotas' => $cotas]);
    }

    private function finalizar(string $forma, array $extra = [])
    {
        return $this->post(route('pedido.store'), array_merge([
            'forma' => $forma, 'nome' => 'Ana', 'email' => 'ana@example.com', 'mensagem' => 'Parabéns!',
        ], $extra));
    }

    // ---------- Minha lista ----------

    public function test_adicionar_poe_o_presente_na_lista_e_o_menu_mostra_a_contagem(): void
    {
        $p = Presente::factory()->create(['nome' => 'Jogo de panelas', 'cotas_total' => 1]);

        $this->naLista($p)->assertRedirect(route('presentes.index'))->assertSessionHas('mensagem');

        $this->get(route('carrinho.mostrar'))->assertOk()->assertSee('Jogo de panelas')->assertSee('Minha lista (1)');
    }

    public function test_nao_aceita_mais_cotas_do_que_restam(): void
    {
        $p = Presente::factory()->create(['cotas_total' => 4]);

        // a primeira pessoa finaliza 3 das 4 cotas (só o pedido finalizado segura cotas)
        $this->naLista($p, 3);
        $this->finalizar('pix');
        $this->flushSession();

        // a seguinte só consegue 1
        $this->naLista($p, 2)->assertSessionHasErrors('quantidade_cotas');
        $this->naLista($p, 1)->assertSessionHasNoErrors();
    }

    public function test_presente_esgotado_nao_entra_na_lista(): void
    {
        $p = Presente::factory()->create(['cotas_total' => 1]);
        $this->naLista($p);
        $this->finalizar('comprar');
        $this->flushSession();

        $this->naLista($p)->assertSessionHas('erro');
        $this->get(route('carrinho.mostrar'))->assertDontSee('Minha lista (');
    }

    public function test_remover_tira_da_lista(): void
    {
        $p = Presente::factory()->create(['nome' => 'Liquidificador', 'cotas_total' => 1]);
        $this->naLista($p);

        $this->delete(route('carrinho.remover', $p))->assertRedirect(route('carrinho.mostrar'));

        $this->get(route('carrinho.mostrar'))->assertSee('Sua lista está vazia');
    }

    public function test_lista_mostra_so_as_formas_que_valem_para_ela(): void
    {
        $inteiro = Presente::factory()->create(['cotas_total' => 1, 'valor' => 200]);
        $cotas = Presente::factory()->create(['cotas_total' => 5, 'valor' => 500]);

        // presente inteiro: comprar vale; Stripe desligado; Pix ligado
        $this->naLista($inteiro);
        $html = $this->get(route('carrinho.mostrar'))->assertOk()->getContent();
        $this->assertStringNotContainsString('value="comprar" disabled', preg_replace('/\s+/', ' ', $html));
        $this->assertMatchesRegularExpression('/value="stripe"[^>]*disabled/s', $html);
        $this->assertDoesNotMatchRegularExpression('/value="pix"[^>]*disabled/s', $html);

        // acrescenta um presente em cotas: "comprar" deixa de valer
        $this->naLista($cotas, 2);
        $html = $this->get(route('carrinho.mostrar'))->getContent();
        $this->assertMatchesRegularExpression('/value="comprar"[^>]*disabled/s', $html);
        $this->assertStringContainsString('Disponível só para presentes inteiros', $html);
    }

    public function test_sem_chave_pix_a_forma_pix_some(): void
    {
        config(['casamento.pix.chave' => null]);
        $p = Presente::factory()->create(['cotas_total' => 1]);
        $this->naLista($p);

        $this->get(route('carrinho.mostrar'))->assertSee('Indisponível no momento');
        $this->finalizar('pix')->assertSessionHas('erro', 'A chave Pix dos noivos ainda não foi cadastrada.');
        $this->assertDatabaseCount('pedidos', 0);
    }

    // ---------- 1) Comprar o presente ----------

    public function test_comprar_reserva_o_presente_e_mostra_onde_entregar(): void
    {
        $p = Presente::factory()->create(['nome' => 'Jogo de panelas', 'cotas_total' => 1, 'link' => 'https://loja.example/panelas']);
        $this->naLista($p);

        $r = $this->finalizar('comprar');

        $pedido = Pedido::firstOrFail();
        $r->assertRedirect(route('pedido.mostrar', $pedido->token));
        $this->assertSame('comprar', $pedido->forma);
        $this->assertSame('reservada', $pedido->status);
        $this->assertSame('reservada', $pedido->itens->first()->status);
        $this->assertSame(0, $p->fresh()->cotas_restantes); // ninguém mais compra o mesmo

        $this->get(route('pedido.mostrar', $pedido->token))
            ->assertOk()->assertSee('Reservado para você')->assertSee('Rua das Flores, 10')->assertSee('loja.example/panelas');

        $this->get(route('carrinho.mostrar'))->assertSee('Sua lista está vazia'); // a lista foi esvaziada
    }

    public function test_comprar_so_vale_para_presente_inteiro(): void
    {
        $cotas = Presente::factory()->create(['cotas_total' => 5]);
        $this->naLista($cotas, 1);

        $this->finalizar('comprar')->assertSessionHas('erro', 'Comprar o presente só vale para presentes inteiros, sem cotas.');

        $this->assertDatabaseCount('pedidos', 0);
    }

    public function test_noivos_marcam_como_recebido_e_o_presente_continua_ocupado(): void
    {
        $p = Presente::factory()->create(['cotas_total' => 1]);
        $this->naLista($p);
        $this->finalizar('comprar');
        $pedido = Pedido::firstOrFail();

        $this->noivoLogado();
        $this->post(route('pedidos.entregar', $pedido))->assertSessionHas('mensagem');

        $this->assertSame('entregue', $pedido->fresh()->status);
        $this->assertSame('entregue', $pedido->fresh()->itens->first()->status);
        $this->assertSame(0, $p->fresh()->cotas_restantes);
    }

    public function test_noivos_cancelam_a_reserva_e_as_cotas_voltam(): void
    {
        $p = Presente::factory()->create(['cotas_total' => 1]);
        $this->naLista($p);
        $this->finalizar('comprar');
        $pedido = Pedido::firstOrFail();

        $this->noivoLogado();
        $this->post(route('pedidos.cancelar', $pedido))->assertSessionHas('mensagem');

        $this->assertSame('cancelada', $pedido->fresh()->status);
        $this->assertSame(1, $p->fresh()->cotas_restantes);
    }

    // ---------- 3) Pix direto ----------

    public function test_pix_cria_pedido_pendente_com_prazo_e_mostra_a_chave_o_valor_e_o_codigo(): void
    {
        $p = Presente::factory()->create(['cotas_total' => 4, 'valor' => 400]);
        $this->naLista($p, 3);

        $r = $this->finalizar('pix');

        $pedido = Pedido::firstOrFail();
        $r->assertRedirect(route('pedido.mostrar', $pedido->token));
        $this->assertSame('pix', $pedido->forma);
        $this->assertSame('pendente', $pedido->status);
        $this->assertSame(30000, $pedido->total_centavos);
        $this->assertEqualsWithDelta(48 * 3600, now()->diffInSeconds($pedido->expira_em, false), 5);

        $this->get(route('pedido.mostrar', $pedido->token))
            ->assertOk()
            ->assertSee('noivos@pix.example')
            ->assertSee('R$ 300,00')
            ->assertSee('Victor e Kamilly')
            ->assertSee($pedido->codigo);
    }

    public function test_pix_nao_confirma_sozinho_e_a_pagina_nao_vira_paga_ao_visitar(): void
    {
        $p = Presente::factory()->create(['cotas_total' => 1]);
        $this->naLista($p);
        $this->finalizar('pix');
        $pedido = Pedido::firstOrFail();

        $this->get(route('pedido.mostrar', $pedido->token))->assertSee('Envie o Pix');
        $this->get(route('pagamento.sucesso', $pedido->token))->assertSee('Envie o Pix'); // a URL do Stripe também não paga nada

        $this->assertSame('pendente', $pedido->fresh()->status);
    }

    public function test_noivos_confirmam_o_pix_e_o_valor_entra_no_painel(): void
    {
        $p = Presente::factory()->create(['cotas_total' => 1, 'valor' => 250]);
        $this->naLista($p);
        $this->finalizar('pix');
        $pedido = Pedido::firstOrFail();

        $this->noivoLogado();
        $this->post(route('pedidos.confirmar', $pedido))->assertSessionHas('mensagem');

        $pedido->refresh();
        $this->assertSame('paga', $pedido->status);
        $this->assertSame('paga', $pedido->itens->first()->status);
        $this->assertNotNull($pedido->pago_em);

        $this->get(route('painel'))->assertSee('R$ 250,00');
    }

    public function test_confirmar_duas_vezes_nao_duplica(): void
    {
        $p = Presente::factory()->create(['cotas_total' => 1]);
        $this->naLista($p);
        $this->finalizar('pix');
        $pedido = Pedido::firstOrFail();

        $this->noivoLogado();
        $this->post(route('pedidos.confirmar', $pedido))->assertSessionHas('mensagem');
        $hora = $pedido->fresh()->pago_em;
        $this->travel(5)->minutes();

        $this->post(route('pedidos.confirmar', $pedido))->assertSessionHas('erro');
        $this->assertEquals($hora, $pedido->fresh()->pago_em);
    }

    public function test_pix_vencido_libera_as_cotas_mas_os_noivos_ainda_podem_confirmar(): void
    {
        $p = Presente::factory()->create(['cotas_total' => 1]);
        $this->naLista($p);
        $this->finalizar('pix');
        $pedido = Pedido::firstOrFail();

        $this->travel(49)->hours(); // passou do prazo
        $this->assertSame(1, $p->fresh()->cotas_restantes);
        $this->assertSame('expirada', $pedido->fresh()->status_efetivo);

        $this->noivoLogado();
        $this->post(route('pedidos.confirmar', $pedido))->assertSessionHas('mensagem'); // o Pix chegou tarde, mas chegou

        $this->assertSame('paga', $pedido->fresh()->status);
        $this->assertSame(0, $p->fresh()->cotas_restantes);
    }

    public function test_pix_tardio_avisa_se_outra_pessoa_ja_pegou_as_cotas(): void
    {
        $p = Presente::factory()->create(['cotas_total' => 1, 'nome' => 'Cafeteira']);
        $this->naLista($p);
        $this->finalizar('pix');
        $primeiro = Pedido::firstOrFail();

        $this->travel(49)->hours();
        $this->flushSession();
        $this->naLista($p);                   // outra pessoa pega a cafeteira
        $this->finalizar('comprar', ['email' => 'bia@example.com', 'nome' => 'Bia']);

        $this->noivoLogado();
        $this->post(route('pedidos.confirmar', $primeiro))
            ->assertSessionHas('erro', fn ($msg) => str_contains($msg, 'Cafeteira') && str_contains($msg, 'mais cotas do que existem'));

        $this->assertSame('paga', $primeiro->fresh()->status); // o dinheiro entrou: a confirmação vale
    }

    public function test_noivos_cancelam_um_pix_que_nao_veio(): void
    {
        $p = Presente::factory()->create(['cotas_total' => 2]);
        $this->naLista($p, 2);
        $this->finalizar('pix');
        $pedido = Pedido::firstOrFail();

        $this->noivoLogado();
        $this->post(route('pedidos.cancelar', $pedido))->assertSessionHas('mensagem');

        $this->assertSame('cancelada', $pedido->fresh()->status);
        $this->assertSame(2, $p->fresh()->cotas_restantes);
    }

    // ---------- várias seleções num pedido só ----------

    public function test_varios_presentes_viram_um_unico_pedido_com_o_total_certo(): void
    {
        $a = Presente::factory()->create(['valor' => 100, 'cotas_total' => 1]);
        $b = Presente::factory()->create(['valor' => 90, 'cotas_total' => 3]);
        $this->naLista($a);
        $this->naLista($b, 2);

        $this->finalizar('pix');

        $this->assertDatabaseCount('pedidos', 1);
        $this->assertDatabaseCount('contribuicoes', 2);
        $this->assertSame(16000, Pedido::first()->total_centavos);
        $this->assertSame(0, $a->fresh()->cotas_restantes);
        $this->assertSame(1, $b->fresh()->cotas_restantes);
    }

    public function test_se_alguem_pegou_as_cotas_enquanto_a_pessoa_decidia_nada_e_criado(): void
    {
        $a = Presente::factory()->create(['cotas_total' => 1, 'nome' => 'Panela']);
        $b = Presente::factory()->create(['cotas_total' => 1, 'nome' => 'Cafeteira']);

        // a primeira pessoa monta a lista com os dois e fica pensando
        $this->naLista($a);
        $this->naLista($b);
        $listaDela = session('carrinho');
        $this->flushSession();

        // a segunda pessoa leva a cafeteira
        $this->naLista($b);
        $this->finalizar('comprar', ['email' => 'bia@example.com', 'nome' => 'Bia'])->assertRedirect();

        // a primeira volta e tenta finalizar
        $this->flushSession();
        session(['carrinho' => $listaDela]);
        $this->finalizar('pix')->assertRedirect(route('carrinho.mostrar'))->assertSessionHas('erro');

        // tudo ou nada: nem a panela (que estava livre) foi reservada
        $this->assertDatabaseCount('pedidos', 1);
        $this->assertSame(1, $a->fresh()->cotas_restantes);
    }

    public function test_preco_e_situacao_vindos_do_formulario_sao_ignorados(): void
    {
        $p = Presente::factory()->create(['valor' => 300, 'cotas_total' => 1]);
        $this->naLista($p);

        $this->finalizar('pix', ['total_centavos' => 100, 'status' => 'paga']);

        $pedido = Pedido::firstOrFail();
        $this->assertSame(30000, $pedido->total_centavos);
        $this->assertSame('pendente', $pedido->status);
    }

    public function test_forma_invalida_e_recusada(): void
    {
        $p = Presente::factory()->create(['cotas_total' => 1]);
        $this->naLista($p);

        $this->finalizar('cheque')->assertSessionHasErrors('forma');
        $this->assertDatabaseCount('pedidos', 0);
    }

    public function test_lista_vazia_nao_finaliza(): void
    {
        $this->finalizar('pix')->assertRedirect(route('presentes.index'))->assertSessionHas('erro');
    }

    // ---------- quem pode mexer ----------

    public function test_so_noivos_acessam_e_agem_nos_pedidos(): void
    {
        $p = Presente::factory()->create(['cotas_total' => 1]);
        $this->naLista($p);
        $this->finalizar('pix');
        $pedido = Pedido::firstOrFail();
        $this->flushSession();

        $this->get(route('pedidos.index'))->assertRedirect('/login');
        $this->post(route('pedidos.confirmar', $pedido))->assertRedirect('/login');

        $this->actingAs(User::factory()->create(), 'api'); // existe, mas não é noivo
        $this->post(route('pedidos.confirmar', $pedido))->assertForbidden();
        $this->assertSame('pendente', $pedido->fresh()->status);
    }

    public function test_noivos_nao_confirmam_manualmente_pedido_que_nao_e_pix(): void
    {
        $p = Presente::factory()->create(['cotas_total' => 1]);
        $this->naLista($p);
        $this->finalizar('comprar');
        $pedido = Pedido::firstOrFail();

        $this->noivoLogado();
        $this->post(route('pedidos.confirmar', $pedido))->assertSessionHas('erro');
        $this->assertSame('reservada', $pedido->fresh()->status);
    }

    public function test_tela_de_pedidos_lista_forma_e_situacao(): void
    {
        $this->noivoLogado();
        $p = Presente::factory()->create(['cotas_total' => 1, 'nome' => 'Aspirador']);
        $this->naLista($p);
        $this->finalizar('pix');

        $this->get(route('pedidos.index'))
            ->assertOk()->assertSee('Aspirador')->assertSee('Pix direto')->assertSee('Aguardando o Pix')->assertSee('Pix chegou');
    }

    public function test_pedido_com_token_inexistente_da_404(): void
    {
        $this->get(route('pedido.mostrar', (string) Str::uuid()))->assertNotFound();
    }

    public function test_sem_nenhuma_forma_disponivel_o_botao_finalizar_fica_desabilitado_e_explica(): void
    {
        // o caso da tela do Victor: lista com presente em cotas, sem Stripe e sem Pix
        config(['casamento.pix.chave' => null, 'app.debug' => true]);
        $cotas = Presente::factory()->create(['cotas_total' => 5, 'valor' => 500]);
        $this->naLista($cotas, 2);

        $html = $this->get(route('carrinho.mostrar'))->assertOk()
            ->assertSee('Nenhuma forma de envio está disponível')
            ->assertSee('Falta PIX_CHAVE no .env')
            ->assertSee('Falta STRIPE_SK no .env')
            ->getContent();

        $this->assertMatchesRegularExpression('/<button type="submit" class="btn btn-ouro w-100"[^>]*disabled/s', $html);
    }

    public function test_para_o_visitante_a_tela_nao_revela_o_que_falta_no_env(): void
    {
        config(['casamento.pix.chave' => null, 'app.debug' => false]);
        $cotas = Presente::factory()->create(['cotas_total' => 5, 'valor' => 500]);
        $this->naLista($cotas, 2);

        $this->get(route('carrinho.mostrar'))
            ->assertSee('Nenhuma forma de envio está disponível')
            ->assertDontSee('PIX_CHAVE')
            ->assertDontSee('STRIPE_SK')
            ->assertDontSee('.env');
    }

    public function test_com_ao_menos_uma_forma_o_botao_finalizar_fica_habilitado(): void
    {
        $inteiro = Presente::factory()->create(['cotas_total' => 1]);
        $this->naLista($inteiro);

        $html = $this->get(route('carrinho.mostrar'))->getContent();

        $this->assertDoesNotMatchRegularExpression('/<button type="submit" class="btn btn-ouro w-100"[^>]*disabled/s', $html);
    }

    public function test_apagar_dados_depois_do_casamento_apaga_tambem_os_pedidos_e_as_mensagens(): void
    {
        $p = Presente::factory()->create(['cotas_total' => 1]);
        $this->naLista($p);
        $this->finalizar('pix', ['mensagem' => 'Felicidades, Ana e Bruno!']);
        $this->assertDatabaseCount('pedidos', 1);

        $this->artisan('casamento:apagar-dados', ['--confirmo' => true])->assertSuccessful();

        $this->assertDatabaseCount('pedidos', 0);
        $this->assertDatabaseCount('contribuicoes', 0);
        $this->assertDatabaseCount('convidados', 0);
        $this->assertDatabaseMissing('pedidos', ['mensagem' => 'Felicidades, Ana e Bruno!']);
        $this->assertSame(1, Presente::count()); // a lista de presentes em si continua
    }
}
