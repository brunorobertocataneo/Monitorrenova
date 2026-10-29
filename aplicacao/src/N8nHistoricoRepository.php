<?php
declare(strict_types=1);

/**
 * Repository POO que reúne:
 * 1) metadados do histórico do n8n, sem payloads e sem dados pessoais;
 * 2) confirmações do MySQL, registradas NA MESMA TRANSAÇÃO da sincronização.
 *
 * Um workflow pode estar "sem encerramento" no n8n e, ao mesmo tempo,
 * possuir uma confirmação independente de atualização no MySQL.
 */
final class N8nHistoricoRepository
{
    public const WORKFLOW_NOMES=[
        'brunoDiarioResumoSeguro20261020'=>'Resumo WhatsApp — 9h',
        'brunoSheetsSyncFiveMin20261021'=>'Sincronização da planilha — 5 min'
    ];
    private const SYNC_WORKFLOW='brunoSheetsSyncFiveMin20261021';

    public function __construct(private readonly PDO $db) {}

    /**
     * Retorna métricas de fontes separadas, sem contar a mesma execução
     * duas vezes quando ela aparece em ambos os históricos.
     */
    public function resumo():array
    {
        $historico=(int)$this->db->query(
            'SELECT COUNT(*) FROM n8n_execucao_resumo'
        )->fetchColumn();
        $semN8n=(int)$this->db->query(
            'SELECT COUNT(*) FROM sheet_sync_execucao s
             LEFT JOIN n8n_execucao_resumo n
               ON n.workflow_id=s.workflow_id AND n.execution_id=s.execution_id
             WHERE n.execution_id IS NULL'
        )->fetchColumn();
        $confirmadas=(int)$this->db->query(
            'SELECT COUNT(*) FROM sheet_sync_execucao'
        )->fetchColumn();
        $falhas=(int)$this->db->query(
            "SELECT COUNT(*) FROM n8n_execucao_resumo WHERE situacao='error'"
        )->fetchColumn();
        $aceitos=(int)$this->db->query(
            "SELECT COUNT(*) FROM n8n_execucao_resumo WHERE resultado='ACEITO_API'"
        )->fetchColumn();

        // Coleta de metadados e confirmação do banco são processos distintos.
        // Por isso, a idade do histórico continua ligada ao coletor Windows.
        $ultimo=$this->db->query(
            'SELECT UNIX_TIMESTAMP(MAX(consultado_em)) FROM n8n_execucao_resumo'
        )->fetchColumn();
        $instante=$ultimo===false||$ultimo===null?null:(int)$ultimo;
        return [
            'total'=>$historico+$semN8n,
            'falhas'=>$falhas,
            'aceitosApi'=>$aceitos,
            'mysqlConfirmadas'=>$confirmadas,
            'coletadoEm'=>$instante===null?null:self::horario($instante),
            'coletaRecente'=>$instante!==null &&
                $instante<=time()+60 && time()-$instante<=1200
        ];
    }

