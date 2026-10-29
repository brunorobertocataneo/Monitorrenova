<?php
declare(strict_types=1);

/**
 * Controller de Integrações (POO/MVC).
 * Diagnósticos são leituras locais já registradas. Não chama
 * APIs de disparo, não executa n8n e não mostra segredos.
 */
final class IntegracoesController
{
    public function __construct(private readonly PDO $db) {}

    public function index():array
    {
        $wa=WhatsAppStatus::lastCheck();
        $dbOK=true;
        $stats=[];
        $historicoResumo=null;
        $ultimaAuditoriaN8n=null;
        try{
            // Histórico manual de outubro continua identificado como legado,
            // distinto da competência ativa sincronizada pelo Google Sheets.
            $mesLegado='2026-10';
            $q=$this->db->prepare("SELECT COUNT(*) total,
                SUM(CASE WHEN status_atual IS NULL OR TRIM(status_atual)='' THEN 1 ELSE 0 END) sem_status,
                SUM(CASE WHEN produto IS NULL OR TRIM(produto)='' THEN 1 ELSE 0 END) sem_produto,
                SUM(CASE WHEN renovado=1 THEN 1 ELSE 0 END) marcados_renovados,
                SUM(CASE WHEN cobrado=1 THEN 1 ELSE 0 END) marcados_cobrados,
                SUM(CASE WHEN nao_vai_renovar=1 THEN 1 ELSE 0 END) nao_renovam
                FROM seguro_mensal WHERE competencia=?");
            $q->execute([$mesLegado]);
            $stats=$q->fetch()?:[];
            $historicoResumo=(new N8nHistoricoRepository($this->db))->resumo();

            $q=$this->db->prepare("SELECT e.id,e.status,e.iniciada_em,
                e.total_processados,e.total_elegiveis,e.total_pendentes,
                e.total_enviados FROM execucao e
                JOIN automacao a ON a.id=e.automacao_id
                WHERE a.referencia_externa=? AND e.identificador_externo LIKE 'N8N-%'
                ORDER BY e.id DESC LIMIT 1");
            $q->execute(['brunoEvolutionPreparo20261019']);
            $ultimaAuditoriaN8n=$q->fetch(PDO::FETCH_ASSOC)?:null;
        }catch(Throwable $e){
            error_log('Integracoes controller: '.$e->getMessage());
            $dbOK=false;
            $stats=[];
            $historicoResumo=null;
        }

        $stateNames=[
            'open'=>'Conectado no último teste',
            'close'=>'Desconectado no último teste',
            'connecting'=>'Em conexão no último teste',
            'desconhecido'=>'Não verificado'
        ];
        $title=$stateNames[$wa['state']??'desconhecido']??$stateNames['desconhecido'];

        $ultimoEstadoAuditoria=null;
        $statusPath='/var/www/src/n8n-audit-status.json';
        if(is_file($statusPath) && is_readable($statusPath)){
            $decoded=json_decode((string)file_get_contents($statusPath),true);
            if(is_array($decoded) &&
                ($decoded['workflow_id']??null)==='brunoEvolutionPreparo20261019'){
                $ultimoEstadoAuditoria=$decoded;
            }
        }
        $secret=getenv('INGEST_TOKEN');
        $ingestReady=is_string($secret)&&strlen($secret)>=32;
        unset($secret);

        return compact('wa','dbOK','stats','historicoResumo','ultimaAuditoriaN8n',
            'title','ultimoEstadoAuditoria','ingestReady');
    }
}
