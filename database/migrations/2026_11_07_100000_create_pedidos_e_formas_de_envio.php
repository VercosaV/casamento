<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Um PEDIDO é a escolha de um convidado: 1 ou mais presentes enviados de UMA forma só.
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('convidado_id')->nullable()->constrained('convidados')->nullOnDelete();
            $table->string('forma', 10);                       // stripe | pix | comprar
            $table->string('status', 20)->default('pendente')->index();
            $table->unsignedInteger('total_centavos')->default(0);
            $table->uuid('token')->unique();                   // vai nas URLs do convidado (não expõe o id)
            $table->string('codigo', 8)->unique();             // referência curta, para achar o Pix no extrato
            $table->text('mensagem')->nullable();
            $table->string('stripe_session_id')->nullable()->unique();
            $table->string('stripe_payment_intent_id')->nullable();
            $table->timestamp('expira_em')->nullable();
            $table->timestamp('pago_em')->nullable();
            $table->timestamps();
        });

        Schema::table('contribuicoes', function (Blueprint $table) {
            $table->foreignId('pedido_id')->nullable()->after('id')->constrained('pedidos')->cascadeOnDelete();
        });

        // Dados que já existiam: cada contribuição antiga vira um pedido de 1 item.
        foreach (DB::table('contribuicoes')->get() as $c) {
            $pedidoId = DB::table('pedidos')->insertGetId([
                'convidado_id' => $c->convidado_id,
                // "reservada" era a escolha SEM pagamento: é a forma "comprar" de agora
                'forma' => $c->status === 'reservada' ? 'comprar' : 'stripe',
                'status' => $c->status,
                'total_centavos' => (int) $c->valor_centavos,
                'token' => $c->token ?: (string) Str::uuid(),
                'codigo' => $this->codigoUnico(),
                'mensagem' => $c->mensagem,
                'stripe_session_id' => $c->stripe_session_id,
                'stripe_payment_intent_id' => $c->stripe_payment_intent_id,
                'expira_em' => $c->expira_em,
                'pago_em' => $c->pago_em,
                'created_at' => $c->created_at,
                'updated_at' => $c->updated_at,
            ]);

            DB::table('contribuicoes')->where('id', $c->id)->update(['pedido_id' => $pedidoId]);
        }

        // Esses campos agora vivem no pedido.
        Schema::table('contribuicoes', function (Blueprint $table) {
            $table->dropUnique(['token']);
            $table->dropUnique(['stripe_session_id']);
        });
        Schema::table('contribuicoes', function (Blueprint $table) {
            $table->dropColumn(['token', 'stripe_session_id', 'stripe_payment_intent_id', 'pago_em']);
        });
    }

    public function down(): void
    {
        Schema::table('contribuicoes', function (Blueprint $table) {
            $table->uuid('token')->nullable()->unique();
            $table->string('stripe_session_id')->nullable()->unique();
            $table->string('stripe_payment_intent_id')->nullable();
            $table->timestamp('pago_em')->nullable();
        });

        Schema::table('contribuicoes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pedido_id');
        });

        Schema::dropIfExists('pedidos');
    }

    private function codigoUnico(): string
    {
        do {
            // sem 0/O/1/I para não confundir ao ler ou digitar
            $codigo = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 6);
        } while (DB::table('pedidos')->where('codigo', $codigo)->exists());

        return $codigo;
    }
};
