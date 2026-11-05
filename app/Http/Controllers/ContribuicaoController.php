<?php

namespace App\Http\Controllers;

use App\Models\Contribuicao;
use App\Models\Convidado;
use App\Models\Presente;
use App\Services\StripeGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Stripe\Exception\ApiErrorException;

class ContribuicaoController extends Controller
{
    // Painel dos noivos: rota protegida
    public function index()
    {
        $contribuicoes = Contribuicao::with(['presente', 'convidado'])->latest()->paginate(20);

        return view('contribuicoes.index', compact('contribuicoes'));
    }

    public function store(Request $request, Presente $presente, StripeGateway $stripe)
    {
        $convidadoId = session('convidado_id');

        if ($convidadoId && ! Convidado::whereKey($convidadoId)->exists()) {
            session()->forget('convidado_id');
            $convidadoId = null;
        }

        $dados = $request->validate([
            'quantidade_cotas' => ['required', 'integer', 'min:1', 'max:'.$presente->cotas_total],
            'mensagem' => ['nullable', 'string', 'max:400'],
            'nome' => [Rule::requiredIf($convidadoId === null), 'nullable', 'string', 'max:255'],
            'email' => [Rule::requiredIf($convidadoId === null), 'nullable', 'email', 'max:255'],
        ]);

        if ($convidadoId === null) {
            $convidado = Convidado::firstOrCreate(
                ['email' => $dados['email']],
                ['nome' => $dados['nome']]
            );
            $convidadoId = $convidado->id;
            session(['convidado_id' => $convidadoId]);
        }

        $online = config('stripe.habilitado');

        // O preço NUNCA vem do formulário: sai do banco. (O exemplo do telecare multiplicava
        // o valor digitado pelo usuário, então qualquer um podia pagar o que quisesse.)
        $valorCentavos = $presente->valor_cota_centavos * $dados['quantidade_cotas'];

        if ($online && $valorCentavos < config('stripe.valor_minimo_centavos')) {
            return redirect()->route('presentes.index')->with('erro', sprintf(
                'O valor mínimo para pagamento online é R$ %s.',
                number_format(config('stripe.valor_minimo_centavos') / 100, 2, ',', '.')
            ));
        }

        $resultado = DB::transaction(function () use ($presente, $dados, $convidadoId, $online, $valorCentavos) {
            // Trava a linha do presente até o fim da transação: duas pessoas não passam juntas.
            $travado = Presente::whereKey($presente->id)->lockForUpdate()->firstOrFail();

            $ocupadas = (int) $travado->contribuicoesAtivas()->sum('quantidade_cotas');
            $restantes = $travado->cotas_total - $ocupadas;

            if ($dados['quantidade_cotas'] > $restantes) {
                return ['restantes' => $restantes];
            }

            $contribuicao = $travado->contribuicoes()->create([
                'convidado_id'     => $convidadoId,
                'quantidade_cotas' => $dados['quantidade_cotas'],
                'mensagem'         => $dados['mensagem'] ?? null,
                'valor_centavos'   => $valorCentavos,
                'status'           => $online ? Contribuicao::PENDENTE : Contribuicao::RESERVADA,
                'token'            => (string) Str::uuid(),
                'expira_em'        => $online ? now()->addMinutes(config('stripe.minutos_reserva')) : null,
            ]);

            return ['contribuicao' => $contribuicao];
        });

        if (isset($resultado['restantes'])) {
            $restantes = $resultado['restantes'];
            $texto = $restantes === 0
                ? 'Esse presente acabou de ser totalmente escolhido por outras pessoas.'
                : "Restam apenas {$restantes} cota(s) desse presente. Escolha uma quantidade menor.";

            return redirect()->route('presentes.index')->with('erro', $texto);
        }

        /** @var Contribuicao $contribuicao */
        $contribuicao = $resultado['contribuicao'];

        // Stripe desligado: comportamento antigo (a pessoa só reserva).
        if (! $online) {
            return redirect()->route('presentes.index')
                ->with('mensagem', 'Muito obrigado! Sua escolha foi registrada.');
        }

        // A chamada ao Stripe fica FORA da transação: é uma requisição de rede (lenta) e não
        // deve segurar o bloqueio do banco enquanto espera.
        try {
            $sessao = $stripe->criarCheckout($contribuicao);
        } catch (ApiErrorException $e) {
            report($e); // vai para o log; o convidado não vê a mensagem técnica do Stripe
            $contribuicao->liberar(Contribuicao::FALHOU);

            return redirect()->route('presentes.index')
                ->with('erro', 'Não foi possível iniciar o pagamento agora. Tente novamente em instantes.');
        }

        $contribuicao->update(['stripe_session_id' => $sessao->id]);

        return redirect()->away($sessao->url);
    }
}
