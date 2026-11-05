<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contribuicao extends Model
{
    // Obrigatório: sem isto o Laravel procuraria a tabela "contribuicaos"
    // (ele pluraliza em inglês).
    protected $table = 'contribuicoes';

    protected $fillable = ['presente_id', 'convidado_id', 'quantidade_cotas', 'mensagem'];

    public function presente(): BelongsTo
    {
        return $this->belongsTo(Presente::class);
    }

    public function convidado(): BelongsTo
    {
        return $this->belongsTo(Convidado::class);
    }
}