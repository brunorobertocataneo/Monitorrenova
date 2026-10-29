<?php
declare(strict_types=1);
/** Filtros GET e paginação sem SQL ou HTML. */
final class AcompanhamentoController {
  public const STATUSES=[
    'sem_revisao'=>'Sem revisão','pendente'=>'Pendente','em_analise'=>'Em análise',
    'conferido'=>'Conferido','divergencia'=>'Com divergência'
  ];
  public function __construct(private readonly AcompanhamentoRepository $repo){}
  public function index(array $query):array {
    $months=$this->repo->meses();
    $mes=is_string($query['mes']??null)&&in_array($query['mes'],$months,true)?$query['mes']:($months[0]??null);
    $statuses=self::STATUSES;
    $filter=is_string($query['situacao']??null)&&isset($statuses[$query['situacao']])?$query['situacao']:'';
    $raw=$query['pagina']??1;
    $page=(is_int($raw)||is_string($raw))&&preg_match('/^[1-9][0-9]{0,4}$/D',(string)$raw)?min(10000,(int)$raw):1;
    $result=$this->repo->consultar($mes,$filter,$page);
    return [
      'months'=>$months,'mes'=>$mes,'statuses'=>$statuses,'filter'=>$filter,
      'counts'=>$result['counts'],'rows'=>$result['rows'],'total'=>$result['total'],
      'page'=>$result['page'],'pages'=>$result['pages'],
      'qs'=>['mes'=>$mes,'situacao'=>$filter]
    ];
  }
}
