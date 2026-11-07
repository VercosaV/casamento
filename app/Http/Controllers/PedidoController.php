<?php

namespace App\Http\Controllers;

use App\Models\Contribuicao;
use App\Models\Convidado;
use App\Models\Pedido;
use App\Models\Presente;
use App\Services\StripeGateway;
use App\Support\Carrinho;
use App\Support\ConferenciaStripe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Stripe\Exception\ApiErrorException;

class PedidoController extends Controller
{
    // O convidado escolheu a forma na página "Minha lista" e clicou em finalizar.
    public function store(Request $request, StripeGateway $stripe)
    {
        $itens = Carrinho::itens();

        if (empty($itens)) {
            return redirect()->route('presentes.index')->with('erro', 'Sua lista está vazia. Escolha um presente primeiro.');
        }

        $convidadoId = session('convidado_id');

        if ($convidadoId && ! Convidado::whereKey($convidadoId)->exists()) {
            session()->forget('convidado_id');
            $convidadoId = null;
        }

        $dados = $request->validate([
            'forma' => ['required', Rule::in([Pedido::FORMA_STRIPE, Pedido::FORMA_PIX, Pedido::FORMA_COMPRAR])],
            'mensagem' => ['nullable', 'string', 'max:400'],
            'nome' => [Rule::requiredIf($convidadoId === null), 'nullable', 'string', 'max:255'],
            'email' => [Rule::requiredIf($convidadoId === null), 'nullable', 'email', 'max:255'],
        ], [
            'forma.required' => 'Escolha como você quer enviar o presente.',
        ]);

        $forma = $dados['forma'];

        // Os presentes e o valor vêm do BANCO. O formulário só diz a forma, a mensagem e quem é a pessoa.
        $presentes = Presente::whereIn('id', array_keys($itens))->get()->keyBy('id');

        if ($presentes->count() !== count($itens)) {
            foreach (array_diff(array_keys($itens), $presentes->keys()->all()) as $sumido) {
                Carrinho::remover($sumido);
            }

            return redirect()->route('carrinho.mostrar')->with('erro', 'Um dos presentes da sua lista não existe mais e foi removido. Confira e finalize de novo.');
        }

        $total = collect($itens)->sum(fn ($cotas, $id) => $presentes[$id]->valor_cota_centavos * $cotas);

        // A forma escolhida precisa ser permitida para ESTA lista (a tela já esconde, mas aqui é a garantia).
        $recusa = match (true) {
            $forma === Pedido::FORMA_STRIPE && ! config('stripe.habilitado') => 'O pagamento online ainda não está disponível.',
            $forma === Pedido::FORMA_STRIPE && $total < config('stripe.valor_minimo_centavos') => sprintf(
                'O valor mínimo para pagar online é R$ %s.', number_format(config('stripe.valor_minimo_centavos') / 100, 2, ',', '.')
            ),
            $forma === Pedido::FORMA_PIX && blank(config('casamento.pix.chave')) => 'A chave Pix dos noivos ainda não foi cadastrada.',
            $forma === Pedido::FORMA_COMPRAR && $presentes->contains(fn ($p) => $p->cotas_total !== 1) => 'Comprar o presente só vale para presentes inteiros, sem cotas.',
            default => null,
        };

        if ($recusa) {
            return redirect()->route('carrinho.mostrar')->with('erro', $recusa);
        }

        if ($convidadoId === null) {
            $convidadoId = Convidado::firstOrCreate(['email' => $dados['email']], ['nome' => $dados['nome']])->id;
            session(['convidado_id' => $convidadoId]);
        }

        $resultado = DB::transaction(function () use ($itens, $forma, $dados, $convidadoId, $total) {
            // Trava os presentes na MESMA ordem sempre (por id): duas pessoas com listas parecidas
            // não ficam esperando uma pela outra (deadlock).
            $travados = Presente::whereIn('id', array_keys($itens))->orderBy('id')->lockForUpdate()->get();

            foreach ($travados as $presente) {
                $restantes = $presente->cotas_total - (int) $presente->contribuicoesAtivas()->sum('quantidade_cotas');

                if ($itens[$presente->id] > $restantes) {
                    return ['conflito' => $presente->nome, 'restantes' => $restantes];
                }
            }

            $status = $forma === Pedido::FORMA_COMPRAR ? Pedido::RESERVADA : Pedido::PENDENTE;
            $expiraEm = match ($forma) {
                Pedido::FORMA_STRIPE => now()->addMinutes(config('stripe.minutos_reserva')),
                Pedido::FORMA_PIX => now()->addHours(config('casamento.pix.horas_reserva')),
                default => null, // comprar: fica reservado até os noivos cancelarem ou receberem
            };

            $pedido = Pedido::create([
                'convidado_id' => $convidadoId,
                'forma' => $forma,
                'status' => $status,
                'total_centavos' => $total,
                'token' => (string) Str::uuid(),
                'codigo' => Pedido::gerarCodigo(),
                'mensagem' => $dados['mensagem'] ?? null,
                'expira_em' => $expiraEm,
            ]);

            foreach ($travados as $presente) {
                $pedido->itens()->create([
                    'presente_id' => $presente->id,
                    'convidado_id' => $convidadoId,
                    'quantidade_cotas' => $itens[$presente->id],
                    'valor_centavos' => $presente->valor_cota_centavos * $itens[$presente->id],
                    'status' => $status,
                    'expira_em' => $expiraEm,
                ]);
            }

            return ['pedido' => $pedido];
        });

        if (isset($resultado['conflito'])) {
            $r = $resultado['restantes'];

            return redirect()->route('carrinho.mostrar')->with('erro', $r === 0
                ? '"'.$resultado['conflito'].'" acabou de ser totalmente escolhido por outras pessoas. Tire-o da lista para continuar.'
                : 'Restam apenas '.$r.' cota(s) de "'.$resultado['conflito'].'". Ajuste a quantidade e tente de novo.');
        }

        /** @var Pedido $pedido */
        $pedido = $resultado['pedido'];

        if ($forma !== Pedido::FORMA_STRIPE) {
            Carrinho::limpar();

            return redirect()->route('pedido.mostrar', $pedido->token);
        }

        // A chamada ao Stripe fica FORA da transação: é uma requisição de rede (lenta) e não
        // deve segurar o bloqueio do banco enquanto espera.
        try {
            $sessao = $stripe->criarCheckout($pedido);
        } catch (ApiErrorException $e) {
            report($e); // vai para o log; o convidado não vê a mensagem técnica do Stripe
            $pedido->liberar(Pedido::FALHOU);

            return redirect()->route('carrinho.mostrar')
                ->with('erro', 'Não foi possível iniciar o pagamento agora. Tente de novo em instantes ou escolha outra forma de enviar.');
        }

        $pedido->update(['stripe_session_id' => $sessao->id]);
        Carrinho::limpar();

        return redirect()->away($sessao->url);
    }

    // Página do pedido: instruções do Pix, confirmação da compra, ou o retorno do Stripe.
    // ATENÇÃO: estar nesta página NÃO prova que pagou (qualquer um digita a URL). Para o Stripe,
    // perguntamos a ele, com a nossa chave secreta, qual é a situação real.
    public function mostrar(string $token, StripeGateway $stripe)
    {
        $pedido = Pedido::where('token', $token)->with('itens.presente')->firstOrFail();

        if ($pedido->forma === Pedido::FORMA_STRIPE && $pedido->status === Pedido::PENDENTE && $pedido->stripe_session_id) {
            try {
                $sessao = $stripe->buscarCheckout($pedido->stripe_session_id);

                if ($sessao->payment_status === 'paid' && ConferenciaStripe::valorConfere($pedido, $sessao)) {
                    $pedido->confirmar(ConferenciaStripe::paymentIntentId($sessao));
                }
            } catch (ApiErrorException $e) {
                report($e); // sem problema: o webhook confirma depois
            }
        }

        return view('pedido.mostrar', compact('pedido'));
    }
}
