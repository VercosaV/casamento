<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contribuicao extends Model
{
    public const PENDENTE = 'pendente';
    public const PAGA = 'paga';
    public const RESERVADA = 'reservada';
    public const EXPIRADA = 'expirada';
    public const CANCELADA = 'cancelada';
    public const FALHOU = 'falhou';

    // Obrigatório: sem isto o Laravel procuraria a tabela "contribuicaos"
    // (ele pluraliza em inglês).
    protected $table = 'contribuicoes';

    protected $fillable = [
        'presente_id', 'convidado_id', 'quantidade_cotas', 'mensagem',
        'status', 'valor_centavos', 'token', 'stripe_session_id',
        'stripe_payment_intent_id', 'expira_em', 'pago_em',
    ];

    protected function casts(): array
    {
        return [
            'expira_em' => 'datetime',
            'pago_em'   => 'datetime',
        ];
    }

    public function presente(): BelongsTo
    {
        return $this->belongsTo(Presente::class);
    }

    public function convidado(): BelongsTo
    {
        return $this->belongsTo(Convidado::class);
    }

    // Contribuições que OCUPAM cotas: pagas, reservadas sem pagamento, ou pendentes
    // que ainda estão dentro do prazo. Pendente vencida não segura nada.
    // Este é o ÚNICO lugar que define "cota ocupada": todo o resto reaproveita.
    public function scopeOcupandoCota(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereIn('status', [self::PAGA, self::RESERVADA])
              ->orWhere(function (Builder $q) {
                  $q->where('status', self::PENDENTE)->where('expira_em', '>', now());
              });
        });
    }

    // "pendente" com prazo vencido é, na prática, expirada (mesmo que o Stripe ainda não tenha avisado).
    protected function statusEfetivo(): Attribute
    {
        return Attribute::get(fn () => $this->status === self::PENDENTE && $this->expira_em?->isPast()
            ? self::EXPIRADA
            : $this->status);
    }

    protected function valorReais(): Attribute
    {
        return Attribute::get(fn () => $this->valor_centavos === null ? null : $this->valor_centavos / 100);
    }

    // ---------- Transições de estado ----------
    // Cada uma é UM update condicional: o próprio banco decide quem "ganha" se dois avisos
    // chegarem ao mesmo tempo, e repetir o mesmo aviso não faz nada (idempotente).

    /** Confirma o pagamento. Aceita vir de qualquer estado exceto "paga": dinheiro recebido é dinheiro recebido. */
    public function marcarComoPaga(?string $paymentIntentId): bool
    {
        $linhas = static::whereKey($this->id)
            ->where('status', '!=', self::PAGA)
            ->update([
                'status'                   => self::PAGA,
                'pago_em'                  => now(),
                'stripe_payment_intent_id' => $paymentIntentId,
            ]);

        $this->refresh();

        return $linhas === 1;
    }

    /** Libera as cotas. Só mexe em quem ainda está pendente: nunca desfaz um pagamento já confirmado. */
    public function liberar(string $novoStatus): bool
    {
        $linhas = static::whereKey($this->id)
            ->where('status', self::PENDENTE)
            ->update(['status' => $novoStatus]);

        $this->refresh();

        return $linhas === 1;
    }
}
