<?php

namespace App\Http\Controllers;

use App\Models\Convidado;
use App\Models\Presente;
use Illuminate\Http\Request;

class PresenteController extends Controller
{
    // Página pública: lista de presentes disponíveis pros convidados
    public function index()
    {
        $presentes = Presente::all();
        return view('presentes.index', compact('presentes'));
    }

    // Painel do admin: tabela de gerenciamento (criar/editar/excluir)
    public function admin()
    {
        $presentes = Presente::all();
        return view('presentes.admin', compact('presentes'));
    }

    public function create()
    {
        return view('presentes.create');
    }

    public function store(Request $request)
    {
        if (Presente::create($request->all())) {
            return redirect()->route('presentes.admin')->with('mensagem', 'Presente criado com sucesso!');
        }
        return redirect()->route('presentes.admin')->with('mensagem', 'Erro ao inserir o Presente!');
    }

    public function show(string $id)
    {
        $presente = Presente::findOrFail($id);
        return view('presentes.show', compact('presente'));
    }

    // Um convidado escolhe esse presente pra comprar
    public function reservar(Request $request, string $id)
    {
        $presente = Presente::findOrFail($id);

        if ($presente->comprado) {
            return redirect()
                ->route('presentes.index')
                ->with('mensagem', 'Esse presente já foi escolhido por outro convidado.');
        }

        $convidadoId = session('convidado_id');

        if (! $convidadoId) {
            $dados = $request->validate([
                'nome' => 'required|string|max:255',
                'cpf' => 'required|string|max:14',
                'email' => 'nullable|email|max:255',
            ]);

            $convidado = Convidado::firstOrCreate(
                ['cpf' => $dados['cpf']],
                ['nome' => $dados['nome'], 'email' => $dados['email'] ?? null]
            );

            $convidadoId = $convidado->id;
            session(['convidado_id' => $convidadoId]);
        }

        $presente->update([
            'comprado' => true,
            'convidado_id' => $convidadoId,
        ]);

        return redirect()
            ->route('presentes.index')
            ->with('mensagem', 'Presente reservado! Muito obrigado 💝');
    }

    public function edit(string $id)
    {
        $presente = Presente::findOrFail($id);
        return view('presentes.edit', compact('presente'));
    }

    public function update(Request $request, string $id)
    {
        $presente = Presente::findOrFail($id);
        if ($presente->update($request->all())) {
            return redirect()->route('presentes.admin')->with('mensagem', 'Presente alterado!');
        }
        return redirect()->route('presentes.admin')->with('mensagem', 'Erro ao alterar!');
    }

    public function destroy(string $id)
    {
        $presente = Presente::findOrFail($id);
        if ($presente->delete()) {
            return redirect()->route('presentes.admin')->with('mensagem', 'Presente excluído!');
        }
        return redirect()->route('presentes.admin')->with('mensagem', 'Erro ao excluir!');
    }
}











/*
namespace App\Http\Controllers;

use App\Models\Presente;
use Illuminate\Http\Request;

class PresenteController extends Controller
{
      /**
     * Display a listing of the resource.
     *
     * responsável por trazer todos 
     * os dados, a lista
     * relacionada a tabela que
     * vamos traabalhar
     * vamos chamar a página
     * que vai ter todos
     * os registros de categoria
     * 
      
    public function index()
    {
        $presentes = Presente::all();
        return view('presentes.index', compact('presentes'));    

    }

    /**
     * Show the form for creating a new resource.
     * 
     *  responsável por chamar o form
     * que vai possibilitar criar 
     * um novo registro na tabela
     
    public function create()
    {
        return view('presentes.create');

    }

    /**
     * Store a newly created resource in storage.
     * 
     * a diferença é que o create eu vou chamar
     * o formulário para eu poder inserir os dados
     * e criar o registro
     * 
     * 
     * o store vou receber esses dados e vou 
     * armazenar no banco de dados
     
    public function store(Request $request)
    {
        if( Presente::create($request->all()))
            {
                return redirect()->route('presentes.index')->with('mensagem', 'Presente criado com sucesso!');
            } else {
                return redirect()->route('presentes.index')->with('mensagem', 'Erro ao inserir o Presente!');
            }
    }

    /**
     * Display the specified resource.
     * 
     * esse método vai ser responsável por chanar
     * uma view passae os dados para essa view
     * para mostrar os dados de um registro espe
     * cífico
     
    public function show(String $id)
    {
        $presentes = Presente::findOrFail($id);
        return view('presentes.show', compact('Presente'));
    }

    /**
     * Show the form for editing the specified resource.
     * 
     * vai chamar um formulario para eu poder
     * editar um registro em específico
     * 
    
    public function edit(String $id)
    {
        $presentes = Presente::findOrFail($id);
        return view('presentes.edit', compact('Presente'));
    }

    /**
     * Update the specified resource in storage.
     * 
     * receber os dados que foram editados e
     * fazer a alteração
     
    public function update(Request $request, String $id)
    {
        $presentes = Presente::findOrFail($id);
        if($presentes->update($request->all())){
            return redirect()->route('presentes.index')->with('mensagem', 'Presente alterado!');
        } else {
            return redirect()->route('presentes.index')->with('mensagem', 'Erro ao alterar!');
        }
    }

    /**
     * Remove the specified resource from storage.
     * 
     * vai receber um id por parametro
     * e vai excluir do banco de dados
     * 
     
    public function destroy(String $id)
    {
        $presentes = Presente::findOrFail($id);
        if($presentes->delete()) {
            return redirect()->route('presentes.index')->with('mensagem', 'Presente excluído!');
        } else {
            return redirect()->route('presentes.index')->with('mensagem', 'Erro ao excluir!');
        }
    }
}
*/