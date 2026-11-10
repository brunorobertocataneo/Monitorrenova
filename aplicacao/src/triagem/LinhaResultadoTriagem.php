<?php
declare(strict_types=1);

/**
 * Parte imutavel de um resultado de triagem. A criacao fica restrita ao
 * objeto ResultadoTriagem que agrega as linhas de uma execucao.
 */
final class LinhaResultadoTriagem
{
    /** @param array<string,mixed> $dados */
    public function __construct(private readonly array $dados) {}

    /** @return array<string,mixed> */
    public function paraArray(): array
    {
        return $this->dados;
    }
}
