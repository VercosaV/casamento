<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Presente extends Model
{
    use HasFactory;

    protected $table = 'presentes';

    public $incrementing = true;

    protected $fillable = [
        'nome', 'descricao', 'valor', 'link',
        'imagem', 'categoria', 'cotas_total',
    ];

    public function contribuicoes(): HasMany
    {
        return $this->hasMany(Contribuicao::class);
    }

    // Usa a soma já carregada por withSum() quando existir (1 consulta para a lista toda).
    // Sem ela, consulta o banco (1 consulta por presente).
    protected function cotasReservadas(): Attribute
    {
        return Attribute::get(
            fn () => (int) ($this->contribuicoes_sum_quantidade_cotas
                ?? $this->contribuicoes()->sum('quantidade_cotas'))
        );
    }

    protected function cotasRestantes(): Attribute
    {
        return Attribute::get(fn () => max(0, $this->cotas_total - $this->cotas_reservadas));
    }

    protected function esgotado(): Attribute
    {
        return Attribute::get(fn () => $this->cotas_restantes === 0);
    }

    protected function valorCota(): Attribute
    {
        return Attribute::get(fn () => round((float) $this->valor / $this->cotas_total, 2));
    }

    protected function imagemUrl(): Attribute
    {
        return Attribute::get(
            fn () => $this->imagem ? Storage::disk('public')->url($this->imagem) : null
        );
    }
}