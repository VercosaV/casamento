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

    // Só as contribuições que seguram cota (pagas, reservadas, pendentes no prazo).
    public function contribuicoesAtivas(): HasMany
    {
        return $this->hasMany(Contribuicao::class)->ocupandoCota();
    }

    // Usa a soma já carregada por withSum('contribuicoesAtivas', ...) quando existir
    // (1 consulta para a lista toda). Sem ela, consulta o banco (1 por presente).
    protected function cotasReservadas(): Attribute
    {
        return Attribute::get(
            fn () => (int) ($this->contribuicoes_ativas_sum_quantidade_cotas
                ?? $this->contribuicoesAtivas()->sum('quantidade_cotas'))
        );
    }

    // Valor de UMA cota em centavos (inteiro). O Stripe só aceita inteiros: 33,33 reais = 3333.
    // round() depois de multiplicar evita o clássico 19.99 * 100 = 1998.9999999999998.
    protected function valorCotaCentavos(): Attribute
    {
        return Attribute::get(fn () => (int) round(((float) $this->valor * 100) / $this->cotas_total));
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