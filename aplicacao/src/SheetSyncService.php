<?php
declare(strict_types=1);

/**
 * Model/serviço POO de sincronização mensal (Google Sheets → MySQL).
 *
 * Cada competência é arquivada sob a chave (mes, linha_origem). Um novo mês
 * pode reutilizar as mesmas linhas do Google Sheets sem sobrescrever o
 * histórico. Trocar o mês ativo exige:
 *  1) lote completo e homogêneo com apenas UM mês;
 *  2) competência imediatamente posterior à atual;
 *  3) duas leituras seguidas com exatamente o mesmo hash;
 *  4) aprovação autenticada do administrador no painel PHP.
 *
 * Nunca grava dados de um lote novo no mês ativo antes da aprovação.
 * Não tem envio WhatsApp nem trata ausência de status como cobrança.
 */
final class SheetSyncService
{
    private const MAX_SOURCE_LENGTH=120;
    private static function planilha():string
    {
        $id=getenv('GOOGLE_SHEETS_ID');
        if(!is_string($id)||strlen($id)>self::MAX_SOURCE_LENGTH||
           !preg_match('/^[A-Za-z0-9_-]{30,120}$/D',$id)){
            throw new DomainException('Origem Google Sheets nao configurada');
        }
        return $id;
    }
    private const MAX_LINHAS=998;
    private const WORKFLOW='brunoSheetsSyncFiveMin20261021';

    public function __construct(private readonly PDO $db) {}

    /**
     * @return array{hash:string,registros:array,execucao_n8n:?int,competencia:string}
     */
    private static function validar(array $request):array
    {
        if(($request['spreadsheetId']??null)!==self::planilha() ||
            ($request['aba']??null)!=='_BASE' ||
            !is_array($request['registros']??null) ||
            !array_is_list($request['registros'])){
            throw new InvalidArgumentException('Fonte inesperada ou lote invalido');
        }
        foreach(array_keys($request) as $key){
            if(!in_array($key,['spreadsheetId','aba','registros','execucao_n8n'],true)){
                throw new InvalidArgumentException('Campo inesperado no lote');
            }
        }
        $exec=$request['execucao_n8n']??null;
        if($exec!==null&&(!is_int($exec)||$exec<1)) {
            throw new InvalidArgumentException('ID da execucao n8n invalido');
        }
        $rows=$request['registros'];
        if(count($rows)<1||count($rows)>self::MAX_LINHAS){
            throw new InvalidArgumentException('Numero de seguros fora do limite');
        }
        $required=['linha_origem','segurado','vendedor','vencimento',
            'renovado','cobrado','nao_vai_renovar','produto','status_atual',
            'status_pdf','premio_pdf','fonte_pdf'];
        $limits=['segurado'=>255,'vendedor'=>180,'produto'=>120,
            'status_atual'=>30,'status_pdf'=>30,'fonte_pdf'=>255];
        $seen=[];$normal=[];$mes=null;
        foreach($rows as $row){
            if(!is_array($row)||array_is_list($row) ||
                array_diff(array_keys($row),$required) ||
                array_diff($required,array_keys($row))){
                throw new InvalidArgumentException('Estrutura de registro invalida');
            }
            $id=$row['linha_origem'];
            if(!is_int($id)||$id<2||$id>1000||isset($seen[$id])){
                throw new InvalidArgumentException('Linha ausente ou duplicada');
            }
            $seen[$id]=true;
            $segurado=trim((string)$row['segurado']);
            $vendedor=trim((string)$row['vendedor']);
            $data=(string)$row['vencimento'];
            $day=DateTimeImmutable::createFromFormat('!Y-m-d',$data);
            if($segurado===''||$vendedor===''||
                !$day||$day->format('Y-m-d')!==$data){
                throw new InvalidArgumentException('Segurado, vendedor ou vencimento invalidos');
            }
            $current=substr($data,0,7);
            if($mes!==null && $current!==$mes){
                // Não interpretar planilha parcial de transição como nova base.
                throw new DomainException('Planilha contem competencias misturadas');
            }
            $mes=$current;
            foreach($limits as $field=>$limit){
                if($row[$field]!==null && !is_string($row[$field])){
                    throw new InvalidArgumentException('Campo texto invalido');
                }
                if($row[$field]!==null && strlen($row[$field])>$limit){
                    throw new InvalidArgumentException('Campo excede o limite');
                }
            }
            foreach(['renovado','cobrado','nao_vai_renovar'] as $flag){
                if(!in_array($row[$flag],[0,1],true)){
                    throw new InvalidArgumentException('Marcacao invalida');
                }
            }
            $price=$row['premio_pdf'];
            if($price!==null && (!is_string($price) ||
                !preg_match('/^\d{1,10}(\.\d{1,2})?$/D',$price))){
                throw new InvalidArgumentException('Premio historico invalido');
            }
            $normal[]=[
                'linha_origem'=>$id,
                'segurado'=>$segurado,'vendedor'=>$vendedor,'vencimento'=>$data,
                'renovado'=>$row['renovado'],'cobrado'=>$row['cobrado'],
                'nao_vai_renovar'=>$row['nao_vai_renovar'],
                'produto'=>$row['produto']===null?null:trim($row['produto']),
                'status_atual'=>$row['status_atual']===null?null:trim($row['status_atual']),
                'status_pdf'=>$row['status_pdf']===null?null:trim($row['status_pdf']),
                'premio_pdf'=>$price,'fonte_pdf'=>$row['fonte_pdf']
            ];
        }
        usort($normal,static fn(array $a,array $b):int=>$a['linha_origem']<=>$b['linha_origem']);
        return [
            'hash'=>hash('sha256',json_encode($normal,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)),
            'registros'=>$normal,
            'execucao_n8n'=>$exec,
            'competencia'=>(string)$mes
        ];
    }

