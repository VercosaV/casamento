<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Convidado extends Model
{
    protected $fillable = [
        'nome',
        'telefone',
        'cpf',            // mantenha até o passo 2.6 e depois remova da lista
        'email',
        'confirma_presenca',
        'adultos',
        'criancas',
        'observacoes',
    ];

    protected function casts(): array
    {
        return [
            'confirma_presenca' => 'boolean',
        ];
    }
}
