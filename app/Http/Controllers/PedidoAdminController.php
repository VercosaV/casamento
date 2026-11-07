<?php

namespace App\Http\Controllers;

use App\Models\Pedido;

// Painel dos noivos: acompanhar os pedidos e resolver o que é manual (Pix e "comprar").
class PedidoAdminController extends Controller
{
    public function index()
    {
        $pedidos = Pedido::with(['convidado', 'itens.presente'])->latest()->paginate(20);

        return view('pedidos.index', compact('pedidos'));
    }

    // Os noivos viram o Pix na conta e conferem aqui. Só vale para a forma "pix".
    public function confirmar(Pedido $pedido)
    {
        if ($pedido->forma !== Pedido::FORMA_PIX || $pedido->status === Pedido::PAGA) {
            return back()->with('erro', 'Só dá para confirmar manualmente um Pix que ainda não foi confirmado.');
        }

        $pedido->confirmar();

        // Pix que chegou depois de o prazo vencer: as cotas podem ter sido pegas por outra pessoa.
        $acima = $pedido->presentesAcimaDoLimite();

        if ($acima->isNotEmpty()) {
            return back()->with('erro', 'Pix confirmado, mas ATENÇÃO: o prazo tinha vencido e outra pessoa já escolheu as cotas de "'
                .$acima->pluck('nome')->implode('", "').'". Esse presente ficou com mais cotas do que existem. Combine com os convidados.');
        }

        return back()->with('mensagem', 'Pix confirmado. Obrigado ao convidado!');
    }

    // Desistência: o Pix não veio ou a pessoa não vai entregar. Devolve as cotas.
    public function cancelar(Pedido $pedido)
    {
        if (! in_array($pedido->forma, [Pedido::FORMA_PIX, Pedido::FORMA_COMPRAR], true)) {
            return back()->with('erro', 'Pedidos pagos pelo Stripe expiram sozinhos.');
        }

        if (! $pedido->liberar(Pedido::CANCELADA, [Pedido::PENDENTE, Pedido::RESERVADA])) {
            return back()->with('erro', 'Esse pedido não pode mais ser cancelado.');
        }

        return back()->with('mensagem', 'Pedido cancelado. As cotas voltaram para a lista.');
    }

    // "Comprar o presente": o presente chegou.
    public function entregar(Pedido $pedido)
    {
        if ($pedido->forma !== Pedido::FORMA_COMPRAR || ! $pedido->entregar()) {
            return back()->with('erro', 'Só dá para marcar como recebido um presente reservado.');
        }

        return back()->with('mensagem', 'Presente marcado como recebido!');
    }
}
