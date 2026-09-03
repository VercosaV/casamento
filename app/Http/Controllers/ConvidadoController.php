<?php


namespace App\Http\Controllers;

use App\Models\Convidado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConvidadoController extends Controller
{
    public function index()
    {
        $convidados = Convidado::all();
        return view('convidados.index', compact('convidados'));
    }

    public function create()
    {
        return view('convidados.create');
    }

    public function store(Request $request)
    {
        $dados = $request->validate([
            'nome' => 'required|string|max:255',
            'telefone' => 'nullable|string|max:20',
            'cpf' => 'required|string|max:14|unique:convidados,cpf',
            'email' => 'nullable|email|max:255',
        ]);

        $dados['confirma_presenca'] = true;

        $convidado = Convidado::create($dados);

        session(['convidado_id' => $convidado->id]);

        // Se quem preencheu foi um dos noivos logados, volta pro painel;
        // se foi um convidado comum, segue pra tela pública de presentes.
        if (Auth::guard('api')->check()) {
            return redirect()->route('convidados.index')->with('mensagem', 'Convidado criado com sucesso!');
        }

        return redirect()
            ->route('presentes.index')
            ->with('mensagem', 'Presença confirmada! Obrigado, '.$convidado->nome.'.');
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
        if ($convidado->update($request->all())) {
            return redirect()->route('convidados.index')->with('mensagem', 'Convidado alterado!');
        }
        return redirect()->route('convidados.index')->with('mensagem', 'Erro ao alterar!');
    }

    public function destroy(string $id)
    {
        $convidado = Convidado::findOrFail($id);
        if ($convidado->delete()) {
            return redirect()->route('convidados.index')->with('mensagem', 'Convidado(a) excluído(a)!');
        }
        return redirect()->route('convidados.index')->with('mensagem', 'Erro ao excluir!');
    }
}






























/*
namespace App\Http\Controllers;

use App\Models\Convidado;
use Illuminate\Http\Request;

class ConvidadoController extends Controller
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
        $convidados = Convidado::all();
        return view('convidados.index', compact('convidados'));    

    }

    /**
     * Show the form for creating a new resource.
     * 
     *  responsável por chamar o form
     * que vai possibilitar criar 
     * um novo registro na tabela
     
    public function create()
    {
        return view('convidados.create');

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
        if( Convidado::create($request->all()))
            {
                return redirect()->route('convidados.index')->with('mensagem', 'Convidado criado com sucesso!');
            } else {
                return redirect()->route('convidados.index')->with('mensagem', 'Erro ao inserir o Convidado!');
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
        $convidados = Convidado::findOrFail($id);
        return view('convidados.show', compact('Convidado'));
    }

    /**
     * Show the form for editing the specified resource.
     * 
     * vai chamar um formulario para eu poder
     * editar um registro em específico
     * 
    
    public function edit(String $id)
    {
        $convidados = Convidado::findOrFail($id);
        return view('convidados.edit', compact('Convidado'));
    }

    /**
     * Update the specified resource in storage.
     * 
     * receber os dados que foram editados e
     * fazer a alteração
     
    public function update(Request $request, String $id)
    {
        $convidados = Convidado::findOrFail($id);
        if($convidados->update($request->all())){
            return redirect()->route('convidados.index')->with('mensagem', 'Convidado alterado!');
        } else {
            return redirect()->route('convidados.index')->with('mensagem', 'Erro ao alterar!');
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
        $convidados = Convidado::findOrFail($id);
        if($convidados->delete()) {
            return redirect()->route('convidados.index')->with('mensagem', 'Convidado(a) excluído(a)!');
        } else {
            return redirect()->route('convidados.index')->with('mensagem', 'Erro ao excluir!');
        }
    }
}
*/