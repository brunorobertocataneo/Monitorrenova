<?php
declare(strict_types=1);
/** Consultas read-only das validações MANUAIS de seguro_mensal. */
final class ValidacoesRepository {
  public function __construct(private readonly PDO $db){}
  public function meses():array {
    return $this->db->query('SELECT competencia FROM seguro_mensal GROUP BY competencia ORDER BY competencia DESC')->fetchAll(PDO::FETCH_COLUMN);
  }
  public function registros(string $month):array {
    $q=$this->db->prepare("SELECT s.id,s.competencia,s.linha_origem,s.apolice,s.segurado,s.vendedor,s.vencimento,s.produto,s.status_atual,s.status_pdf,s.renovado,s.cobrado,s.nao_vai_renovar,v.id validacao_id,v.situacao validacao_situacao,v.fonte validacao_fonte,v.data_consulta,v.fingerprint_origem FROM seguro_mensal s LEFT JOIN validacao_apolice v ON v.id=(SELECT MAX(v2.id) FROM validacao_apolice v2 WHERE v2.seguro_mensal_id=s.id) WHERE s.competencia=? ORDER BY s.vencimento ASC,s.linha_origem ASC");
    $q->execute([$month]);return $q->fetchAll(PDO::FETCH_ASSOC);
  }
}
