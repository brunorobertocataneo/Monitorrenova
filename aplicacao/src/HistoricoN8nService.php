<?php
declare(strict_types=1);

/**
 * Serviço de auditoria n8n, sem acesso a mensagens ou dados dos segurados.
 * Recebe somente workflow, ID, situação, modo, resultado e datas.
 * Não altera execuções no n8n, apólices ou histórico financeiro.
 */
final class HistoricoN8nService
{
    private const WORKFLOWS=[
        'brunoDiarioResumoSeguro20261020'=>'Resumo WhatsApp 9h — somente responsável',
        'brunoSheetsSyncFiveMin20261021'=>'Google Sheets → MySQL (5 min)'
    ];
    private const STATUS=['success','error','running','waiting','canceled','unknown'];
    private const MODOS=['trigger','cli','manual','other'];
    private const RESULTADOS=['ACEITO_API','DUPLICATA_BLOQUEADA','ERRO',
        'SUCESSO_TECNICO','SEM_CONFIRMACAO','EM_ANDAMENTO'];

    public function __construct(private readonly PDO $db)
    {
    }

    /**
     * Garante que cada execução possua identidade e metadados consistentes.
     * Datas chegam em UTC e são transformadas para o fuso do dashboard.
     */
    private static function validar(array $lote): array
    {
        if(array_keys($lote)!==['registros'] ||
            !is_array($lote['registros']) || !array_is_list($lote['registros']) ||
            count($lote['registros'])<1 || count($lote['registros'])>100){
            throw new InvalidArgumentException('Lote invalido');
        }
        $out=[];$seen=[];
        foreach($lote['registros'] as $r){
            $keys=['workflow_id','execution_id','situacao','origem',
                'resultado','iniciado_em','terminado_em'];
            if(!is_array($r)||array_is_list($r)||array_keys($r)!==$keys){
                throw new InvalidArgumentException('Estrutura de execucao invalida');
            }
            if(!is_string($r['workflow_id'])||
                !array_key_exists($r['workflow_id'],self::WORKFLOWS)||
                !is_int($r['execution_id'])||$r['execution_id']<1 ||
                !in_array($r['situacao'],self::STATUS,true) ||
                !in_array($r['origem'],self::MODOS,true) ||
                !in_array($r['resultado'],self::RESULTADOS,true)){
                throw new InvalidArgumentException('Metadados nao autorizados');
            }
            $key=$r['workflow_id'].'|'.$r['execution_id'];
            if(isset($seen[$key])) throw new InvalidArgumentException('Duplicata no lote');
            $seen[$key]=true;

            if($r['situacao']==='error'&&$r['resultado']!=='ERRO' ||
               $r['situacao']==='running'&&$r['resultado']!=='EM_ANDAMENTO' ||
               $r['resultado']==='ACEITO_API'&&
                 ($r['situacao']!=='success'||$r['workflow_id']!=='brunoDiarioResumoSeguro20261020') ||
               $r['resultado']==='DUPLICATA_BLOQUEADA'&&
                 ($r['situacao']!=='success'||$r['workflow_id']!=='brunoDiarioResumoSeguro20261020')){
                throw new InvalidArgumentException('Resultado incompativel com estado');
            }
            $iniciado=self::data($r['iniciado_em'],false);
            $terminado=self::data($r['terminado_em'],true);
            if($terminado!==null && $terminado<$iniciado){
                throw new InvalidArgumentException('Fim anterior ao inicio');
            }
            $out[]=[$r['workflow_id'],$r['execution_id'],$r['situacao'],
                $r['origem'],$r['resultado'],$iniciado,$terminado];
        }
        return $out;
    }

    private static function data(mixed $value,bool $null):?string
    {
        if($null && $value===null) return null;
        if(!is_string($value)||!preg_match('/^20\\d{2}-\\d{2}-\\d{2}T\\d{2}:\\d{2}:\\d{2}\\.\\d{3}Z$/D',$value)){
            throw new InvalidArgumentException('Data UTC invalida');
        }
        $utc=DateTimeImmutable::createFromFormat('!Y-m-d\\TH:i:s.v\\Z',$value,new DateTimeZone('UTC'));
        if(!$utc||$utc->format('Y-m-d\\TH:i:s.v\\Z')!==$value){
            throw new InvalidArgumentException('Instante UTC invalido');
        }
        return $utc->setTimezone(new DateTimeZone('America/Sao_Paulo'))
            ->format('Y-m-d H:i:s');
    }

    /**
     * UPSERT idempotente: atualizar uma linha existente é permitido, mas
     * repetir a coleta não cria novas execuções. Transação por lote inteiro.
     */
    public function registrar(array $payload):array
    {
        $rows=self::validar($payload);
        $this->db->beginTransaction();
        try {
            $stmt=$this->db->prepare('INSERT INTO n8n_execucao_resumo
                (workflow_id,execution_id,situacao,origem,resultado,iniciado_em,terminado_em)
                VALUES (?,?,?,?,?,?,?)
                ON DUPLICATE KEY UPDATE
                situacao=VALUES(situacao),origem=VALUES(origem),
                resultado=VALUES(resultado),terminado_em=VALUES(terminado_em)');
            foreach($rows as $row)$stmt->execute($row);
            $this->db->commit();
            return ['ok'=>true,'registros'=>count($rows),'dados_pessoais'=>false];
        }catch(Throwable $e){
            if($this->db->inTransaction())$this->db->rollBack();
            throw $e;
        }
    }
}
