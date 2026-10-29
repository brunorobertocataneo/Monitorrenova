<?php
declare(strict_types=1);

require_once __DIR__.'/MonitorRepository.php';
require_once __DIR__.'/SeguroRepository.php';
require_once __DIR__.'/N8nHistoricoRepository.php';

/**
 * Controller da página inicial do MonitorRenova (padrão MVC).
 *
 * - Não contém SQL: cada Repository faz suas próprias consultas;
 * - Não produz HTML: a View é responsável pela apresentação;
 * - Centraliza a data de São Paulo e a validação dos filtros antigos;
 * - Nenhuma operação modifica apólices ou dispara mensagens.
 *
 * @phpstan-type DashboardData array<string,mixed>
 */
final class DashboardController
{
    public function __construct(
        private readonly MonitorRepository $monitor,
        private readonly SeguroRepository $seguros,
        private readonly N8nHistoricoRepository $historico
    ) {}

    /**
     * @param array<string,mixed> $filtros Valores brutos da query HTTP.
     * @return array<string,mixed> Somente dados já preparados para a View.
     */
    public function index(array $filtros, ?DateTimeImmutable $agora=null): array
    {
        $tz=new DateTimeZone('America/Sao_Paulo');
        $hoje=($agora??new DateTimeImmutable('now',$tz))
            ->setTimezone($tz)->format('Y-m-d');

        $status=is_string($filtros['status']??null) &&
            in_array($filtros['status'],['iniciada','sucesso','falha','cancelada'],true)
            ? $filtros['status'] : null;

        $automacao=$filtros['automacao']??null;
        $automacaoId=(is_string($automacao)||is_int($automacao)) &&
            preg_match('/^[1-9][0-9]{0,8}$/D',(string)$automacao)
            ? (int)$automacao : null;

        return [
            'dataHoje'=>$hoje,
            'kpi'=>$this->monitor->indicadores(),
            'automacoes'=>$this->monitor->automacoes(),
            'status'=>$status,
            'automacaoId'=>$automacaoId,
            'execucoes'=>$this->monitor->execucoes($status,$automacaoId),
            'eventos'=>$this->monitor->eventos(),
            'resumoDia'=>$this->seguros->resumoDoDia($hoje),
            'estadoSheet'=>$this->seguros->estadoSincronizacao(),
            'n8nResumo'=>$this->historico->resumo(),
            'n8nUltimas'=>$this->historico->ultimas(5)
        ];
    }
}
