<?php
declare(strict_types=1);
require_once __DIR__.'/Database.php';

/**
 * Receptor de auditorias agregadas do n8n.
 * Nunca registra destinatarios, conteudos de WhatsApp ou seguros individuais.
 */
final class AuditIngestService {
    private const WORKFLOW_ID = 'brunoEvolutionPreparo20261019';

    public function __construct(private readonly PDO $db) {}

    private static function validNumber(array $input, string $key): int {
        $value=$input[$key]??null;
        if(!is_int($value)||$value<0||$value>1000000){
            throw new InvalidArgumentException("Campo $key invalido");
        }
        return $value;
    }

    public static function validate(array $input): array {
        if(($input['modo']??null)!=='auditoria_sem_envios'){
            throw new InvalidArgumentException('Somente auditoria sem envios e permitida');
        }
        if(($input['workflow_id']??null)!==self::WORKFLOW_ID){
            throw new InvalidArgumentException('Workflow nao autorizado');
        }
        $id=$input['execution_id']??null;
        if(!is_string($id)||!preg_match('/^[A-Za-z0-9_.:-]{1,120}$/D',$id)){
            throw new InvalidArgumentException('execution_id invalido');
        }
        $status=$input['status']??null;
        if(!in_array($status,['sucesso','falha'],true)){
            throw new InvalidArgumentException('Status de auditoria invalido');
        }
        $processed=self::validNumber($input,'total_processados');
        $eligible=self::validNumber($input,'total_elegiveis');
        $pending=self::validNumber($input,'total_pendentes');
        $sent=self::validNumber($input,'total_enviados');
        if($sent!==0){
            throw new InvalidArgumentException('Envio nao permitido pela auditoria');
        }
        if($eligible>$processed||$pending>$processed){
            throw new InvalidArgumentException('Contagem maior que registros processados');
        }
        if($status==='falha'&&($eligible!==0||$pending!==0)){
            throw new InvalidArgumentException('Auditoria com falha nao deve declarar elegiveis');
        }
        $code=$input['codigo_erro']??null;
        if($code!==null&&(!is_string($code)||!preg_match('/^[A-Z0-9_-]{1,50}$/D',$code))){
            throw new InvalidArgumentException('codigo_erro invalido');
        }
        if($status==='sucesso'&&$code!==null){
            throw new InvalidArgumentException('Auditoria bem-sucedida nao pode conter codigo de erro');
        }
        $payload=[
            'workflow_id'=>self::WORKFLOW_ID,'execution_id'=>$id,'status'=>$status,
            'total_processados'=>$processed,'total_elegiveis'=>$eligible,
            'total_pendentes'=>$pending,'total_enviados'=>0,'codigo_erro'=>$code
        ];
        return $payload;
    }

    public function record(array $payload): array {
        $p=self::validate($payload);
        $this->db->beginTransaction();
        try {
            $select=$this->db->prepare('SELECT id FROM automacao WHERE referencia_externa=? AND ativo=0 LIMIT 1');
            $select->execute([self::WORKFLOW_ID]);
            $automationId=$select->fetchColumn();
            if(!$automationId){
                throw new RuntimeException('Automacao de auditoria nao cadastrada');
            }
            $query=$this->db->prepare('SELECT id,status,total_processados,total_elegiveis,total_pendentes,total_enviados,mensagem_erro FROM execucao WHERE automacao_id=? AND identificador_externo=? FOR UPDATE');
            $query->execute([(int)$automationId,$p['execution_id']]);
            $existing=$query->fetch(PDO::FETCH_ASSOC);
            if($existing){
                if(
                    $existing['status']!==$p['status']
                    ||(int)$existing['total_processados']!==$p['total_processados']
                    ||(int)$existing['total_elegiveis']!==$p['total_elegiveis']
                    ||(int)$existing['total_pendentes']!==$p['total_pendentes']
                    ||(int)$existing['total_enviados']!==0
                    ||($existing['mensagem_erro']??null)!==$p['codigo_erro']
                ){
                    throw new DomainException('Id de execucao repetido com dados divergentes');
                }
                $executionId=(int)$existing['id'];
                $new=false;
            } else {
                $insert=$this->db->prepare('INSERT INTO execucao (automacao_id,identificador_externo,status,terminada_em,total_processados,total_elegiveis,total_pendentes,total_enviados,mensagem_erro) VALUES (?,?,?,NOW(),?,?,?,?,?)');
                $insert->execute([(int)$automationId,$p['execution_id'],$p['status'],$p['total_processados'],$p['total_elegiveis'],$p['total_pendentes'],0,$p['codigo_erro']]);
                $executionId=(int)$this->db->lastInsertId();
                $new=true;
                $event=$this->db->prepare('INSERT INTO evento_execucao (execucao_id,nivel,tipo,descricao) VALUES (?,?,?,?)');
                $description=$p['status']==='sucesso'
                    ? sprintf('Auditoria de leitura concluida: %d registros; %d candidatos preliminares; %d pendentes. Nenhum envio.', $p['total_processados'],$p['total_elegiveis'],$p['total_pendentes'])
                    : 'Auditoria de leitura falhou. Sem envios. Codigo: '.($p['codigo_erro']??'INDEFINIDO');
                $event->execute([$executionId,$p['status']==='sucesso'?'info':'aviso','auditoria_sem_envios',$description]);
            }
            $this->db->commit();
            return ['id'=>$executionId,'novo'=>$new,'status'=>$p['status']];
        }catch(Throwable $e){
            if($this->db->inTransaction())$this->db->rollBack();
            throw $e;
        }
    }
}
