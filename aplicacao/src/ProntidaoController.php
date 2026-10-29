<?php
declare(strict_types=1);
/** Presentation data only. Uses existing AuditoriaProntidao repository. */
final class ProntidaoController {
  public const LABELS=[
    'registros'=>'Registros da competência','sem_status_atual'=>'Sem status atual confirmado',
    'sem_produto'=>'Sem produto informado','com_status_historico'=>'Com referência histórica (PDF)',
    'marcados_renovados'=>'Marcados renovados na cópia','marcados_cobrados'=>'Marcados cobrados na cópia',
    'marcados_nao_renovam'=>'Marcados que não renovarão','sinais_conflitantes'=>'Marcações contraditórias',
    'liberados_envio'=>'Liberações automáticas','mensagens_enviadas'=>'Mensagens enviadas por esta auditoria'
  ];
  public function __construct(private readonly AuditoriaProntidao $repo){}
  public function index(array $query):array {
    $months=$this->repo->meses();
    $mes=is_string($query['mes']??null)&&in_array($query['mes'],$months,true)?$query['mes']:($months[0]??null);
    $latest=$mes!==null?$this->repo->ultimo($mes):null;
    $history=$mes!==null?$this->repo->historico($mes):[];
    $labels=self::LABELS;
    return compact('months','mes','latest','history','labels');
  }
}
