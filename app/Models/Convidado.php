<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Convidado extends Model
{
    use HasFactory;

    protected $fillable = [
        'nome',
        'telefone',        // mantenha até o passo 2.6 e depois remova da lista
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

    public function contribuicoes(): HasMany
    {
        return $this->hasMany(Contribuicao::class);
    }
}
