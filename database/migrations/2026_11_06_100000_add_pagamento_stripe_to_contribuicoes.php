<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contribuicoes', function (Blueprint $table) {
            // pendente  -> aguardando o pagamento (segura as cotas até expira_em)
            // paga      -> o Stripe confirmou
            // reservada -> escolhido SEM pagamento online (modo antigo / Stripe desligado)
            // expirada, cancelada, falhou -> liberam as cotas
            $table->string('status', 20)->default('pendente')->after('quantidade_cotas')->index();
            $table->unsignedInteger('valor_centavos')->nullable()->after('status');   // total cobrado, em centavos
            $table->uuid('token')->nullable()->unique()->after('valor_centavos');     // identifica a compra nas URLs de retorno
            $table->string('stripe_session_id')->nullable()->unique()->after('token');
            $table->string('stripe_payment_intent_id')->nullable()->after('stripe_session_id');
            $table->timestamp('expira_em')->nullable()->after('stripe_payment_intent_id');
            $table->timestamp('pago_em')->nullable()->after('expira_em');
        });

        // O que já existia era "escolha sem pagamento": continua ocupando as cotas.
        DB::table('contribuicoes')->update(['status' => 'reservada']);
    }

    public function down(): void
    {
        Schema::table('contribuicoes', function (Blueprint $table) {
            $table->dropColumn([
                'status', 'valor_centavos', 'token', 'stripe_session_id',
                'stripe_payment_intent_id', 'expira_em', 'pago_em',
            ]);
        });
    }
};
