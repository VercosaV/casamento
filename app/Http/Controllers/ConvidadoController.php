<?php

namespace App\Http\Controllers;

use App\Models\Convidado;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ConvidadoController extends Controller
{
    public function index(Request $request)
    {
        $query = Convidado::query();

        if ($busca = trim((string) $request->query('q'))) {
            $query->where(function ($q) use ($busca) {
                $q->where('nome', 'like', "%{$busca}%")
                    ->orWhere('email', 'like', "%{$busca}%");
            });
        }

        match ($request->query('presenca')) {
            'sim' => $query->where('confirma_presenca', true),
            'nao' => $query->where('confirma_presenca', false),
            'pendente' => $query->whereNull('confirma_presenca'),
            default => null,
        };

        $convidados = $query->orderBy('nome')->paginate(20)->withQueryString();

        return view('convidados.index', compact('convidados'));
    }

    public function create()
    {
        return view('convidados.create');
    }

    public function store(Request $request)
    {
        Convidado::create($this->validar($request));

        return redirect()->route('convidados.index')->with('mensagem', 'Convidado criado com sucesso!');
    }

    public function show(string $id)
    {
        $convidado = Convidado::findOrFail($id);

        return view('convidados.show', compact('convidado'));
    }

    public function edit(string $id)
    {
        $convidado = Convidado::findOrFail($id);

        return view('convidados.edit', compact('convidado'));
    }

    public function update(Request $request, string $id)
    {
        $convidado = Convidado::findOrFail($id);
        $convidado->update($this->validar($request, $convidado));

        return redirect()->route('convidados.index')->with('mensagem', 'Convidado alterado!');
    }

    public function destroy(string $id)
    {
        Convidado::findOrFail($id)->delete();

        return redirect()->route('convidados.index')->with('mensagem', 'Convidado excluído!');
    }

    // Regras compartilhadas por criar e editar. $convidado só existe na edição.
    private function validar(Request $request, ?Convidado $convidado = null): array
    {
        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('convidados', 'email')->ignore($convidado?->id)],
            'telefone' => ['nullable', 'string', 'max:20'],
            'confirma_presenca' => ['nullable', 'boolean'],
            'adultos' => ['required', 'integer', 'min:0', 'max:10'],
            'criancas' => ['required', 'integer', 'min:0', 'max:10'],
            'observacoes' => ['nullable', 'string', 'max:500'],
        ]);

        // Quem não vai não ocupa lugar. O return acima impedia este bloco de rodar.
        if ($request->input('confirma_presenca') === '0') {
            $dados['adultos'] = 0;
            $dados['criancas'] = 0;
        }

        return $dados;
    }

    public function exportar(): StreamedResponse
    {
        $arquivo = 'convidados-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () {
            $saida = fopen('php://output', 'w');
            fwrite($saida, "\xEF\xBB\xBF"); // BOM

            fputcsv($saida, ['Nome', 'E-mail', 'Telefone', 'Presença', 'Adultos', 'Crianças', 'Observações'], ';', '"', '');

            Convidado::orderBy('nome')->chunk(200, function ($lote) use ($saida) {
                foreach ($lote as $c) {
                    fputcsv($saida, [
                        $this->limpar($c->nome),
                        $this->limpar($c->email),
                        $this->limpar($c->telefone),
                        match ($c->confirma_presenca) {
                            true => 'Sim',
                            false => 'Não',
                            default => 'Pendente',
                        },
                        $c->adultos,
                        $c->criancas,
                        $this->limpar($c->observacoes),
                    ], ';', '"', '');
                }
            });

            fclose($saida);
        }, $arquivo, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function limpar(?string $valor): string
    {
        $valor = (string) $valor;

        return preg_match('/^[=+\-@]/', $valor) ? "'".$valor : $valor;
    }
}
