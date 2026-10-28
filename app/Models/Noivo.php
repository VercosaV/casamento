<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Noivo extends Model
{
    use HasFactory;

    protected $table = 'noivos';

    public $incrementing = true;

    protected $fillable = [
        'nome', 'email', 'user_id',
    ];
}
