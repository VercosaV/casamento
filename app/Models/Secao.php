<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

// Uma "seção" é um bloco da página principal do convite.
// tipo: 'inicio'  -> o topo (nomes, data, contagem, boas-vindas); só existe uma
//       'texto'   -> título + texto (+ foto opcional): informações, local, traje...
//       'galeria' -> título + texto + várias fotos (carrossel): "O casal"
class Secao extends Model
{
    protected $table = 'secoes';

    protected $fillable = [
        'tipo', 'slug', 'titulo', 'rotulo_menu', 'conteudo',
        'imagem', 'data_evento', 'ordem', 'visivel', 'no_menu',
    ];

    protected function casts(): array
    {
        return [
            'data_evento' => 'datetime',
            'visivel'     => 'boolean',
            'no_menu'     => 'boolean',
        ];
    }

    public function fotos(): HasMany
    {
        return $this->hasMany(Foto::class)->orderBy('ordem');
    }

    // Seções que o convidado vê, na ordem escolhida pelos noivos.
    public function scopeVisiveis(Builder $query): Builder
    {
        return $query->where('visivel', true)->orderBy('ordem');
    }

    // Seções que aparecem como link no menu do topo.
    public function scopeNoMenu(Builder $query): Builder
    {
        return $query->visiveis()->where('no_menu', true);
    }

    public function getRotuloAttribute(): string
    {
        return $this->rotulo_menu ?: $this->titulo;
    }

    public function getImagemUrlAttribute(): ?string
    {
        return $this->imagem ? Storage::disk('public')->url($this->imagem) : null;
    }

    public function ehInicio(): bool
    {
        return $this->tipo === 'inicio';
    }
}
