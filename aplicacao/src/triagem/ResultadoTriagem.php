<?php
declare(strict_types=1);

require_once __DIR__ . '/LinhaResultadoTriagem.php';

/**
 * COMPOSICAO: cria internamente cada LinhaResultadoTriagem e controla seu
 * ciclo de vida. Nao recebe objetos de linha compartilhados de fora.
 * Entrega arrays por valor para preservar o formato publico do simulador.
 */
final class ResultadoTriagem
{
    /** @var list<LinhaResultadoTriagem> */
    private array $linhas = [];

    /** @param array<string,mixed> $dados */
    public function adicionar(array $dados): void
    {
        $this->linhas[] = new LinhaResultadoTriagem($dados);
    }

    /** @return list<array<string,mixed>> */
    public function linhas(): array
    {
        return array_map(
            static fn (LinhaResultadoTriagem $linha): array => $linha->paraArray(),
            $this->linhas
        );
    }

    public function total(): int
    {
        return count($this->linhas);
    }
}