    /**
     * Traz as execuções n8n e acrescenta confirmações que o n8n não
     * persistiu (por configuração de privacidade ou limpeza de histórico).
     * Nunca modifica nem corrige o estado original do n8n.
     */
    public function ultimas(int $limite=40,?string $workflow=null):array
    {
        $limite=max(1,min($limite,100));
        if($workflow!==null&&!isset(self::WORKFLOW_NOMES[$workflow])){
            throw new InvalidArgumentException('Workflow nao reconhecido');
        }

        $sql='SELECT workflow_id,execution_id,situacao,origem,resultado,
            iniciado_em,terminado_em,consultado_em
            FROM n8n_execucao_resumo';
        $params=[];
        if($workflow!==null){
            $sql.=' WHERE workflow_id=:workflow';
            $params['workflow']=$workflow;
        }
        $sql.=' ORDER BY iniciado_em DESC,execution_id DESC LIMIT :limite';
        $q=$this->db->prepare($sql);
        if($workflow!==null)$q->bindValue(':workflow',$workflow,PDO::PARAM_STR);
        $q->bindValue(':limite',$limite,PDO::PARAM_INT);
        $q->execute();
        $rows=$q->fetchAll();

        // Cada confirmação do MySQL contém ID único do workflow + execução.
        // Esses identificadores permitem provar o commit exato sem depender
        // de proximidade de horários ou adivinhação.
        $map=[];
        foreach($rows as $r){
            $r['mysqlConfirmadoEm']=null;
            $r['mysqlRegistros']=null;
            $r['origemHistorico']='n8n';
            $key=$r['workflow_id'].'|'.$r['execution_id'];
            $map[$key]=$r;
        }
        if($workflow===null||$workflow===self::SYNC_WORKFLOW){
            $q=$this->db->query(
                'SELECT workflow_id,execution_id,total_registros,
                    UNIX_TIMESTAMP(confirmado_em) AS instante
                 FROM sheet_sync_execucao
                 ORDER BY confirmado_em DESC LIMIT 100'
            );
            foreach($q->fetchAll() as $c){
                $id=(int)$c['execution_id'];
                $key=$c['workflow_id'].'|'.$id;
                $horario=self::horario((int)$c['instante']);
                if(isset($map[$key])){
                    // O status n8n é preservado mesmo se estiver "running".
                    $map[$key]['resultado']='MYSQL_CONFIRMADO';
                    $map[$key]['mysqlConfirmadoEm']=$horario;
                    $map[$key]['mysqlRegistros']=(int)$c['total_registros'];
                    continue;
                }
                // Algumas execuções são removidas do banco do n8n. O commit
                // ainda é verificável diretamente no MySQL, sem inventar o
                // resultado do workflow ou um suposto encerramento n8n.
                $map[$key]=[
                    'workflow_id'=>$c['workflow_id'],
                    'execution_id'=>$id,
                    'situacao'=>'unknown',
                    'origem'=>'other',
                    'resultado'=>'MYSQL_CONFIRMADO',
                    'iniciado_em'=>$horario,
                    'terminado_em'=>null,
                    'consultado_em'=>$horario,
                    'mysqlConfirmadoEm'=>$horario,
                    'mysqlRegistros'=>(int)$c['total_registros'],
                    'origemHistorico'=>'mysql'
                ];
            }
        }

        $items=array_values($map);
        usort($items,static function(array $a,array $b):int {
            $date=strcmp((string)$b['iniciado_em'],(string)$a['iniciado_em']);
            return $date!==0?$date:((int)$b['execution_id']<=>(int)$a['execution_id']);
        });
        return array_slice($items,0,$limite);
    }

    private static function horario(int $unix):string
    {
        return (new DateTimeImmutable('@'.$unix))
            ->setTimezone(new DateTimeZone('America/Sao_Paulo'))
            ->format('Y-m-d H:i:s');
    }

    public static function nomeWorkflow(string $id):string
    {
        return self::WORKFLOW_NOMES[$id]??'Workflow não identificado';
    }

    public static function situacao(string $state):string
    {
        return match($state){
            'success'=>'Concluído no n8n',
            'error'=>'Falha no n8n',
            'running'=>'Sem encerramento registrado',
            'waiting'=>'Aguardando',
            'canceled'=>'Cancelado',
            default=>'Estado do n8n indisponível'
        };
    }

    public static function resultado(string $result):string
    {
        return match($result){
            'ACEITO_API'=>'Aceito pela Evolution API',
            'DUPLICATA_BLOQUEADA'=>'Reenvio duplicado bloqueado',
            'MYSQL_CONFIRMADO'=>'MySQL atualizado e confirmado',
            'ERRO'=>'Execução falhou',
            'SUCESSO_TECNICO'=>'Execução concluída',
            'EM_ANDAMENTO'=>'Sem encerramento registrado',
            default=>'Resultado não confirmado'
        };
    }
}
