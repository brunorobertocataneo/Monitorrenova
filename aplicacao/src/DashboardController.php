<?php
declare(strict_types=1);
require_once __DIR__.'/SeguroRepository.php';
require_once __DIR__.'/VencimentoRepository.php';
require_once __DIR__.'/ListaVencimentos.php';

final class DashboardController
{
    public function __construct(
        private readonly SeguroRepository $seguros,
        private readonly VencimentoRepository $vencimentos
    ) {}

    public function index(): array
    {
        $hoje = (new DateTimeImmutable('today', new DateTimeZone('America/Sao_Paulo')))->format('Y-m-d');
        $lista = new ListaVencimentos();
        foreach ($this->vencimentos->vencimentosDeHoje($hoje) as $vendedor => $seguros) {
            foreach ($seguros as $seguro) $lista->adicionar($vendedor, $seguro);
        }
        return [
            'dataHoje' => $hoje,
            'vencimentosHoje' => $lista->porVendedor(),
            'resumoDia' => $this->seguros->resumoDoDia($hoje),
            'estadoSheet' => $this->seguros->estadoSincronizacao()
        ];
    }
}