    private static function proximoMes(string $mes):string
    {
        $date=DateTimeImmutable::createFromFormat('!Y-m',$mes);
        if(!$date||$date->format('Y-m')!==$mes){
            throw new DomainException('Competencia ativa invalida');
        }
        return $date->modify('+1 month')->format('Y-m');
    }

    private function guardar(array $registros,string $competencia):void
    {
        $sql='INSERT INTO sheet_sync_registro_mensal
            (competencia,linha_origem,segurado,vendedor,vencimento,renovado,
            cobrado,nao_vai_renovar,produto,status_atual,status_pdf,premio_pdf,fonte_pdf)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE
              renovado=VALUES(renovado),cobrado=VALUES(cobrado),
              nao_vai_renovar=VALUES(nao_vai_renovar),
              produto=VALUES(produto),status_atual=VALUES(status_atual),
              status_pdf=VALUES(status_pdf),premio_pdf=VALUES(premio_pdf),
              fonte_pdf=VALUES(fonte_pdf)';
        $q=$this->db->prepare($sql);
        foreach($registros as $r){
            $q->execute([$competencia,...array_values($r)]);
        }
    }

    /**
     * Uma transição nunca altera segurados: guarda somente competência,
     * hash e contagens para revisão na interface administrativa.
     * Leituras idênticas aumentam a confiança de que a substituição acabou.
     */
    private function observarTransicao(string $mes,string $hash,int $total):array
    {
        $q=$this->db->prepare('SELECT estado,hash_observado,leituras_iguais
            FROM sheet_sync_transicao WHERE competencia=? FOR UPDATE');
        $q->execute([$mes]);
        $pending=$q->fetch();

        if(!$pending){
            $q=$this->db->prepare("INSERT INTO sheet_sync_transicao
              (competencia,estado,hash_observado,total_detectado,leituras_iguais)
              VALUES (?,'pendente',?,?,1)");
            $q->execute([$mes,$hash,$total]);
            return ['autorizada'=>false,'leituras'=>1];
        }
        if($pending['hash_observado']!==$hash){
            // Mudança após aprovação INVALIDA o consentimento anterior.
            $q=$this->db->prepare("UPDATE sheet_sync_transicao
                SET estado='pendente',hash_observado=?,total_detectado=?,
                    leituras_iguais=1,aprovada_em=NULL,concluida_em=NULL,
                    ultima_leitura=CURRENT_TIMESTAMP WHERE competencia=?");
            $q->execute([$hash,$total,$mes]);
            return ['autorizada'=>false,'leituras'=>1];
        }
        if($pending['estado']==='autorizada'){
            return ['autorizada'=>true,'leituras'=>(int)$pending['leituras_iguais']];
        }
        if($pending['estado']==='concluida'){
            throw new DomainException('Transicao concluida nao pode ser reutilizada');
        }
        $q=$this->db->prepare('UPDATE sheet_sync_transicao SET
            leituras_iguais=LEAST(leituras_iguais+1,999999),
            total_detectado=?,ultima_leitura=CURRENT_TIMESTAMP
            WHERE competencia=?');
        $q->execute([$total,$mes]);
        return ['autorizada'=>false,'leituras'=>(int)$pending['leituras_iguais']+1];
    }

    /**
     * Preview sempre realiza ROLLBACK. Apply grava um lote por transação.
     * Se surgir novo mês, apenas REGISTRA A DETECÇÃO; o mês ativo permanece.
     * Depois da confirmação do administrador, o próximo apply realiza
     * a troca de competência com o mesmo hash previamente autorizado.
     */
    public function executar(array $entrada,bool $aplicar):array
    {
        $carga=self::validar($entrada);
        $dados=$carga['registros'];
        $mes=$carga['competencia'];
        $hash=$carga['hash'];
        $total=count($dados);
        $exec=$carga['execucao_n8n'];
        $this->db->beginTransaction();
        try{
            $estado=$this->db->query('SELECT hash_sha256,total_registros,
                competencia_ativa FROM sheet_sync_estado WHERE id=1 FOR UPDATE')->fetch();
            if(!$estado||!is_string($estado['competencia_ativa'])){
                throw new DomainException('Configuracao mensal nao inicializada');
            }
            $ativo=$estado['competencia_ativa'];
            $nova=$mes!==$ativo;
            if($nova && $mes!==self::proximoMes($ativo)){
                throw new DomainException('Competencia fora da sequencia mensal');
            }
            if($nova){
                // Verifica que a competência antiga foi integralmente arquivada.
                $q=$this->db->prepare('SELECT COUNT(*) FROM sheet_sync_registro_mensal
                    WHERE competencia=?');
                $q->execute([$ativo]);
                $antigos=(int)$q->fetchColumn();
                if($antigos!==(int)$estado['total_registros']){
                    throw new DomainException('Arquivo do mes ativo nao esta completo');
                }
                if(!$aplicar){
                    $this->db->rollBack();
                    return ['ok'=>true,'mode'=>'preview','registros'=>$total,
                        'competencia_ativa'=>$ativo,'competencia_detectada'=>$mes,
                        'aguardando_aprovacao'=>true,'historico_preservado'=>true];
                }
                $observado=$this->observarTransicao($mes,$hash,$total);
                if(!$observado['autorizada']){
                    $this->db->commit(); // somente METADADOS, nunca segurados
                    return ['ok'=>true,'mode'=>'pending','registros'=>$total,
                        'competencia_ativa'=>$ativo,'competencia_detectada'=>$mes,
                        'leituras_iguais'=>$observado['leituras'],
                        'aguardando_aprovacao'=>true,'historico_preservado'=>true];
                }
            }

            // Compara apenas registros do MESMO mês: linhas 2,3,... podem
            // ser reutilizadas livremente em outra competência.
            $q=$this->db->prepare('SELECT linha_origem,segurado,vendedor,vencimento
                FROM sheet_sync_registro_mensal WHERE competencia=? FOR UPDATE');
            $q->execute([$mes]);
            $anteriores=[];
            foreach($q->fetchAll() as $a)$anteriores[(int)$a['linha_origem']]=$a;
            $ids=array_fill_keys(array_column($dados,'linha_origem'),true);
            foreach($anteriores as $id=>$old){
                if(!isset($ids[$id])){
                    throw new DomainException('Linha arquivada ausente do lote atual');
                }
            }
            $novos=0;
            foreach($dados as $r){
                $prev=$anteriores[$r['linha_origem']]??null;
                if(!$prev){$novos++;continue;}
                if($prev['segurado']!==$r['segurado'] ||
                    $prev['vendedor']!==$r['vendedor'] ||
                    $prev['vencimento']!==$r['vencimento']){
                    throw new DomainException('Identidade mudou na mesma linha do mes');
                }
            }
            $igual=!$nova && hash_equals((string)$estado['hash_sha256'],$hash);
            if(!$aplicar){
                // PREVIEW JAMAIS pode gravar nem registrar confirmacao.
                $this->db->rollBack();
                return ['ok'=>true,'mode'=>'preview','registros'=>$total,
                    'competencia_ativa'=>$mes,'novos'=>$novos,
                    'sem_alteracoes'=>$igual,'historico_preservado'=>true];
            }
            if(!$igual)$this->guardar($dados,$mes);
            $q=$this->db->prepare('SELECT COUNT(*) FROM sheet_sync_registro_mensal
                WHERE competencia=?');
            $q->execute([$mes]);
            if((int)$q->fetchColumn()!==$total){
                throw new DomainException('Quantidade divergente apos sincronizacao');
            }
            $q=$this->db->prepare('UPDATE sheet_sync_estado SET
                hash_sha256=?,total_registros=?,competencia_ativa=?,
                ultima_sincronizacao=CURRENT_TIMESTAMP,
                sincronizacoes=sincronizacoes+1 WHERE id=1');
            $q->execute([$hash,$total,$mes]);
            if($nova){
                $q=$this->db->prepare("UPDATE sheet_sync_transicao
                    SET estado='concluida',concluida_em=CURRENT_TIMESTAMP
                    WHERE competencia=? AND estado='autorizada'");
                $q->execute([$mes]);
                if($q->rowCount()!==1){
                    throw new DomainException('Aprovacao nao confirmada');
                }
            }
            if($exec!==null){
                $q=$this->db->prepare('INSERT INTO sheet_sync_execucao
                    (workflow_id,execution_id,total_registros,sem_alteracoes)
                    VALUES (?,?,?,?)
                    ON DUPLICATE KEY UPDATE
                      total_registros=VALUES(total_registros),
                      sem_alteracoes=VALUES(sem_alteracoes)');
                $q->execute([self::WORKFLOW,$exec,$total,$igual?1:0]);
            }
            $this->db->commit();
            return ['ok'=>true,'mode'=>$aplicar?'apply':'preview',
                'registros'=>$total,'novos'=>$novos,
                'competencia_ativa'=>$mes,'transicao_concluida'=>$nova,
                'sem_alteracoes'=>$igual,'historico_preservado'=>true];
        }catch(Throwable $e){
            if($this->db->inTransaction())$this->db->rollBack();
            throw $e;
        }
    }
}
