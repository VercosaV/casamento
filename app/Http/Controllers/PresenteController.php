<?php

namespace App\Http\Controllers;

use App\Models\Presente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PresenteController extends Controller
{
    // ---------- Público ----------

    public function index(Request $request)
    {
        $ordem       = $request->query('ordem', 'nome');
        $categoria   = $request->query('categoria');
        $disponiveis = $request->boolean('disponiveis');

        $query = Presente::query()->withSum('contribuicoes', 'quantidade_cotas');

        if ($categoria) {
            $query->where('categoria', $categoria);
        }

        if ($disponiveis) {
            // Filtra no banco (e não na coleção) para continuar funcionando com paginação.
            $query->whereRaw(
                'cotas_total > (SELECT COALESCE(SUM(quantidade_cotas), 0)
                                FROM contribuicoes WHERE contribuicoes.presente_id = presentes.id)'
            );
        }

        match ($ordem) {
            'menor-preco' => $query->orderBy('valor'),
            'maior-preco' => $query->orderByDesc('valor'),
            default       => $query->orderBy('nome'),
        };

        $presentes  = $query->get();
        $categorias = Presente::whereNotNull('categoria')->distinct()->orderBy('categoria')->pluck('categoria');

        return view('presentes.index', compact('presentes', 'categorias', 'ordem', 'categoria', 'disponiveis'));
    }

    // ---------- Painel dos noivos ----------

    public function admin()
    {
        $presentes = Presente::withSum('contribuicoes', 'quantidade_cotas')->orderBy('nome')->get();

        return view('presentes.admin', compact('presentes'));
    }

    public function create()
    {
        return view('presentes.create');
    }

    public function store(Request $request)
    {
        $dados = $this->validar($request);
        unset($dados['imagem']); // o arquivo não vai direto para o banco

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

    public function update(Request $request, string $id)
    {
        $presente = Presente::findOrFail($id);

        $dados = $this->validar($request, $presente);
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

    private function validar(Request $request, ?Presente $presente = null): array
    {
        $reservadas = $presente ? (int) $presente->contribuicoes()->sum('quantidade_cotas') : 0;

        return $request->validate([
            'nome'        => ['required', 'string', 'max:255'],
            'descricao'   => ['nullable', 'string', 'max:1000'],
            'valor'       => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'link'        => ['nullable', 'url', 'max:255'],
            'categoria'   => ['nullable', 'string', 'max:60'],
            'cotas_total' => ['required', 'integer', 'min:' . max(1, $reservadas), 'max:100'],
            'imagem'      => ['nullable', 'image', 'max:2048'],
        ], [
            'cotas_total.min' => "Já existem {$reservadas} cota(s) reservadas. O total não pode ser menor que isso.",
            'imagem.image'    => 'O arquivo precisa ser uma imagem.',
            'imagem.max'      => 'A imagem pode ter no máximo 2 MB.',
        ]);
    }
}