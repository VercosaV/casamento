<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Presente extends Model
{
    protected $table = 'presentes';
    
    public $incrementing = true;

    protected $fillable = [
        'nome', 'descricao', 'valor',
        'link', 'comprado',
        'convidado_id'
    ];

}
