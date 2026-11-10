<?php
declare(strict_types=1);

require_once __DIR__ . '/RegraAvaliavel.php';

/**
 * AGREGACAO: recebe regras criadas fora do conjunto; nao e responsavel por
 * instancia-las. Os objetos podem continuar existindo sem este agrupamento.
 * A ordem e intencional: a primeira regra aplicavel tem prioridade.
 */
final class ConjuntoRegrasTriagem
{
    /** @var list<RegraAvaliavel> */
    private array $regras;

    /** @param list<RegraAvaliavel> $regras */
    public function __construct(array $regras)
    {
        if ($regras === [] || !array_is_list($regras)) {
            throw new InvalidArgumentException('Informe uma lista nao vazia de regras.');
        }
        foreach ($regras as $regra) {
            if (!$regra instanceof RegraAvaliavel) {
                throw new InvalidArgumentException('Todas as regras devem implementar RegraAvaliavel.');
            }
        }
        $this->regras = $regras;
    }

    /** @param array<string,mixed> $seguro @param array<string,mixed> $contexto */
    public function avaliar(array $seguro, array $contexto): ?string
    {
        foreach ($this->regras as $regra) {
            $motivo = $regra->avaliar($seguro, $contexto);
            if ($motivo !== null) {
                return $motivo;
            }
        }
        return null;
    }

    /** Permite demonstrar que os objetos agregados sao os mesmos recebidos. */
    public function quantidade(): int
    {
        return count($this->regras);
    }

    public function contem(RegraAvaliavel $regra): bool
    {
        foreach ($this->regras as $item) {
            if ($item === $regra) {
                return true;
            }
        }
        return false;
    }
}
