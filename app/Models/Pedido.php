<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

// Um pedido = a escolha de um convidado: 1 ou mais presentes, enviados de UMA forma:
//   stripe  -> paga online (cartão, ou Pix na página do Stripe)
//   pix     -> manda o Pix direto para a chave dos noivos; os noivos confirmam à mão
//   comprar -> compra o presente numa loja e entrega; só fica reservado
class Pedido extends Model
{
    public const FORMA_STRIPE = 'stripe';
    public const FORMA_PIX = 'pix';
    public const FORMA_COMPRAR = 'comprar';

    public const PENDENTE = 'pendente';     // stripe/pix aguardando o dinheiro (segura as cotas até expira_em)
    public const PAGA = 'paga';             // dinheiro confirmado
    public const RESERVADA = 'reservada';   // "comprar": reservado, ainda não chegou
    public const ENTREGUE = 'entregue';     // "comprar": os noivos receberam
    public const EXPIRADA = 'expirada';
    public const CANCELADA = 'cancelada';
    public const FALHOU = 'falhou';

    protected $fillable = [
        'convidado_id', 'forma', 'status', 'total_centavos', 'token', 'codigo', 'mensagem',
        'stripe_session_id', 'stripe_payment_intent_id', 'expira_em', 'pago_em',
    ];

    protected function casts(): array
    {
        return ['expira_em' => 'datetime', 'pago_em' => 'datetime'];
    }

    // Código curto para o convidado escrever na mensagem do Pix e os noivos acharem no extrato.
    // Sem 0/O/1/I, que se confundem ao ler.
    public static function gerarCodigo(): string
    {
        do {
            $codigo = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 6);
        } while (static::where('codigo', $codigo)->exists());

        return $codigo;
    }

    public function itens(): HasMany
    {
        return $this->hasMany(Contribuicao::class);
    }

    public function convidado(): BelongsTo
    {
        return $this->belongsTo(Convidado::class);
    }

    // "pendente" com prazo vencido é, na prática, expirada (mesmo antes de alguém avisar).
    protected function statusEfetivo(): Attribute
    {
        return Attribute::get(fn () => $this->status === self::PENDENTE && $this->expira_em?->isPast()
            ? self::EXPIRADA
            : $this->status);
    }

    protected function totalReais(): Attribute
    {
        return Attribute::get(fn () => $this->total_centavos / 100);
    }

    // ---------- Transições de estado ----------
    // Cada uma é UM update condicional (o banco decide quem "ganha" se dois avisos chegarem juntos)
    // e leva os itens junto, dentro de uma transação. Repetir o mesmo aviso não faz nada.

    /** Dinheiro confirmado (Stripe ou Pix conferido pelos noivos). Aceita vir de qualquer estado exceto "paga". */
    public function confirmar(?string $paymentIntentId = null): bool
    {
        return DB::transaction(function () use ($paymentIntentId) {
            $linhas = static::whereKey($this->id)
                ->whereNotIn('status', [self::PAGA])
                ->update([
                    'status' => self::PAGA,
                    'pago_em' => now(),
                    'stripe_payment_intent_id' => $paymentIntentId ?? $this->stripe_payment_intent_id,
                ]);

            if ($linhas === 1) {
                $this->itens()->update(['status' => self::PAGA]);
            }

            $this->refresh();

            return $linhas === 1;
        });
    }

    /** Devolve as cotas. Por padrão só mexe em quem ainda está pendente: nunca desfaz pagamento confirmado. */
    public function liberar(string $novoStatus, array $de = [self::PENDENTE]): bool
    {
        return DB::transaction(function () use ($novoStatus, $de) {
            $linhas = static::whereKey($this->id)->whereIn('status', $de)->update(['status' => $novoStatus]);

            if ($linhas === 1) {
                $this->itens()->update(['status' => $novoStatus]);
            }

            $this->refresh();

            return $linhas === 1;
        });
    }

    /** "Comprar": os noivos receberam o presente. */
    public function entregar(): bool
    {
        return DB::transaction(function () {
            $linhas = static::whereKey($this->id)->where('status', self::RESERVADA)->update(['status' => self::ENTREGUE]);

            if ($linhas === 1) {
                $this->itens()->update(['status' => self::ENTREGUE]);
            }

            $this->refresh();

            return $linhas === 1;
        });
    }

    /** Presentes deste pedido que ficaram com mais cotas ocupadas do que o total (pagamento tardio). */
    public function presentesAcimaDoLimite(): Collection
    {
        return $this->itens()->with('presente')->get()
            ->pluck('presente')->unique('id')
            ->filter(fn (Presente $p) => (int) $p->contribuicoesAtivas()->sum('quantidade_cotas') > $p->cotas_total);
    }
}
