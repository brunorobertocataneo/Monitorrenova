<?php
declare(strict_types=1);

/**
 * Model (Repository) dos indicadores de vencimentos.
 *
 * Todos os dados vêm da view seguro_dashboard, alimentada pelo
 * Google Sheets → n8n → MySQL. Consultas são somente leitura.
 *
 * A classe não conhece $_GET, não escreve HTML e não autoriza
 * cobranças: status não informado permanece "sem confirmação".
 */
final class VencimentoRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    /**
     * Retorna os dados para o dashboard dentro do intervalo indicado.
     * As datas e o vendedor foram normalizados pelo Controller.
     * Os parâmetros são vinculados e nunca concatenados com SQL.
     *
     * @return array<string,mixed>
     */
    public function consultar(string $de, string $ate, string $vendedor): array
    {
        $sellers=$this->db
            ->query('SELECT DISTINCT vendedor FROM seguro_dashboard ORDER BY vendedor')
            ->fetchAll(PDO::FETCH_COLUMN);

        $params=['de'=>$de,'ate'=>$ate];
        $where='vencimento BETWEEN :de AND :ate';
        if($vendedor!==''){
            $where.=' AND vendedor=:vendedor';
            $params['vendedor']=$vendedor;
        }

        // Todos os gráficos usam o MESMO filtro de datas e vendedor,
        // evitando mostrar totais inconsistentes entre os cartões.
        $stats=$this->query(
            "SELECT COUNT(*) total,
                COALESCE(SUM(CASE WHEN renovado=1 THEN 1 ELSE 0 END),0) renovados,
                COALESCE(SUM(CASE WHEN nao_vai_renovar=1 THEN 1 ELSE 0 END),0) nao_renovam,
                COALESCE(SUM(CASE WHEN cobrado=1 THEN 1 ELSE 0 END),0) cobrados,
                COALESCE(SUM(CASE WHEN produto IS NOT NULL AND produto<>'' THEN 1 ELSE 0 END),0) com_produto,
                COALESCE(SUM(CASE WHEN status_atual IS NOT NULL AND status_atual<>'' THEN 1 ELSE 0 END),0) com_status_atual,
                COALESCE(SUM(CASE WHEN renovado=0 AND nao_vai_renovar=0 AND cobrado=0
                    AND (status_atual IS NULL OR status_atual='') THEN 1 ELSE 0 END),0) sem_confirmacao
             FROM seguro_dashboard WHERE $where",
            $params
        )->fetch();

        $bySeller=$this->query(
            "SELECT vendedor,COUNT(*) total,
                COALESCE(SUM(CASE WHEN renovado=1 THEN 1 ELSE 0 END),0) renovados,
                COALESCE(SUM(CASE WHEN cobrado=1 THEN 1 ELSE 0 END),0) cobrados,
                COALESCE(SUM(CASE WHEN nao_vai_renovar=1 THEN 1 ELSE 0 END),0) nao_renovam,
                COALESCE(SUM(CASE WHEN status_atual IS NULL OR status_atual='' THEN 1 ELSE 0 END),0) sem_status
             FROM seguro_dashboard WHERE $where
             GROUP BY vendedor ORDER BY total DESC,vendedor ASC",
            $params
        )->fetchAll();

        $byProduct=$this->query(
            "SELECT COALESCE(NULLIF(produto,''),'Não informado') produto,
                COUNT(*) total
             FROM seguro_dashboard WHERE $where
             GROUP BY COALESCE(NULLIF(produto,''),'Não informado')
             ORDER BY total DESC,produto ASC",
            $params
        )->fetchAll();

        $byDate=$this->query(
            "SELECT vencimento,COUNT(*) total
             FROM seguro_dashboard WHERE $where
             GROUP BY vencimento ORDER BY vencimento ASC",
            $params
        )->fetchAll();

        $import=$this->db->query(
            'SELECT iniciado_em,total_registros FROM importacao_snapshot ORDER BY id DESC LIMIT 1'
        )->fetch();

        // Diferente do lote histórico, esta data representa a última
        // leitura bem-sucedida da planilha, registrada pelo sincronizador.
        $sync=$this->db->query(
            'SELECT total_registros,UNIX_TIMESTAMP(ultima_sincronizacao) instante
             FROM sheet_sync_estado WHERE id=1'
        )->fetch();
        $estadoSheet=null;
        if($sync){
            $instant=(int)$sync['instante'];
            $date=(new DateTimeImmutable('@'.$instant))
                ->setTimezone(new DateTimeZone('America/Sao_Paulo'));
            $estadoSheet=[
                'horario'=>$date->format('d/m/Y H:i:s'),
                'total'=>(int)$sync['total_registros'],
                'recente'=>$instant<=time()+60 && time()-$instant<=900
            ];
        }

        return [
            'sellers'=>$sellers,
            'stats'=>$stats?:[],
            'bySeller'=>$bySeller,
            'byProduct'=>$byProduct,
            'byDate'=>$byDate,
            'import'=>$import,
            'estadoSheet'=>$estadoSheet
        ];
    }

    /**
     * Centraliza o uso de parâmetros e proíbe SQL não parametrizado
     * vindo de filtros HTTP. O trecho WHERE foi construído internamente.
     */

    /**
     * Retorna seguros que vencem hoje e ainda nao foram concluidos, agrupados por vendedor.
     * Consulta exclusivamente a cópia local de seguro_dashboard.
     * Nao altera status nem faz envios. Exclui renovados e nao renovados.
     *
     * @return array<string,list<array<string,mixed>>>
     */
    public function vencimentosDeHoje(string $dia): array
    {
        $data=DateTimeImmutable::createFromFormat('!Y-m-d',$dia);
        if(!$data || $data->format('Y-m-d')!==$dia){
            throw new InvalidArgumentException('Data inválida para vencimentos de hoje.');
        }
        $sql='SELECT segurado,vendedor,produto,renovado,cobrado,nao_vai_renovar '
            .'FROM seguro_dashboard WHERE vencimento=:dia AND renovado=0 AND nao_vai_renovar=0 '
            .'ORDER BY vendedor ASC,segurado ASC,linha_origem ASC';
        $consulta=$this->db->prepare($sql);
        $consulta->execute(['dia'=>$dia]);
        $grupos=[];
        foreach($consulta->fetchAll(PDO::FETCH_ASSOC) as $seguro){
            $nome=trim((string)($seguro['vendedor']??''));
            if($nome==='')$nome='SEM VENDEDOR';
            $grupos[$nome][]=$seguro;
        }
        return $grupos;
    }

    private function query(string $sql, array $params): PDOStatement
    {
        $q=$this->db->prepare($sql);
        $q->execute($params);
        return $q;
    }
}
