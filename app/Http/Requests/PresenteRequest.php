<?php

namespace App\Http\Requests;

use App\Models\Presente;
use Illuminate\Foundation\Http\FormRequest;

class PresenteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // a rota já está protegida pelo middleware auth:api
    }

    public function rules(): array
    {
        return [
            'nome'        => ['required', 'string', 'max:255'],
            'descricao'   => ['nullable', 'string', 'max:1000'],
            'valor'       => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'link'        => ['nullable', 'url', 'max:255'],
            'categoria'   => ['nullable', 'string', 'max:60'],
            'cotas_total' => ['required', 'integer', 'min:' . max(1, $this->cotasReservadas()), 'max:100'],
            'imagem'      => ['nullable', 'image', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'cotas_total.min' => "Já existem {$this->cotasReservadas()} cota(s) reservadas. O total não pode ser menor que isso.",
            'imagem.image'    => 'O arquivo precisa ser uma imagem.',
            'imagem.max'      => 'A imagem pode ter no máximo 2 MB.',
        ];
    }

    private function cotasReservadas(): int
    {
        $rota = $this->route('presente');
        $id   = $rota instanceof Presente ? $rota->id : $rota;

        if (! $id) {
            return 0; // criando um presente novo
        }

        return (int) Presente::find($id)?->contribuicoes()->sum('quantidade_cotas');
    }
}