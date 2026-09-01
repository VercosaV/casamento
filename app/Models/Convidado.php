<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Convidado extends Model
{

    protected $table = 'convidados';
    
    public $incrementing = true;

    protected $fillable = [
        'nome', 'telefone', 'cpf',
        'confirma_presenca',
        'email'
    ];

}
