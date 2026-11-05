<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Campos novos em presentes
        Schema::table('presentes', function (Blueprint $table) {
            $table->string('imagem')->nullable()->after('link');
            $table->string('categoria')->nullable()->after('imagem');
            $table->unsignedSmallInteger('cotas_total')->default(1)->after('valor');
        });

        // 2) Tabela de contribuições
        Schema::create('contribuicoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('presente_id')->constrained('presentes')->cascadeOnDelete();
            $table->foreignId('convidado_id')->nullable()->constrained('convidados')->nullOnDelete();
            $table->unsignedSmallInteger('quantidade_cotas')->default(1);
            $table->text('mensagem')->nullable();
            $table->timestamps();
        });

        // 3) O que já estava reservado vira uma contribuição de 1 cota
        $reservados = DB::table('presentes')->where('comprado', true)->get(['id', 'convidado_id']);

        foreach ($reservados as $presente) {
            DB::table('contribuicoes')->insert([
                'presente_id'      => $presente->id,
                'convidado_id'     => $presente->convidado_id,
                'quantidade_cotas' => 1,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        }

        // 4) Só agora as colunas antigas podem sair
        Schema::table('presentes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('convidado_id');
            $table->dropColumn('comprado');
        });
    }

    public function down(): void
    {
        Schema::table('presentes', function (Blueprint $table) {
            $table->boolean('comprado')->default(false)->after('link');
            $table->foreignId('convidado_id')->nullable()->after('comprado')
                ->constrained('convidados')->nullOnDelete();
        });

        foreach (DB::table('contribuicoes')->get() as $c) {
            DB::table('presentes')->where('id', $c->presente_id)
                ->update(['comprado' => true, 'convidado_id' => $c->convidado_id]);
        }

        Schema::dropIfExists('contribuicoes');

        Schema::table('presentes', function (Blueprint $table) {
            $table->dropColumn(['imagem', 'categoria', 'cotas_total']);
        });
    }
};