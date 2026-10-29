<?php
declare(strict_types=1);

/**
 * Fonte única do resumo WhatsApp: somente a competência APROVADA no MySQL.
 *
 * O serviço é read-only: não registra cobrança, não envia mensagens,
 * não altera o estado dos seguros e não consulta o Google Sheets.
 * A página pública que usa esta classe exige a credencial privada n8n.
 */
final class ResumoAprovadoRepository
{
    /** Sem leitura recente, o resumo não é autorizado (fail closed). */
    public const MAX_IDADE_SEGUNDOS=900;

    public function __construct(private readonly PDO $db){}

    /**
     * O formato de valores mantém a compatibilidade com a transformação
     * já validada no workflow n8n. A fonte é identificada explicitamente;
     * não simulamos as outras duas abas que deixaram de ser necessárias.
     *
     * @return array<string,mixed>
     */
    public function obter():array
    {
        $q=$this->db->query('SELECT spreadsheet_id,competencia_ativa,total_registros,
            UNIX_TIMESTAMP(ultima_sincronizacao) AS ultima_leitura
            FROM sheet_sync_estado WHERE id=1');
        $s=$q->fetch();
        $sourceId=getenv('GOOGLE_SHEETS_ID');
        if(!is_string($sourceId)||
           !preg_match('/^[A-Za-z0-9_-]{30,120}$/D',$sourceId)||
           !$s||!hash_equals($sourceId,(string)($s['spreadsheet_id']??''))){
            throw new DomainException('Origem Google Sheets nao aprovada');
        }
        if(!$s || !is_string($s['competencia_ativa']) ||
           !preg_match('/^20[0-9]{2}-(0[1-9]|1[0-2])$/D',$s['competencia_ativa'])){
            throw new DomainException('Nenhuma competência aprovada');
        }

        $agora=time();
        $ultimo=(int)$s['ultima_leitura'];
        if($ultimo<=0 || $ultimo>$agora+60 ||
           ($agora-$ultimo)>self::MAX_IDADE_SEGUNDOS){
            throw new DomainException('Espelho do Google Sheets está desatualizado');
        }

        // O mês atual, não apenas o último mês importado, deve estar
        // aprovado antes de permitir um resumo de vencimentos.
        $competenciaAtual=(new DateTimeImmutable('now',
            new DateTimeZone('America/Sao_Paulo')))->format('Y-m');
        if($s['competencia_ativa']!==$competenciaAtual){
            throw new DomainException('A competência do mês atual ainda não foi aprovada');
        }

        $q=$this->db->prepare('SELECT segurado,vendedor,vencimento,
            renovado,cobrado,nao_vai_renovar,produto,status_atual
            FROM sheet_sync_registro_mensal
            WHERE competencia=:competencia
            ORDER BY linha_origem');
        $q->execute(['competencia'=>$s['competencia_ativa']]);
        $dados=$q->fetchAll();

        $total=(int)$s['total_registros'];
        if($total<1 || $total>998 || count($dados)!==$total){
            throw new DomainException('Arquivo aprovado incompleto ou divergente');
        }
        $headers=['SEGURADO','VENDEDOR','DATA','RENOVADO','COBRADO',
            'NÃO VAI RENOVAR','PRODUTO','STATUS_APOLICE'];
        $values=[$headers];
        foreach($dados as $r){
            if(substr((string)$r['vencimento'],0,7)!==$s['competencia_ativa']){
                throw new DomainException('Vencimento fora da competência aprovada');
            }
            // A regra existente do n8n entende as marcações SIM/NÃO.
            // Todos os textos são tratados como dados e nunca como código.
            $values[]=[
                (string)$r['segurado'],
                (string)$r['vendedor'],
                (string)$r['vencimento'],
                (int)$r['renovado']===1?'SIM':'NÃO',
                (int)$r['cobrado']===1?'SIM':'NÃO',
                (int)$r['nao_vai_renovar']===1?'SIM':'NÃO',
                (string)($r['produto']??''),
                (string)($r['status_atual']??'')
            ];
        }

        return [
            'source'=>'MONITOR_RENOVA_MYSQL_APROVADO_V1',
            'spreadsheetId'=>$sourceId,
            'competenciaAtiva'=>$s['competencia_ativa'],
            'totalRegistros'=>$total,
            'atualizadoEm'=>(new DateTimeImmutable('@'.$ultimo))
                ->format('Y-m-d\TH:i:s\Z'),
            'valueRanges'=>[[
                'range'=>'_BASE!A1:H'.(count($values)),
                'values'=>$values
            ]]
        ];
    }
}
