<?php
declare(strict_types=1);
require_once __DIR__.'/Database.php';

/**
 * Registro manual de consultas a fontes atuais de apolices.
 * Dados importados e status historicos nao sao alterados.
 * NUNCA libera envio ou classifica automaticamente como cobravel.
 */
final class ValidacaoApolice {
    public const SITUACOES=[
        'ativa'=>'Ativa na fonte consultada',
        'cancelada'=>'Cancelada',
        'recusada'=>'Recusada',
        'indeterminada'=>'Nao foi possivel confirmar'
    ];
    public const FONTES=[
        'portal_seguradora'=>'Portal da seguradora',
        'sistema_corretora'=>'Sistema da corretora',
        'documento_atual'=>'Documento atual',
    ];
    public function __construct(private readonly PDO $db) {}

    public function preparar(): void {
        $this->db->exec("CREATE TABLE IF NOT EXISTS validacao_apolice (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            seguro_mensal_id BIGINT UNSIGNED NOT NULL,
            situacao ENUM('ativa','cancelada','recusada','indeterminada') NOT NULL,
            fonte ENUM('portal_seguradora','sistema_corretora','documento_atual') NOT NULL,
            data_consulta DATE NOT NULL,
            observacao VARCHAR(400) NOT NULL DEFAULT '',
            fingerprint_origem CHAR(64) NOT NULL,
            registrado_por BIGINT UNSIGNED NOT NULL,
            criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY ix_validacao_seguro (seguro_mensal_id,id),
            KEY ix_validacao_data (data_consulta),
            CONSTRAINT fk_validacao_seguro_restrict FOREIGN KEY (seguro_mensal_id) REFERENCES seguro_mensal(id) ON DELETE RESTRICT,
            CONSTRAINT fk_validacao_usuario FOREIGN KEY (registrado_por) REFERENCES usuario(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $q=$this->db->query("SELECT DELETE_RULE FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='validacao_apolice' AND CONSTRAINT_NAME IN ('fk_validacao_seguro','fk_validacao_seguro_restrict') LIMIT 1");
        $rule=$q->fetchColumn();
        if($rule==='CASCADE'){
            $this->db->exec('ALTER TABLE validacao_apolice DROP FOREIGN KEY fk_validacao_seguro, ADD CONSTRAINT fk_validacao_seguro_restrict FOREIGN KEY (seguro_mensal_id) REFERENCES seguro_mensal(id) ON DELETE RESTRICT');
        }elseif(!in_array($rule,['RESTRICT','NO ACTION'],true)){
            throw new RuntimeException('Regra de integridade inesperada na validação de apólices.');
        }
    }

    public static function origemFingerprint(array $seguro): string {
        $keys=['competencia','linha_origem','apolice','segurado','vendedor','vencimento','produto','status_atual','status_pdf','renovado','cobrado','nao_vai_renovar'];
        $values=[];
        foreach($keys as $key){$values[]=(string)($seguro[$key]??'');}
        return hash('sha256',json_encode($values,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE|JSON_THROW_ON_ERROR));
    }

    public static function validateInputs(array $inputs, DateTimeImmutable $today): array {
        $situacao=$inputs['situacao']??null;
        $fonte=$inputs['fonte']??null;
        $obs=$inputs['observacao']??null;
        $date=$inputs['data_consulta']??null;
        if(!is_string($situacao)||!isset(self::SITUACOES[$situacao]))throw new InvalidArgumentException('Selecione uma situacao valida.');
        if(!is_string($fonte)||!isset(self::FONTES[$fonte]))throw new InvalidArgumentException('Selecione a fonte utilizada.');
        if(!is_string($obs)||mb_strlen($obs)>400)throw new InvalidArgumentException('Observacao invalida (maximo 400 caracteres).');
        $obs=trim($obs);
        if($obs!==''&&preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f]/',$obs))throw new InvalidArgumentException('Caracteres de controle na observacao.');
        if(!is_string($date)||!preg_match('/^\d{4}-\d{2}-\d{2}$/D',$date))throw new InvalidArgumentException('Informe a data da consulta.');
        $dateObj=DateTimeImmutable::createFromFormat('!Y-m-d',$date,new DateTimeZone('America/Sao_Paulo'));
        if(!$dateObj||$dateObj->format('Y-m-d')!==$date)throw new InvalidArgumentException('Data da consulta invalida.');
        if($dateObj>$today||$dateObj<$today->modify('-30 days'))throw new InvalidArgumentException('Consulta deve ter ocorrido nos ultimos 30 dias.');
        if(($inputs['confirmo']??null)!=='1')throw new InvalidArgumentException('Confirme que consultou uma fonte atual da apolice.');
        return ['situacao'=>$situacao,'fonte'=>$fonte,'data_consulta'=>$date,'observacao'=>$obs];
    }

    public function getSeguro(int $id): ?array {
        $q=$this->db->prepare('SELECT id,competencia,linha_origem,apolice,segurado,vendedor,vencimento,produto,status_atual,status_pdf,renovado,cobrado,nao_vai_renovar FROM seguro_mensal WHERE id=?');
        $q->execute([$id]);return $q->fetch(PDO::FETCH_ASSOC)?:null;
    }
    public function ultima(int $id): ?array {
        $q=$this->db->prepare('SELECT v.*,u.login FROM validacao_apolice v JOIN usuario u ON u.id=v.registrado_por WHERE v.seguro_mensal_id=? ORDER BY v.id DESC LIMIT 1');
        $q->execute([$id]);return $q->fetch(PDO::FETCH_ASSOC)?:null;
    }
    public function historico(int $id): array {
        $q=$this->db->prepare('SELECT v.*,u.login FROM validacao_apolice v JOIN usuario u ON u.id=v.registrado_por WHERE v.seguro_mensal_id=? ORDER BY v.id DESC LIMIT 30');
        $q->execute([$id]);return $q->fetchAll(PDO::FETCH_ASSOC);
    }
    public function registrar(int $id,int $user,array $inputs,string $expectedFingerprint,string $expectedLatest,DateTimeImmutable $today): int {
        if($id<1||$user<1)throw new InvalidArgumentException('Identificadores invalidos.');
        $valid=self::validateInputs($inputs,$today);
        if(!preg_match('/^[0-9a-f]{64}$/D',$expectedFingerprint))throw new InvalidArgumentException('Versao da apolice invalida.');
        $openedTx=!$this->db->inTransaction();
        if($openedTx)$this->db->beginTransaction();
        try{
            $q=$this->db->prepare('SELECT id,competencia,linha_origem,apolice,segurado,vendedor,vencimento,produto,status_atual,status_pdf,renovado,cobrado,nao_vai_renovar FROM seguro_mensal WHERE id=? FOR UPDATE');
            $q->execute([$id]);$seguro=$q->fetch(PDO::FETCH_ASSOC);
            if(!$seguro)throw new RuntimeException('Seguro nao encontrado.');
            $fingerprint=self::origemFingerprint($seguro);
            if(!hash_equals($fingerprint,$expectedFingerprint))throw new RuntimeException('Os dados do seguro mudaram; recarregue a pagina antes de confirmar.');
            $q=$this->db->prepare('SELECT id FROM validacao_apolice WHERE seguro_mensal_id=? ORDER BY id DESC LIMIT 1 FOR UPDATE');
            $q->execute([$id]);$latest=$q->fetchColumn();
            $latestToken=$latest===false?'novo':(string)$latest;
            if(!hash_equals($latestToken,$expectedLatest))throw new RuntimeException('Outra validacao foi registrada. Recarregue a pagina.');
            $q=$this->db->prepare('INSERT INTO validacao_apolice (seguro_mensal_id,situacao,fonte,data_consulta,observacao,fingerprint_origem,registrado_por) VALUES (?,?,?,?,?,?,?)');
            $q->execute([$id,$valid['situacao'],$valid['fonte'],$valid['data_consulta'],$valid['observacao'],$fingerprint,$user]);
            $newId=(int)$this->db->lastInsertId();
            if($openedTx)$this->db->commit();
            return $newId;
        }catch(Throwable $e){
            if($openedTx&&$this->db->inTransaction())$this->db->rollBack();
            throw $e;
        }
    }
}
