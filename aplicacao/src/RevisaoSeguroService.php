<?php
declare(strict_types=1);

/**
 * Serviço de revisão interna de seguros históricos.
 *
 * - Revisa SOMENTE seguro_mensal, nunca a planilha sincronizada.
 * - Não valida situação da apólice e não libera cobranças.
 * - Registra histórico apenas quando situação ou observação mudam.
 * - Exige token de versão para impedir sobrescrever revisão concorrente.
 * - Respeita transação preexistente, permitindo testes que dão ROLLBACK.
 */
final class RevisaoSeguroService
{
    public const SITUACOES=[
        'pendente'=>'Pendente de revisão',
        'em_analise'=>'Em análise',
        'conferido'=>'Conferido manualmente',
        'divergencia'=>'Divergência encontrada'
    ];

    public function __construct(private readonly PDO $db){}

    public static function versao(?array $anterior):string
    {
        if($anterior===null)return 'novo';
        return hash('sha256',$anterior['situacao'].'|'.
            $anterior['observacao'].'|'.$anterior['atualizado_em']);
    }

    public function seguro(int $id):?array
    {
        $q=$this->db->prepare('SELECT id,competencia,linha_origem,segurado,
          vendedor,vencimento,produto,status_atual,status_pdf
          FROM seguro_mensal WHERE id=?');
        $q->execute([$id]);
        return $q->fetch(PDO::FETCH_ASSOC)?:null;
    }

    public function atual(int $id):?array
    {
        $q=$this->db->prepare('SELECT situacao,observacao,atualizado_em
            FROM revisao_seguro WHERE seguro_mensal_id=?');
        $q->execute([$id]);
        return $q->fetch(PDO::FETCH_ASSOC)?:null;
    }

    public function historico(int $id):array
    {
        $q=$this->db->prepare('SELECT h.situacao_anterior,h.situacao_nova,
          h.observacao_anterior,h.observacao_nova,h.criado_em,u.login
          FROM historico_revisao h LEFT JOIN usuario u ON u.id=h.usuario_id
          WHERE h.seguro_mensal_id=? ORDER BY h.id DESC LIMIT 30');
        $q->execute([$id]);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return bool true se alterou; false se a revisão era idêntica.
     */
    public function registrar(
        int $seguroId,int $usuarioId,mixed $situacao,mixed $observacao,
        mixed $expectedVersion
    ):bool {
        if($seguroId<1||$usuarioId<1){
            throw new InvalidArgumentException('Identificador inválido.');
        }
        if(!is_string($situacao)||!isset(self::SITUACOES[$situacao])||
           !is_string($observacao)||mb_strlen($observacao)>500){
            throw new InvalidArgumentException(
                'Situação ou observação inválida (máximo 500 caracteres).');
        }
        if(preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f]/',$observacao)){
            throw new InvalidArgumentException('Observação contém caracteres inválidos.');
        }
        $observacao=trim($observacao);
        if(!is_string($expectedVersion)||
          ($expectedVersion!=='novo'&&!preg_match('/^[0-9a-f]{64}$/D',$expectedVersion))){
            throw new InvalidArgumentException('Token de versão inválido.');
        }

        $ownTransaction=!$this->db->inTransaction();
        if($ownTransaction)$this->db->beginTransaction();
        try {
            $q=$this->db->prepare('SELECT id FROM seguro_mensal WHERE id=? FOR UPDATE');
            $q->execute([$seguroId]);
            if(!$q->fetchColumn())throw new RuntimeException('Seguro não encontrado.');

            $q=$this->db->prepare('SELECT situacao,observacao,atualizado_em
               FROM revisao_seguro WHERE seguro_mensal_id=? FOR UPDATE');
            $q->execute([$seguroId]);
            $previous=$q->fetch(PDO::FETCH_ASSOC)?:null;
            $version=self::versao($previous);
            if(!hash_equals($version,$expectedVersion)){
                throw new RuntimeException(
                  'Este registro mudou desde que a página foi aberta. Atualize antes de salvar.');
            }

            $oldStatus=$previous['situacao']??null;
            $oldNote=$previous['observacao']??null;
            $changed=($oldStatus!==$situacao||$oldNote!==$observacao);
            if($changed){
                $q=$this->db->prepare('INSERT INTO revisao_seguro
                  (seguro_mensal_id,situacao,observacao,atualizado_por)
                  VALUES (?,?,?,?)
                  ON DUPLICATE KEY UPDATE situacao=VALUES(situacao),
                    observacao=VALUES(observacao),atualizado_por=VALUES(atualizado_por)');
                $q->execute([$seguroId,$situacao,$observacao,$usuarioId]);
                $q=$this->db->prepare('INSERT INTO historico_revisao
                  (seguro_mensal_id,situacao_anterior,situacao_nova,
                   observacao_anterior,observacao_nova,usuario_id)
                  VALUES (?,?,?,?,?,?)');
                $q->execute([$seguroId,$oldStatus,$situacao,$oldNote,
                             $observacao,$usuarioId]);
            }
            if($ownTransaction)$this->db->commit();
            return $changed;
        }catch(Throwable $e){
            if($ownTransaction && $this->db->inTransaction())$this->db->rollBack();
            throw $e;
        }
    }
}
