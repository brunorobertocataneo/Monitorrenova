<?php
declare(strict_types=1);
require_once __DIR__.'/Database.php';
final class ConferenciaRepository {
 public const FILTROS=[
  'todos'=>'1=1',
  'sem_produto'=>"(produto IS NULL OR TRIM(produto)='')",
  'sem_status'=>"(status_atual IS NULL OR TRIM(status_atual)='')",
  'historico_sem_atual'=>"(status_pdf IS NOT NULL AND TRIM(status_pdf)<>'' AND (status_atual IS NULL OR TRIM(status_atual)=''))",
  'sinais_conflitantes'=>'(renovado=1 AND nao_vai_renovar=1)',
  'sem_confirmacao'=>"(renovado=0 AND nao_vai_renovar=0 AND cobrado=0 AND (status_atual IS NULL OR TRIM(status_atual)=''))"
 ];
 public function __construct(private readonly PDO $db){}
 public function meses():array {
  return $this->db->query('SELECT competencia FROM seguro_mensal GROUP BY competencia ORDER BY competencia DESC')->fetchAll(PDO::FETCH_COLUMN);
 }
 public function validarMes(string $mes):bool {return in_array($mes,$this->meses(),true);}
 public function contar(string $mes,string $filtro):int {
  if(!isset(self::FILTROS[$filtro]))throw new InvalidArgumentException('Filtro inválido');
  $q=$this->db->prepare('SELECT COUNT(*) FROM seguro_mensal WHERE competencia=? AND '.self::FILTROS[$filtro]);
  $q->execute([$mes]);return (int)$q->fetchColumn();
 }
 public function consultar(string $mes,string $filtro,int $limite=5000):array {
  if(!isset(self::FILTROS[$filtro]))throw new InvalidArgumentException('Filtro inválido');
  $limite=max(1,min(10000,$limite));
  $q=$this->db->prepare('SELECT linha_origem,segurado,vendedor,vencimento,produto,renovado,cobrado,nao_vai_renovar,status_atual,status_pdf FROM seguro_mensal WHERE competencia=:mes AND '.self::FILTROS[$filtro].' ORDER BY vencimento ASC,linha_origem ASC LIMIT :limite');
  $q->bindValue(':mes',$mes,PDO::PARAM_STR);$q->bindValue(':limite',$limite,PDO::PARAM_INT);
  $q->execute();return $q->fetchAll();
 }
}
