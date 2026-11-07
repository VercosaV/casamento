<?php

namespace App\Support;

// A "lista" do convidado enquanto ele escolhe: presente_id => quantidade de cotas.
// Fica na sessão (não vai para o banco): só vira dado de verdade quando ele finaliza o pedido.
class Carrinho
{
    private const CHAVE = 'carrinho';

    /** @return array<int,int> */
    public static function itens(): array
    {
        return session(self::CHAVE, []);
    }

    public static function adicionar(int $presenteId, int $cotas): void
    {
        $itens = self::itens();
        $itens[$presenteId] = $cotas;          // escolher de novo troca a quantidade
        session([self::CHAVE => $itens]);
    }

    public static function remover(int $presenteId): void
    {
        $itens = self::itens();
        unset($itens[$presenteId]);
        session([self::CHAVE => $itens]);
    }

    public static function limpar(): void
    {
        session()->forget(self::CHAVE);
    }

    public static function quantidade(): int
    {
        return count(self::itens());
    }
}
