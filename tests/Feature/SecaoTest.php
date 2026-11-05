<?php

namespace Tests\Feature;

use App\Models\Foto;
use App\Models\Secao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecaoTest extends TestCase
{
    use RefreshDatabase;

    // PNG real de 1x1 pixel. Evita UploadedFile::fake()->image(), que exige a extensão GD.
    private function png(string $nome): UploadedFile
    {
        $bytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

        return UploadedFile::fake()->createWithContent($nome, $bytes);
    }

    public function test_home_mostra_as_secoes_padrao_e_o_menu_centralizado(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Victor')
            ->assertSee('O casal')
            ->assertSee('Informações úteis')
            ->assertSee('Lista de presentes')
            ->assertSee('Confirme sua presença')
            ->assertSee('id="countdown"', false);
    }

    public function test_contagem_usa_o_fuso_de_brasilia(): void
    {
        $this->get('/')->assertSee('data-target="2027-07-10T16:00:00-03:00"', false);
    }

    public function test_secao_escondida_some_da_home_e_do_menu(): void
    {
        $this->noivoLogado();
        $casal = Secao::where('slug', 'o-casal')->first();

        $this->post(route('secoes.visibilidade', $casal))->assertRedirect();
        $this->assertFalse($casal->fresh()->visivel);

        $this->get('/')->assertDontSee('id="o-casal"', false)->assertDontSee('href="http://localhost:8000#o-casal"', false);
    }

    public function test_mover_troca_a_ordem_e_a_home_obedece(): void
    {
        $this->noivoLogado();
        $info = Secao::where('slug', 'informacoes')->first();

        $this->post(route('secoes.mover', $info), ['direcao' => 'sobe'])->assertRedirect();

        $slugs = Secao::orderBy('ordem')->pluck('slug')->all();
        $this->assertSame(['home', 'informacoes', 'o-casal'], $slugs);

        $html = $this->get('/')->getContent();
        $this->assertLessThan(strpos($html, 'id="o-casal"'), strpos($html, 'id="informacoes"'));
    }

    public function test_nenhuma_secao_passa_na_frente_do_topo(): void
    {
        $this->noivoLogado();
        $casal = Secao::where('slug', 'o-casal')->first();

        $this->post(route('secoes.mover', $casal), ['direcao' => 'sobe']);

        $this->assertSame('home', Secao::orderBy('ordem')->first()->slug);
    }

    public function test_edita_textos_e_a_data_do_casamento(): void
    {
        $this->noivoLogado();
        $inicio = Secao::where('tipo', 'inicio')->first();

        $this->put(route('secoes.update', $inicio), [
            'titulo' => 'Ana & Bruno',
            'conteudo' => 'Venham comemorar!',
            'data_evento' => '2027-08-20T17:30',
            'no_menu' => '1',
        ])->assertRedirect(route('secoes.index'));

        $this->get('/')
            ->assertSee('Venham comemorar!')
            ->assertSee('data-target="2027-08-20T17:30:00-03:00"', false);
    }

    public function test_html_digitado_no_texto_nao_executa(): void
    {
        $this->noivoLogado();
        $info = Secao::where('slug', 'informacoes')->first();

        $this->put(route('secoes.update', $info), [
            'titulo' => 'Info', 'conteudo' => '<script>alert(1)</script>',
        ]);

        $this->get('/')->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_cria_secao_nova_no_fim_e_ela_aparece_no_menu(): void
    {
        $this->noivoLogado();

        $this->post(route('secoes.store'), ['titulo' => 'Local da cerimônia', 'tipo' => 'texto', 'conteudo' => 'Igreja Matriz'])
            ->assertRedirect();

        $nova = Secao::where('slug', 'local-da-cerimonia')->first();
        $this->assertNotNull($nova);
        $this->assertSame(Secao::max('ordem'), $nova->ordem);

        $this->get('/')->assertSee('Igreja Matriz')->assertSee('Local da cerimônia');
    }

    public function test_nao_exclui_nem_esconde_o_topo(): void
    {
        $this->noivoLogado();
        $inicio = Secao::where('tipo', 'inicio')->first();

        $this->delete(route('secoes.destroy', $inicio))->assertSessionHas('erro');
        $this->post(route('secoes.visibilidade', $inicio))->assertSessionHas('erro');

        $this->assertTrue($inicio->fresh()->visivel);
        $this->assertNotNull(Secao::find($inicio->id));
    }

    public function test_envia_e_remove_fotos_da_galeria(): void
    {
        Storage::fake('public');
        $this->noivoLogado();
        $casal = Secao::where('slug', 'o-casal')->first();

        $this->post(route('secoes.fotos.store', $casal), [
            'fotos' => [$this->png('a.png'), $this->png('b.png')],
        ])->assertSessionHasNoErrors();

        $this->assertSame(2, $casal->fotos()->count());
        $this->get('/')->assertSee('carousel-item', false)->assertDontSee('As fotos do casal aparecerão aqui');

        $foto = $casal->fotos()->first();
        Storage::disk('public')->assertExists($foto->caminho);

        $this->delete(route('fotos.destroy', $foto))->assertRedirect();
        Storage::disk('public')->assertMissing($foto->caminho);
        $this->assertSame(1, Foto::count());
    }

    public function test_rejeita_arquivo_que_nao_e_imagem(): void
    {
        Storage::fake('public');
        $this->noivoLogado();
        $casal = Secao::where('slug', 'o-casal')->first();

        $this->post(route('secoes.fotos.store', $casal), ['fotos' => [UploadedFile::fake()->create('virus.pdf', 10, 'application/pdf')]])
            ->assertSessionHasErrors('fotos.0');

        $this->assertSame(0, Foto::count());
    }
}
