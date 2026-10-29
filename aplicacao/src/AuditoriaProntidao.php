<?php
declare(strict_types=1);
require_once __DIR__.'/Database.php';
final class AuditoriaProntidao {
    public function __construct(private PDO $db) {}

    public function preparar(): void {
        $this->db->exec("CREATE TABLE IF NOT EXISTS auditoria_prontidao (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            competencia CHAR(7) NOT NULL,
            hash_amostra CHAR(64) NOT NULL,
            registros INT UNSIGNED NOT NULL,
            sem_status_atual INT UNSIGNED NOT NULL,
            sem_produto INT UNSIGNED NOT NULL,
            com_status_historico INT UNSIGNED NOT NULL,
            marcados_renovados INT UNSIGNED NOT NULL,
            marcados_cobrados INT UNSIGNED NOT NULL,
            marcados_nao_renovam INT UNSIGNED NOT NULL,
            sinais_conflitantes INT UNSIGNED NOT NULL,
            liberados_envio INT UNSIGNED NOT NULL DEFAULT 0,
            mensagens_enviadas INT UNSIGNED NOT NULL DEFAULT 0,
            origem VARCHAR(100) NOT NULL DEFAULT 'Base mensal local (MySQL)',
            criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_auditoria_mes_hash (competencia,hash_amostra),
            KEY ix_auditoria_competencia (competencia,criado_em)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function calcular(string $mes): array {
        if(!preg_match('/^20\d{2}-(0[1-9]|1[0-2])$/D',$mes)) throw new InvalidArgumentException('Competência inválida');
        $q=$this->db->prepare('SELECT linha_origem,segurado,vendedor,vencimento,produto,renovado,cobrado,nao_vai_renovar,status_atual,status_pdf FROM seguro_mensal WHERE competencia=? ORDER BY linha_origem ASC');
        $q->execute([$mes]);
        $r=['competencia'=>$mes,'registros'=>0,'sem_status_atual'=>0,'sem_produto'=>0,'com_status_historico'=>0,'marcados_renovados'=>0,'marcados_cobrados'=>0,'marcados_nao_renovam'=>0,'sinais_conflitantes'=>0,'liberados_envio'=>0,'mensagens_enviadas'=>0];
        $hash=hash_init('sha256');
        while($row=$q->fetch(PDO::FETCH_ASSOC)){
            hash_update($hash,json_encode(array_values($row),JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE)."\n");
            $r['registros']++;
            if(trim((string)($row['status_atual']??''))==='')$r['sem_status_atual']++;
            if(trim((string)($row['produto']??''))==='')$r['sem_produto']++;
            if(trim((string)($row['status_pdf']??''))!=='')$r['com_status_historico']++;
            $renovado=(int)$row['renovado']===1;
            $cobrado=(int)$row['cobrado']===1;
            $naoRenova=(int)$row['nao_vai_renovar']===1;
            if($renovado)$r['marcados_renovados']++;
            if($cobrado)$r['marcados_cobrados']++;
            if($naoRenova)$r['marcados_nao_renovam']++;
            if($renovado&&$naoRenova)$r['sinais_conflitantes']++;
        }
        if($r['registros']===0)throw new RuntimeException('Nenhum seguro para esta competência');
        if($mes==='2026-10'){
            $q=$this->db->query('SELECT COUNT(*) FROM seguro_snapshot');
            $origem=(int)$q->fetchColumn();
            if($origem!==$r['registros'])throw new RuntimeException('Diferença entre a base de outubro e o snapshot legado');
        }
        $r['hash_amostra']=hash_final($hash);
        return $r;
    }

    public function registrar(array $r): array {
        $fields=['competencia','hash_amostra','registros','sem_status_atual','sem_produto','com_status_historico','marcados_renovados','marcados_cobrados','marcados_nao_renovam','sinais_conflitantes','liberados_envio','mensagens_enviadas'];
        $values=array_map(fn($key)=>$r[$key],$fields);
        $this->db->beginTransaction();
        try{
            $q=$this->db->prepare('INSERT INTO auditoria_prontidao ('.implode(',',$fields).') VALUES ('.implode(',',array_fill(0,count($fields),'?')).') ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)');
            $q->execute($values);
            $id=(int)$this->db->lastInsertId();
            $novo=$q->rowCount()===1;
            $this->db->commit();
            return ['id'=>$id,'nova'=>$novo];
        }catch(Throwable $e){$this->db->rollBack();throw $e;}
    }

    public function ultimo(string $mes): ?array {
        $q=$this->db->prepare('SELECT * FROM auditoria_prontidao WHERE competencia=? ORDER BY id DESC LIMIT 1');
        $q->execute([$mes]);
        return $q->fetch(PDO::FETCH_ASSOC)?:null;
    }
    public function meses(): array {
        return $this->db->query('SELECT DISTINCT competencia FROM auditoria_prontidao ORDER BY competencia DESC')->fetchAll(PDO::FETCH_COLUMN);
    }
    public function historico(string $mes):array {
        $q=$this->db->prepare('SELECT id,registros,sem_status_atual,sem_produto,liberados_envio,mensagens_enviadas,criado_em FROM auditoria_prontidao WHERE competencia=? ORDER BY id DESC LIMIT 15');
        $q->execute([$mes]);return $q->fetchAll(PDO::FETCH_ASSOC);
    }
}
