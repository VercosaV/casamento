<?php

namespace App\Http\Controllers;

use App\Models\Presente;
use App\Support\Carrinho;
use Illuminate\Http\Request;

class CarrinhoController extends Controller
{
    // Página "Minha lista": o resumo e as 3 formas de enviar.
    public function mostrar()
    {
        $presentes = Presente::withSum('contribuicoesAtivas', 'quantidade_cotas')
            ->whereIn('id', array_keys(Carrinho::itens()))->get()->keyBy('id');

        $linhas = collect(Carrinho::itens())->map(function (int $cotas, int $id) use ($presentes) {
            $presente = $presentes->get($id);

            if (! $presente) {
                return null; // o presente foi apagado pelos noivos
            }

            return [
                'presente' => $presente,
                'cotas' => $cotas,
                'valor_centavos' => $presente->valor_cota_centavos * $cotas,
                // alguém pode ter pegado as cotas enquanto a pessoa decidia
                'indisponivel' => $cotas > $presente->cotas_restantes,
            ];
        })->filter()->values();

        $total = $linhas->sum('valor_centavos');
        $temIndisponivel = $linhas->contains('indisponivel', true);

        // Cada forma só fica habilitada quando faz sentido para ESTA lista.
        // 'motivo' é o que o CONVIDADO lê. 'dica' é o que falta configurar: só aparece com APP_DEBUG ligado
        // (no seu computador), para um visitante não ver detalhes do seu servidor.
        $formas = [
            'comprar' => [
                'ativa' => $linhas->isNotEmpty() && $linhas->every(fn ($l) => $l['presente']->cotas_total === 1),
                'motivo' => 'Disponível só para presentes inteiros (sem cotas). Para cotas, use uma das outras formas.',
                'dica' => 'Tire da lista os presentes que têm cotas, ou escolha um presente inteiro.',
            ],
            'stripe' => [
                'ativa' => config('stripe.habilitado') && $total >= config('stripe.valor_minimo_centavos'),
                'motivo' => config('stripe.habilitado')
                    ? 'O valor mínimo para pagar online é R$ '.number_format(config('stripe.valor_minimo_centavos') / 100, 2, ',', '.').'.'
                    : 'Indisponível no momento.',
                'dica' => config('stripe.habilitado') ? null : 'Falta STRIPE_SK no .env (depois: php artisan config:clear).',
            ],
            'pix' => [
                'ativa' => filled(config('casamento.pix.chave')),
                'motivo' => 'Indisponível no momento.',
                'dica' => 'Falta PIX_CHAVE no .env (e PIX_BENEFICIARIO entre aspas). Depois: php artisan config:clear.',
            ],
        ];

        $nenhumaForma = collect($formas)->doesntContain('ativa', true);

        return view('carrinho.mostrar', compact('linhas', 'total', 'formas', 'temIndisponivel', 'nenhumaForma'));
    }

    public function adicionar(Request $request, Presente $presente)
    {
        $restantes = $presente->cotas_restantes;

        if ($restantes < 1) {
            return redirect()->route('presentes.index')->with('erro', 'Esse presente já foi totalmente escolhido por outras pessoas.');
        }

        $dados = $request->validate([
            'quantidade_cotas' => ['required', 'integer', 'min:1', 'max:'.$restantes],
        ], [
            'quantidade_cotas.max' => "Restam apenas {$restantes} cota(s) desse presente.",
        ]);

        Carrinho::adicionar($presente->id, $dados['quantidade_cotas']);

        return redirect()->route('presentes.index')
            ->with('mensagem', '"'.$presente->nome.'" foi para a sua lista. Escolha mais presentes ou finalize em "Minha lista".');
    }

    public function remover(Presente $presente)
    {
        Carrinho::remover($presente->id);

        return redirect()->route('carrinho.mostrar')->with('mensagem', 'Presente removido da sua lista.');
    }
}
