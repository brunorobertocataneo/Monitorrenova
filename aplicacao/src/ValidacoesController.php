<?php
declare(strict_types=1);
/** Classifies manual review currency without inferring eligibility to charge. */
final class ValidacoesController {
  public const LABELS=[
    'todos'=>'Todas as apólices',
    'sem_registro'=>'Sem consulta registrada',
    'alterada'=>'Dados alterados após a consulta',
    'antiga'=>'Consulta desatualizada (mais de 7 dias)',
    'indeterminada'=>'Situação indeterminada',
    'ativa'=>'Ativa — conferida recentemente',
    'cancelada'=>'Cancelada — conferida recentemente',
    'recusada'=>'Recusada — conferida recentemente'
  ];
  public function __construct(private readonly ValidacoesRepository $repo){}
  public function index(array $query,?DateTimeImmutable $agora=null):array {
    $labels=self::LABELS;
    $today=($agora??new DateTimeImmutable('today',new DateTimeZone('America/Sao_Paulo')))->setTimezone(new DateTimeZone('America/Sao_Paulo'));
    $cutoff=$today->modify('-7 days')->format('Y-m-d');
    $months=$this->repo->meses();
    $month=is_string($query['mes']??null)&&in_array($query['mes'],$months,true)?$query['mes']:($months[0]??null);
    $filter=is_string($query['filtro']??null)&&isset($labels[$query['filtro']])?$query['filtro']:'todos';
    $entries=[];$stats=array_fill_keys(array_keys($labels),0);
    if($month!==null){
      foreach($this->repo->registros($month) as $item){
        if($item['validacao_id']===null)$kind='sem_registro';
        elseif(!hash_equals((string)$item['fingerprint_origem'],ValidacaoApolice::origemFingerprint($item)))$kind='alterada';
        elseif($item['data_consulta']<$cutoff||$item['data_consulta']>$today->format('Y-m-d'))$kind='antiga';
        else $kind=in_array($item['validacao_situacao'],['ativa','cancelada','recusada'],true)?$item['validacao_situacao']:'indeterminada';
        $item['categoria']=$kind;
        $stats['todos']++;$stats[$kind]++;
        if($filter==='todos'||$filter===$kind)$entries[]=$item;
      }
    }
    $raw=$query['pagina']??1;
    $page=(is_int($raw)||is_string($raw))&&preg_match('/^[1-9][0-9]{0,4}$/D',(string)$raw)?min(10000,(int)$raw):1;
    $total=count($entries);$pages=max(1,(int)ceil($total/30));$page=min($page,$pages);
    $rows=array_slice($entries,($page-1)*30,30);
    $urlParams=['mes'=>$month,'filtro'=>$filter];
    return compact('labels','months','month','filter','stats','rows','page','pages','total','urlParams');
  }
}
