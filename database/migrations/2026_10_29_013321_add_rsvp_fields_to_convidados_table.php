<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('convidados', function (Blueprint $table) {
            $table->unsignedTinyInteger('adultos')->default(1)->after('confirma_presenca');
            $table->unsignedTinyInteger('criancas')->default(0)->after('adultos');
            $table->text('observacoes')->nullable()->after('criancas');

            // CPF deixa de ser obrigatório (LGPD: não peça o que não precisa)
            $table->string('cpf')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('convidados', function (Blueprint $table) {
            $table->dropColumn(['adultos', 'criancas', 'observacoes']);
            // O CPF volta a ser obrigatório só se não houver registros sem CPF.
            $table->string('cpf')->nullable(false)->change();
        });
    }
};