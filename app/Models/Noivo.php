<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Noivo extends Model
{

    protected $table = 'noivos';
    
    public $incrementing = true;

    protected $fillable = [
        'nome', 'email', 'user_id'
    ];

}
