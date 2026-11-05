<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Foto extends Model
{
    protected $table = 'fotos';

    protected $fillable = ['secao_id', 'caminho', 'legenda', 'ordem'];

    public function secao(): BelongsTo
    {
        return $this->belongsTo(Secao::class);
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->caminho);
    }
}
