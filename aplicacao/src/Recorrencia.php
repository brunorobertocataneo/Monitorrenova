<?php
declare(strict_types=1);

interface CalculaPrazo
{
    public function avancar(DateTimeImmutable $data): DateTimeImmutable;
}

abstract class Recorrencia implements CalculaPrazo
{
    public function proxima(DateTimeImmutable $data, DateTimeImmutable $hoje): DateTimeImmutable
    {
        do {
            $data = $this->avancar($data);
        } while ($data <= $hoje);
        return $data;
    }
}

final class RecorrenciaDias extends Recorrencia
{
    public function __construct(private readonly int $dias)
    {
        if ($dias <= 0) throw new InvalidArgumentException('Intervalo invalido');
    }

    public function avancar(DateTimeImmutable $data): DateTimeImmutable
    {
        return $data->modify('+'.$this->dias.' days');
    }
}

final class RecorrenciaMensal extends Recorrencia
{
    public function avancar(DateTimeImmutable $data): DateTimeImmutable
    {
        $dia = (int)$data->format('j');
        $mes = $data->modify('first day of next month');
        return $mes->setDate((int)$mes->format('Y'), (int)$mes->format('m'), min($dia, (int)$mes->format('t')));
    }
}

final class AgendaRecorrencias
{
    // Agregacao: as regras sao recebidas prontas e podem ser reutilizadas.
    public function __construct(private readonly array $regras) {}

    public function calcular(string $tipo, string $prazo, DateTimeImmutable $hoje): string
    {
        $regra = $this->regras[$tipo];
        $data = new DateTimeImmutable($prazo, $hoje->getTimezone());
        return $regra->proxima($data, $hoje)->format('Y-m-d');
    }
}
