<?php

namespace App\Http\Controllers;

use App\Models\Noivo;
use Illuminate\Http\Request;

class NoivoController extends Controller
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
        $noivos = Noivo::all();
        return view('noivos.index', compact('noivos'));    

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
        return view('noivos.create');

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
        if( Noivo::create($request->all()))
            {
                return redirect()->route('noivos.index')->with('mensagem', 'noivo criado com sucesso!');
            } else {
                return redirect()->route('noivos.index')->with('mensagem', 'Erro ao inserir o noivo!');
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
        $noivos = Noivo::findOrFail($id);
        return view('noivos.show', compact('noivo'));
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
        $noivos = Noivo::findOrFail($id);
        return view('noivos.edit', compact('noivo'));
    }

    /**
     * Update the specified resource in storage.
     * 
     * receber os dados que foram editados e
     * fazer a alteração
     */
    public function update(Request $request, String $id)
    {
        $noivos = Noivo::findOrFail($id);
        if($noivos->update($request->all())){
            return redirect()->route('noivos.index')->with('mensagem', 'Noivo alterado!');
        } else {
            return redirect()->route('noivos.index')->with('mensagem', 'Erro ao alterar!');
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
        $noivos = Noivo::findOrFail($id);
        if($noivos->delete()) {
            return redirect()->route('noivos.index')->with('mensagem', 'Noivo(a) excluído(a)!');
        } else {
            return redirect()->route('noivos.index')->with('mensagem', 'Erro ao excluir!');
        }
    }
}
