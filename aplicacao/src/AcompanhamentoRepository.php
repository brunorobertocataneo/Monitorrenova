<?php
declare(strict_types=1);
/** Somente leituras das revisões internas em seguro_mensal. */
final class AcompanhamentoRepository {
  public function __construct(private readonly PDO $db){}
  public function meses():array {
    return $this->db->query('SELECT DISTINCT competencia FROM seguro_mensal ORDER BY competencia DESC')->fetchAll(PDO::FETCH_COLUMN);
  }
  public function consultar(?string $mes,string $filter,int $page):array {
    $counts=array_fill_keys(['sem_revisao','pendente','em_analise','conferido','divergencia'],0);
    $rows=[];$total=0;$pages=1;
    if($mes!==null){
      $q=$this->db->prepare("SELECT COALESCE(r.situacao,'sem_revisao') situacao,COUNT(*) total FROM seguro_mensal s LEFT JOIN revisao_seguro r ON r.seguro_mensal_id=s.id WHERE s.competencia=? GROUP BY COALESCE(r.situacao,'sem_revisao')");
      $q->execute([$mes]);
      foreach($q->fetchAll() as $r)if(isset($counts[$r['situacao']]))$counts[$r['situacao']]=(int)$r['total'];
      $conditions='s.competencia=:mes';$params=['mes'=>$mes];
      if($filter==='sem_revisao')$conditions.=' AND r.id IS NULL';
      elseif($filter!==''){$conditions.=' AND r.situacao=:situacao';$params['situacao']=$filter;}
      $q=$this->db->prepare("SELECT COUNT(*) FROM seguro_mensal s LEFT JOIN revisao_seguro r ON r.seguro_mensal_id=s.id WHERE $conditions");
      $q->execute($params);$total=(int)$q->fetchColumn();$pages=max(1,(int)ceil($total/30));$page=min($page,$pages);
      $q=$this->db->prepare("SELECT s.id,s.linha_origem,s.segurado,s.vendedor,s.vencimento,COALESCE(r.situacao,'sem_revisao') situacao,r.atualizado_em FROM seguro_mensal s LEFT JOIN revisao_seguro r ON r.seguro_mensal_id=s.id WHERE $conditions ORDER BY s.vencimento ASC,s.linha_origem ASC LIMIT 30 OFFSET :offset");
      foreach($params as $k=>$v)$q->bindValue(':'.$k,$v,PDO::PARAM_STR);
      $q->bindValue(':offset',($page-1)*30,PDO::PARAM_INT);$q->execute();$rows=$q->fetchAll();
    }
    return compact('counts','rows','total','pages','page');
  }
}
