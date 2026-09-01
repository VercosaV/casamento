<?php

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
     */ 
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
     */
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
     */
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
     */
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
    */
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
     */
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
     */
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
