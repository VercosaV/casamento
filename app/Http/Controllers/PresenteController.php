<?php

namespace App\Http\Controllers;

use App\Models\Presente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\PresenteRequest;


class PresenteController extends Controller
{
    // ---------- Público ----------

    public function index(Request $request)
    {
        $ordem       = $request->query('ordem', 'nome');
        $categoria   = $request->query('categoria');
        $disponiveis = $request->boolean('disponiveis');

        $query = Presente::query()->withSum('contribuicoesAtivas', 'quantidade_cotas');

        if ($categoria) {
            $query->where('categoria', $categoria);
        }

        if ($disponiveis) {
            // Filtra no banco (e não na coleção) para continuar funcionando com paginação.
            // Mesma regra de Contribuicao::scopeOcupandoCota(), escrita em SQL.
            $query->whereRaw(
                "cotas_total > (SELECT COALESCE(SUM(quantidade_cotas), 0)
                                FROM contribuicoes
                                WHERE contribuicoes.presente_id = presentes.id
                                  AND (status IN ('paga', 'reservada')
                                       OR (status = 'pendente' AND expira_em > ?)))",
                [now()]
            );
        }

        match ($ordem) {
            'menor-preco' => $query->orderBy('valor'),
            'maior-preco' => $query->orderByDesc('valor'),
            default       => $query->orderBy('nome'),
        };

        $presentes  = $query->paginate(9)->withQueryString();
        $categorias = Presente::whereNotNull('categoria')->distinct()->orderBy('categoria')->pluck('categoria');

        return view('presentes.index', compact('presentes', 'categorias', 'ordem', 'categoria', 'disponiveis'));
    }

    // ---------- Painel dos noivos ----------

    public function admin()
    {
        $presentes = Presente::withSum('contribuicoesAtivas', 'quantidade_cotas')->orderBy('nome')->get();

        return view('presentes.admin', compact('presentes'));
    }

    public function create()
    {
        return view('presentes.create');
    }

    public function store(PresenteRequest $request)
{
    $dados = $request->validated();
    unset($dados['imagem']);

    if ($request->hasFile('imagem')) {
        $dados['imagem'] = $request->file('imagem')->store('presentes', 'public');
    }

    Presente::create($dados);

    return redirect()->route('presentes.admin')->with('mensagem', 'Presente criado com sucesso!');
}

    public function edit(string $id)
    {
        $presente = Presente::findOrFail($id);

        return view('presentes.edit', compact('presente'));
    }

    public function update(PresenteRequest $request, string $id)
{
    $presente = Presente::findOrFail($id);

    $dados = $request->validated();
    unset($dados['imagem']);

    if ($request->hasFile('imagem')) {
        if ($presente->imagem) {
            Storage::disk('public')->delete($presente->imagem);
        }
        $dados['imagem'] = $request->file('imagem')->store('presentes', 'public');
    }

    $presente->update($dados);

    return redirect()->route('presentes.admin')->with('mensagem', 'Presente alterado!');
}

    public function destroy(string $id)
    {
        $presente = Presente::findOrFail($id);

        if ($presente->imagem) {
            Storage::disk('public')->delete($presente->imagem);
        }

        $presente->delete(); // as contribuições vão junto (cascadeOnDelete)

        return redirect()->route('presentes.admin')->with('mensagem', 'Presente excluído!');
    }

    
}