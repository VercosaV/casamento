<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contribuicao extends Model
{
    public const PENDENTE = Pedido::PENDENTE;
    public const PAGA = Pedido::PAGA;
    public const RESERVADA = Pedido::RESERVADA;
    public const ENTREGUE = Pedido::ENTREGUE;

    // Já são presentes de verdade (dinheiro confirmado, ou comprado e reservado/entregue).
    public const ESCOLHIDOS = [self::PAGA, self::RESERVADA, self::ENTREGUE];

    // Obrigatório: sem isto o Laravel procuraria a tabela "contribuicaos"
    // (ele pluraliza em inglês).
    protected $table = 'contribuicoes';

    // Uma contribuição é UMA LINHA do pedido: este presente, esta quantidade de cotas.
    // A forma de envio, o token, o Stripe e o pagamento ficam no Pedido.
    // O status e o expira_em são copiados para cá só para a regra "cota ocupada" ser uma consulta simples.
    protected $fillable = [
        'pedido_id', 'presente_id', 'convidado_id', 'quantidade_cotas', 'mensagem',
        'status', 'valor_centavos', 'expira_em',
    ];

    protected function casts(): array
    {
        return ['expira_em' => 'datetime'];
    }

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }

    public function presente(): BelongsTo
    {
        return $this->belongsTo(Presente::class);
    }

    public function convidado(): BelongsTo
    {
        return $this->belongsTo(Convidado::class);
    }

    // Contribuições que OCUPAM cotas: pagas, reservadas/entregues (comprar), ou pendentes
    // que ainda estão dentro do prazo. Pendente vencida não segura nada.
    // Este é o ÚNICO lugar que define "cota ocupada": todo o resto reaproveita.
    public function scopeOcupandoCota(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereIn('status', self::ESCOLHIDOS)
              ->orWhere(function (Builder $q) {
                  $q->where('status', self::PENDENTE)->where('expira_em', '>', now());
              });
        });
    }

    protected function valorReais(): Attribute
    {
        return Attribute::get(fn () => $this->valor_centavos === null ? null : $this->valor_centavos / 100);
    }
}
