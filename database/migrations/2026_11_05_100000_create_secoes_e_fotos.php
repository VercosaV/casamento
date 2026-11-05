<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('secoes', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 20);                 // inicio | texto | galeria
            $table->string('slug')->unique();           // vira o #âncora e o link do menu
            $table->string('titulo');
            $table->string('rotulo_menu', 40)->nullable(); // nome curto no menu (se vazio, usa o título)
            $table->text('conteudo')->nullable();
            $table->string('imagem')->nullable();
            $table->dateTime('data_evento')->nullable(); // só a seção "inicio" usa
            $table->unsignedSmallInteger('ordem');
            $table->boolean('visivel')->default(true);
            $table->boolean('no_menu')->default(true);
            $table->timestamps();
        });

        Schema::create('fotos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('secao_id')->constrained('secoes')->cascadeOnDelete();
            $table->string('caminho');
            $table->string('legenda')->nullable();
            $table->unsignedSmallInteger('ordem')->default(0);
            $table->timestamps();
        });

        // Conteúdo inicial: o site já abre completo e os noivos só editam os textos.
        // Atenção: num insert de várias linhas, TODAS precisam ter as mesmas colunas
        // (por isso data_evento aparece como null nas seções que não usam data).
        $agora = now();
        DB::table('secoes')->insert([
            [
                'tipo' => 'inicio', 'slug' => 'home', 'titulo' => 'Victor & Kamilly',
                'rotulo_menu' => 'Home', 'ordem' => 1, 'visivel' => true, 'no_menu' => true,
                'conteudo' => 'Com muita alegria, convidamos você para celebrar conosco o começo da nossa vida a dois.',
                'data_evento' => '2027-07-10 16:00:00',
                'created_at' => $agora, 'updated_at' => $agora,
            ],
            [
                'tipo' => 'galeria', 'slug' => 'o-casal', 'titulo' => 'O casal',
                'rotulo_menu' => 'O casal', 'ordem' => 2, 'visivel' => true, 'no_menu' => true,
                'conteudo' => 'Conte aqui a história de vocês. Edite este texto em "Página do convite" no painel.',
                'data_evento' => null, 'created_at' => $agora, 'updated_at' => $agora,
            ],
            [
                'tipo' => 'texto', 'slug' => 'informacoes', 'titulo' => 'Informações úteis',
                'rotulo_menu' => 'Informações', 'ordem' => 3, 'visivel' => true, 'no_menu' => true,
                'conteudo' => "Local e horário: endereço, link do mapa e horário de chegada.\nTraje: sugestão de traje e cores a evitar.\nHospedagem: hotéis próximos, estacionamento e transporte.",
                'data_evento' => null, 'created_at' => $agora, 'updated_at' => $agora,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('fotos');
        Schema::dropIfExists('secoes');
    }
};
