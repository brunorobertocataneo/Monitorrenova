<?php
declare(strict_types=1);

/**
 * Model POO de competências da planilha Google Sheets.
 * Mantém os arquivos mensais preservados e concentra a aprovação
 * administrativa da troca de mês. Nunca executa DELETE.
 */
final class CompetenciaRepository
{
    public function __construct(private readonly PDO $db) {}

    public function resumo():array
    {
        $estado=$this->db->query('SELECT competencia_ativa,total_registros,
            UNIX_TIMESTAMP(ultima_sincronizacao) AS instante
            FROM sheet_sync_estado WHERE id=1')->fetch();
        if(!$estado)throw new RuntimeException('Sincronizador nao inicializado');
        $arquivo=$this->db->query('SELECT competencia,COUNT(*) AS total,
            MAX(atualizado_em) AS ultima_atualizacao
            FROM sheet_sync_registro_mensal GROUP BY competencia
            ORDER BY competencia DESC')->fetchAll();

        $pending=$this->db->query("SELECT competencia,estado,total_detectado,
                leituras_iguais,hash_observado,primeiro_aviso,ultima_leitura,
                aprovada_em,concluida_em
            FROM sheet_sync_transicao
            ORDER BY competencia DESC")->fetchAll();

        $instante=(int)$estado['instante'];
        return [
            'ativa'=>$estado['competencia_ativa'],
            'registrosAtivos'=>(int)$estado['total_registros'],
            'arquivo'=>$arquivo,
            'transicoes'=>$pending,
            'sincronizadoEm'=>(new DateTimeImmutable('@'.$instante))
                ->setTimezone(new DateTimeZone('America/Sao_Paulo'))
                ->format('d/m/Y H:i:s'),
            'sincronizacaoRecente'=>$instante<=time()+60&&time()-$instante<900
        ];
    }

    /**
     * Marca uma competência como aprovada, mas NÃO troca a competência aqui.
     * Somente o próximo lote validado, com o MESMO HASH aprovado, pode
     * executar a transição atômica no SheetSyncService.
     *
     * Chamado exclusivamente após autenticação de sessão e CSRF na rota.
     */
    public function autorizar(string $competencia):void
    {
        if(!preg_match('/^20\d{2}-(?:0[1-9]|1[0-2])$/D',$competencia)){
            throw new InvalidArgumentException('Competencia invalida');
        }
        $this->db->beginTransaction();
        try{
            $state=$this->db->query('SELECT competencia_ativa
                FROM sheet_sync_estado WHERE id=1 FOR UPDATE')->fetch();
            if(!$state||!is_string($state['competencia_ativa'])){
                throw new DomainException('Mes ativo indisponivel');
            }
            $ativo=$state['competencia_ativa'];
            $data=DateTimeImmutable::createFromFormat('!Y-m',$ativo);
            if(!$data || $data->modify('+1 month')->format('Y-m')!==$competencia){
                throw new DomainException('Somente proxima competencia pode ser ativada');
            }
            $q=$this->db->prepare("SELECT estado,leituras_iguais,hash_observado,
                total_detectado FROM sheet_sync_transicao
                WHERE competencia=? FOR UPDATE");
            $q->execute([$competencia]);
            $pending=$q->fetch();
            if(!$pending||$pending['estado']!=='pendente'||
                (int)$pending['leituras_iguais']<2||
                (int)$pending['total_detectado']<1){
                throw new DomainException('Troca nao pronta ou ja autorizada');
            }
            $q=$this->db->prepare("UPDATE sheet_sync_transicao
                SET estado='autorizada',aprovada_em=CURRENT_TIMESTAMP
                WHERE competencia=? AND estado='pendente'");
            $q->execute([$competencia]);
            if($q->rowCount()!==1)throw new DomainException('Sem mudanca de estado');
            $this->db->commit();
        }catch(Throwable $e){
            if($this->db->inTransaction())$this->db->rollBack();
            throw $e;
        }
    }
}
