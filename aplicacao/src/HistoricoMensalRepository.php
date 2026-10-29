<?php
declare(strict_types=1);
/** Read-only; chooses table names solely from fixed whitelist. */
final class HistoricoMensalRepository {
  private const TABLES=['planilha'=>'sheet_sync_registro_mensal','manual'=>'seguro_mensal'];
  public function __construct(private readonly PDO $db){}
  private static function table(string $source):string {
    return self::TABLES[$source]??throw new InvalidArgumentException('Fonte inválida');
  }
  public function months(string $source):array {
    $t=self::table($source);
    return $this->db->query("SELECT competencia,COUNT(*) total FROM $t GROUP BY competencia ORDER BY competencia DESC")->fetchAll();
  }
  public function vendors(string $source,string $month):array {
    $t=self::table($source);
    $s=$this->db->prepare("SELECT DISTINCT vendedor FROM $t WHERE competencia=? ORDER BY vendedor");
    $s->execute([$month]);return $s->fetchAll(PDO::FETCH_COLUMN);
  }
  public function details(string $source,?string $month,string $vendor):array {
    $t=self::table($source);
    $stats=['total'=>0,'produtos'=>0,'sem_status'=>0,'renovados'=>0,'cobrados'=>0];
    $byVendor=[];$byProduct=[];$rows=[];
    if($month!==null){
      $where='competencia=:mes';$args=['mes'=>$month];
      if($vendor!==''){$where.=' AND vendedor=:vendedor';$args['vendedor']=$vendor;}
      $q=$this->db->prepare("SELECT COUNT(*) total,COALESCE(SUM(CASE WHEN produto IS NOT NULL AND produto<>'' THEN 1 ELSE 0 END),0) produtos,COALESCE(SUM(CASE WHEN status_atual IS NULL OR status_atual='' THEN 1 ELSE 0 END),0) sem_status,COALESCE(SUM(renovado),0) renovados,COALESCE(SUM(cobrado),0) cobrados FROM $t WHERE $where");
      $q->execute($args);$stats=$q->fetch()?:$stats;
      $q=$this->db->prepare("SELECT vendedor,COUNT(*) total FROM $t WHERE $where GROUP BY vendedor ORDER BY total DESC,vendedor ASC");
      $q->execute($args);$byVendor=$q->fetchAll();
      $q=$this->db->prepare("SELECT COALESCE(NULLIF(produto,''),'Não informado') produto,COUNT(*) total FROM $t WHERE $where GROUP BY COALESCE(NULLIF(produto,''),'Não informado') ORDER BY total DESC");
      $q->execute($args);$byProduct=$q->fetchAll();
      $q=$this->db->prepare("SELECT segurado,vendedor,vencimento,produto,status_atual,renovado,cobrado FROM $t WHERE $where ORDER BY vencimento ASC,linha_origem ASC LIMIT 30");
      $q->execute($args);$rows=$q->fetchAll();
    }
    $lots=$this->db->query('SELECT competencia,registros,criado_em FROM lote_importacao ORDER BY id DESC LIMIT 10')->fetchAll();
    return compact('stats','byVendor','byProduct','rows','lots');
  }
}
