<?php
declare(strict_types=1);

final class SeguroRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    /**
     * Retorna dados da consulta sem alterar qualquer apólice.
     * Filtros validados pela página controladora; os valores SQL são parametrizados.
     *
     * @param array{vendedor:string,produto:string,de:?string,ate:?string,busca:string} $filtros
     * @return array<string,mixed>
     */
    public function consultar(array $filtros, int $pagina = 1, int $limite = 30): array
    {
        $pagina = max(1, min(10000, $pagina));
        $limite = max(1, min(100, $limite));

        $vendedores = $this->db
            ->query('SELECT DISTINCT vendedor FROM seguro_dashboard ORDER BY vendedor')
            ->fetchAll(PDO::FETCH_COLUMN);

        $produtos = $this->db
            ->query("SELECT DISTINCT produto FROM seguro_dashboard WHERE produto IS NOT NULL AND produto<>'' ORDER BY produto")
            ->fetchAll(PDO::FETCH_COLUMN);

        $condicoes = [];
        $params = [];

        if ($filtros['vendedor'] !== '') {
            $condicoes[] = 'vendedor=:vendedor';
            $params['vendedor'] = $filtros['vendedor'];
        }
        if ($filtros['produto'] !== '') {
            $condicoes[] = 'produto=:produto';
            $params['produto'] = $filtros['produto'];
        }
        if ($filtros['de'] !== null) {
            $condicoes[] = 'vencimento>=:de';
            $params['de'] = $filtros['de'];
        }
        if ($filtros['ate'] !== null) {
            $condicoes[] = 'vencimento<=:ate';
            $params['ate'] = $filtros['ate'];
        }
        if ($filtros['busca'] !== '') {
            $condicoes[] = 'segurado LIKE :busca';
            $params['busca'] = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $filtros['busca']) . '%';
        }

        $where = $condicoes ? ' WHERE ' . implode(' AND ', $condicoes) : '';

        $consultaTotal = $this->db->prepare('SELECT COUNT(*) FROM seguro_dashboard' . $where);
        $consultaTotal->execute($params);
        $total = (int) $consultaTotal->fetchColumn();

        $totalPaginas = max(1, (int) ceil($total / $limite));
        $pagina = min($pagina, $totalPaginas);
        $offset = ($pagina - 1) * $limite;

        $sql = 'SELECT linha_origem,segurado,vendedor,vencimento,renovado,cobrado,nao_vai_renovar,produto,status_atual,status_pdf,premio_pdf,fonte_pdf '
            . 'FROM seguro_dashboard' . $where
            . ' ORDER BY vencimento ASC,vendedor ASC,linha_origem ASC LIMIT :limite OFFSET :offset';

        $consulta = $this->db->prepare($sql);
        foreach ($params as $campo => $valor) {
            $consulta->bindValue(':' . $campo, $valor, PDO::PARAM_STR);
        }
        $consulta->bindValue(':limite', $limite, PDO::PARAM_INT);
        $consulta->bindValue(':offset', $offset, PDO::PARAM_INT);
        $consulta->execute();

        $ultimaImportacao = $this->db
            ->query('SELECT iniciado_em,total_registros FROM importacao_snapshot ORDER BY id DESC LIMIT 1')
            ->fetch();

        $estatisticas = $this->db->query(
            "SELECT COUNT(*) AS total,"
            . "SUM(CASE WHEN produto IS NOT NULL AND produto<>'' THEN 1 ELSE 0 END) AS com_produto,"
            . "SUM(CASE WHEN status_atual IS NOT NULL AND status_atual<>'' THEN 1 ELSE 0 END) AS status_atual,"
            . "SUM(CASE WHEN status_pdf IS NOT NULL AND status_pdf<>'' THEN 1 ELSE 0 END) AS status_pdf "
            . "FROM seguro_dashboard"
        )->fetch();

        return [
            'vendedores' => $vendedores,
            'produtos' => $produtos,
            'total' => $total,
            'totalPaginas' => $totalPaginas,
            'pagina' => $pagina,
            'registros' => $consulta->fetchAll(),
            'ultimaImportacao' => $ultimaImportacao,
            'estatisticas' => $estatisticas,
        ];
    }

    /**
     * Dados da "prévia para conferência" do dia, usados no painel inicial.
     *
     * Fonte: seguro_dashboard do MySQL (última importação), NÃO Google Sheets
     * em tempo real. Ausência de marcações não confirma atraso ou renovação.
     *
     * @return array{dia:string,total:int,paraConferencia:int,vendedores:int,
     *     listados:int,omitidos:int,grupos:array<string,list<string>>}
     */
    public function resumoDoDia(string $dia): array
    {
        // Garante que o dia seja válido antes de utilizá-lo nas consultas.
        $data = DateTimeImmutable::createFromFormat('!Y-m-d', $dia);
        if (!$data || $data->format('Y-m-d') !== $dia) {
            throw new InvalidArgumentException('Data inválida para o resumo diário');
        }

        // Contagens feitas no banco sem um limite de linhas; evita um
        // indicador subestimado quando muitos seguros vencem na mesma data.
        $contagem = $this->db->prepare(
            'SELECT COUNT(*) AS total, '
            . 'SUM(CASE WHEN renovado=0 AND cobrado=0 AND nao_vai_renovar=0 THEN 1 ELSE 0 END) AS para_conferencia, '
            . 'COUNT(DISTINCT vendedor) AS vendedores '
            . 'FROM seguro_dashboard WHERE vencimento=:dia'
        );
        $contagem->execute(['dia' => $dia]);
        $estatisticas = $contagem->fetch();

        // Somente os nomes ainda sem marcações explícitas entram na prévia.
        // O LIMIT impede excesso de dados pessoais na página inicial.
        $q = $this->db->prepare(
            'SELECT vendedor,segurado FROM seguro_dashboard '
            . 'WHERE vencimento=:dia AND renovado=0 AND cobrado=0 AND nao_vai_renovar=0 '
            . 'ORDER BY vendedor ASC, segurado ASC LIMIT 100'
        );
        $q->execute(['dia' => $dia]);

        $grupos = [];
        $listados = 0;
        foreach ($q->fetchAll() as $linha) {
            $vendedor = (string) ($linha['vendedor'] ?: 'SEM VENDEDOR');
            $segurado = (string) ($linha['segurado'] ?: 'SEGURADO NÃO INFORMADO');
            $grupos[$vendedor][] = $segurado;
            ++$listados;
        }

        $paraConferencia = (int) ($estatisticas['para_conferencia'] ?? 0);
        return [
            'dia' => $dia,
            'total' => (int) ($estatisticas['total'] ?? 0),
            'paraConferencia' => $paraConferencia,
            'vendedores' => (int) ($estatisticas['vendedores'] ?? 0),
            'listados' => $listados,
            'omitidos' => max(0, $paraConferencia - $listados),
            'grupos' => $grupos,
        ];
    }

    /**
     * Informa a última leitura do Google Sheets, permitindo distinguir um
     * dashboard atualizado de uma cópia antiga após o PC ficar desligado.
     * Não expõe nomes, tokens nem detalhes do arquivo.
     */
    public function estadoSincronizacao(): ?array
    {
        $r=$this->db->query('SELECT competencia_ativa,total_registros,UNIX_TIMESTAMP(ultima_sincronizacao) AS instante FROM sheet_sync_estado WHERE id=1')->fetch();
        if (!$r) return null;
        $instante=(int)$r['instante'];
        $data=(new DateTimeImmutable('@'.$instante))->setTimezone(new DateTimeZone('America/Sao_Paulo'));
        return [
            'total'=>(int)$r['total_registros'],
            'competencia'=>$r['competencia_ativa'],
            'horario'=>$data->format('d/m/Y H:i:s'),
            'recente'=>$instante<=time()+60 && time()-$instante<=900
        ];
    }
}
