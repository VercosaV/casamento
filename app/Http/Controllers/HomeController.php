<?php

namespace App\Http\Controllers;

use App\Models\Secao;

class HomeController extends Controller
{
    // A home monta a página a partir das seções que os noivos deixaram
    // visíveis, na ordem que escolheram. Para mudar o convite não é
    // preciso mexer em código: tudo vem do banco.
    public function index()
    {
        $secoes = Secao::visiveis()->with('fotos')->get();

        return view('home', compact('secoes'));
    }
}
