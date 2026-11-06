<?php
declare(strict_types=1);

/**
 * Marca a situação operacional sem modificar os registros recebidos do Sheets.
 * Toda alteração exige sessão, CSRF (na rota), versão esperada e é auditada.
 */
final class SeguroStatusService {
    public const SITUACOES=['pendente'=>'PENDENTE','renovado'=>'RENOVADO','nao_renovar'=>'NAO_RENOVA'];
    public function __construct(private readonly PDO $db) {}

    public function atualizar(mixed $linhaEntrada,mixed $versaoEntrada,mixed $situacaoEntrada,mixed $vendedorEntrada,int $usuarioId):void {
        if(!is_string($linhaEntrada)||!preg_match('/^[1-9][0-9]{0,8}$/D',$linhaEntrada))
            throw new InvalidArgumentException('Seguro inválido.');
        if(!is_string($versaoEntrada)||!preg_match('/^(0|[1-9][0-9]{0,8})$/D',$versaoEntrada))
            throw new InvalidArgumentException('Versão inválida. Atualize a página.');
        if(!is_string($situacaoEntrada)||!isset(self::SITUACOES[$situacaoEntrada]))
            throw new InvalidArgumentException('Selecione uma situação válida.');
        if(!is_string($vendedorEntrada)||trim($vendedorEntrada)===''||mb_strlen($vendedorEntrada)>180)
            throw new InvalidArgumentException('Vendedor inválido.');
        if($usuarioId<=0)throw new RuntimeException('Sessão inválida.');

        $linha=(int)$linhaEntrada;
        $versaoEsperada=(int)$versaoEntrada;
        $vendedor=trim($vendedorEntrada);
        $nova=self::SITUACOES[$situacaoEntrada];
        $renovado=$situacaoEntrada==='renovado'?1:0;
        $naoRenova=$situacaoEntrada==='nao_renovar'?1:0;
        $this->db->beginTransaction();
        try {
            // Bloqueia a competência durante a gravação para não conflitar com a sincronização.
            $estado=$this->db->query('SELECT competencia_ativa,total_registros FROM sheet_sync_estado WHERE id=1 FOR UPDATE')->fetch(PDO::FETCH_ASSOC);
            $sincronizado=$estado&&((int)$estado['total_registros']>0);
            if($sincronizado) {
                $competencia=(string)$estado['competencia_ativa'];
                $q=$this->db->prepare('SELECT segurado,vendedor,vencimento,renovado,nao_vai_renovar,status_atual
                    FROM sheet_sync_registro_mensal WHERE competencia=? AND linha_origem=? FOR UPDATE');
                $q->execute([$competencia,$linha]);
            } else {
                $q=$this->db->prepare('SELECT segurado,vendedor,vencimento,renovado,nao_vai_renovar,status_atual
                    FROM seguro_snapshot WHERE linha_origem=? FOR UPDATE');
                $q->execute([$linha]);
            }
            $seguro=$q->fetch(PDO::FETCH_ASSOC);
            if(!$seguro||$seguro['vendedor']!==$vendedor)
                throw new InvalidArgumentException('Seguro não encontrado para este vendedor.');
            if(!$sincronizado)$competencia=substr((string)$seguro['vencimento'],0,7);
            if(!preg_match('/^[0-9]{4}-[0-9]{2}$/D',$competencia))
                throw new RuntimeException('Competência inválida.');

            $q=$this->db->prepare('SELECT versao,status_atual,renovado,nao_vai_renovar
                FROM seguro_status_manual WHERE competencia=? AND linha_origem=? FOR UPDATE');
            $q->execute([$competencia,$linha]);
            $anterior=$q->fetch(PDO::FETCH_ASSOC);
            $versaoAtual=$anterior?(int)$anterior['versao']:0;
            if($versaoAtual!==$versaoEsperada)
                throw new RuntimeException('O seguro foi alterado em outra sessão. Atualize a página antes de salvar.');
            $statusAnterior=$anterior
                ?(string)$anterior['status_atual']
                :((int)$seguro['renovado']===1?'RENOVADO':((int)$seguro['nao_vai_renovar']===1?'NAO_RENOVA':(string)($seguro['status_atual']?:'PENDENTE')));
            if($statusAnterior===$nova){
                $this->db->commit();
                return;
            }
            if($anterior){
                $q=$this->db->prepare('UPDATE seguro_status_manual
                    SET renovado=?,nao_vai_renovar=?,status_atual=?,versao=versao+1,atualizado_por=?,atualizado_em=CURRENT_TIMESTAMP
                    WHERE competencia=? AND linha_origem=? AND versao=?');
                $q->execute([$renovado,$naoRenova,$nova,$usuarioId,$competencia,$linha,$versaoEsperada]);
                if($q->rowCount()!==1)throw new RuntimeException('Conflito de atualização.');
            } else {
                $q=$this->db->prepare('INSERT INTO seguro_status_manual
                    (competencia,linha_origem,renovado,nao_vai_renovar,status_atual,atualizado_por)
                    VALUES(?,?,?,?,?,?)');
                $q->execute([$competencia,$linha,$renovado,$naoRenova,$nova,$usuarioId]);
            }
            $q=$this->db->prepare('INSERT INTO seguro_status_manual_historico
                (competencia,linha_origem,situacao_anterior,situacao_nova,usuario_id)
                VALUES(?,?,?,?,?)');
            $q->execute([$competencia,$linha,$statusAnterior,$nova,$usuarioId]);
            $this->db->commit();
        } catch(Throwable $e) {
            if($this->db->inTransaction())$this->db->rollBack();
            throw $e;
        }
    }
}
