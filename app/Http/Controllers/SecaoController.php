<?php

namespace App\Http\Controllers;

use App\Models\Foto;
use App\Models\Secao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

// Painel dos noivos: editar a página principal do convite.
class SecaoController extends Controller
{
    public function index()
    {
        $secoes = Secao::orderBy('ordem')->withCount('fotos')->get();

        return view('secoes.index', compact('secoes'));
    }

    public function create()
    {
        return view('secoes.create');
    }

    public function store(Request $request)
    {
        $dados = $request->validate([
            'titulo'   => ['required', 'string', 'max:120'],
            'tipo'     => ['required', Rule::in(['texto', 'galeria'])],
            'conteudo' => ['nullable', 'string', 'max:5000'],
        ]);

        $dados['slug']  = $this->slugUnico($dados['titulo']);
        $dados['ordem'] = (int) Secao::max('ordem') + 1; // entra no fim da página

        $secao = Secao::create($dados);

        return redirect()->route('secoes.edit', $secao)
            ->with('mensagem', 'Seção criada! Complete o conteúdo abaixo.');
    }

    public function edit(Secao $secao)
    {
        $secao->load('fotos');

        return view('secoes.edit', compact('secao'));
    }

    public function update(Request $request, Secao $secao)
    {
        $regras = [
            'titulo'      => ['required', 'string', 'max:120'],
            'rotulo_menu' => ['nullable', 'string', 'max:40'],
            'conteudo'    => ['nullable', 'string', 'max:5000'],
            'imagem'      => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];

        if ($secao->ehInicio()) {
            $regras['data_evento'] = ['required', 'date'];
        }

        $dados = $request->validate($regras, [
            'imagem.image' => 'O arquivo precisa ser uma imagem (jpg, png ou webp).',
            'imagem.max'   => 'A imagem pode ter no máximo 4 MB.',
        ]);

        $dados['no_menu'] = $request->boolean('no_menu');
        unset($dados['imagem']);

        // Foto de fundo (início) ou foto ao lado do texto (texto)
        if ($secao->tipo !== 'galeria') {
            if ($request->boolean('remover_imagem') && $secao->imagem) {
                Storage::disk('public')->delete($secao->imagem);
                $dados['imagem'] = null;
            }

            if ($request->hasFile('imagem')) {
                if ($secao->imagem) {
                    Storage::disk('public')->delete($secao->imagem);
                }
                $dados['imagem'] = $request->file('imagem')->store('secoes', 'public');
            }
        }

        $secao->update($dados);

        return redirect()->route('secoes.index')->with('mensagem', 'Seção salva!');
    }

    public function destroy(Secao $secao)
    {
        if ($secao->ehInicio()) {
            return back()->with('erro', 'A seção inicial não pode ser excluída.');
        }

        foreach ($secao->fotos as $foto) {
            Storage::disk('public')->delete($foto->caminho);
        }
        if ($secao->imagem) {
            Storage::disk('public')->delete($secao->imagem);
        }

        $secao->delete(); // as linhas de fotos saem junto (cascadeOnDelete)

        return redirect()->route('secoes.index')->with('mensagem', 'Seção excluída.');
    }

    // Mostrar / esconder do convite sem apagar o conteúdo.
    public function alternarVisibilidade(Secao $secao)
    {
        if ($secao->ehInicio()) {
            return back()->with('erro', 'A seção inicial sempre aparece.');
        }

        $secao->update(['visivel' => ! $secao->visivel]);

        return back()->with('mensagem', $secao->visivel ? 'Seção visível no convite.' : 'Seção escondida do convite.');
    }

    // Sobe ou desce a seção trocando a ordem com a vizinha.
    public function mover(Request $request, Secao $secao)
    {
        $direcao = $request->validate([
            'direcao' => ['required', Rule::in(['sobe', 'desce'])],
        ])['direcao'];

        DB::transaction(function () use ($secao, $direcao) {
            $vizinha = $direcao === 'sobe'
                ? Secao::where('ordem', '<', $secao->ordem)->orderByDesc('ordem')->first()
                : Secao::where('ordem', '>', $secao->ordem)->orderBy('ordem')->first();

            // O topo (início) fica sempre em primeiro lugar.
            if (! $vizinha || $vizinha->ehInicio() || $secao->ehInicio()) {
                return;
            }

            [$minha, $dela] = [$secao->ordem, $vizinha->ordem];
            $secao->update(['ordem' => $dela]);
            $vizinha->update(['ordem' => $minha]);
        });

        return back();
    }

    // ---------- Fotos da galeria ----------

    public function enviarFotos(Request $request, Secao $secao)
    {
        abort_unless($secao->tipo === 'galeria', 404);

        $request->validate([
            'fotos'   => ['required', 'array', 'max:12'],
            'fotos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], [
            'fotos.required' => 'Escolha ao menos uma foto.',
            'fotos.*.image'  => 'Todos os arquivos precisam ser imagens (jpg, png ou webp).',
            'fotos.*.max'    => 'Cada foto pode ter no máximo 4 MB.',
        ]);

        $ordem = (int) $secao->fotos()->max('ordem');

        foreach ($request->file('fotos') as $arquivo) {
            $secao->fotos()->create([
                'caminho' => $arquivo->store('galeria', 'public'),
                'ordem'   => ++$ordem,
            ]);
        }

        return back()->with('mensagem', 'Fotos enviadas!');
    }

    public function excluirFoto(Foto $foto)
    {
        Storage::disk('public')->delete($foto->caminho);
        $foto->delete();

        return back()->with('mensagem', 'Foto removida.');
    }

    private function slugUnico(string $titulo): string
    {
        $base = Str::slug($titulo) ?: 'secao';
        $slug = $base;
        $n = 2;

        while (Secao::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$n++;
        }

        return $slug;
    }
}
