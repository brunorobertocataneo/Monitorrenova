<?php
declare(strict_types=1);
require_once __DIR__.'/Recorrencia.php';

/**
 * Camada de persistencia das tarefas academicas. Todas as consultas utilizam
 * PDO parametrizado; DELETE e substituido por exclusao logica auditavel.
 */
final class TarefaRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    private function jaConcluida(int $id): bool
    {
        $q = $this->db->prepare("SELECT COUNT(*) FROM historico_tarefa_acompanhamento WHERE tarefa_id=? AND JSON_UNQUOTE(JSON_EXTRACT(dados_antes, '$.situacao'))='concluida'");
        $q->execute([$id]);
        return (int)$q->fetchColumn() > 0;
    }

    public function vendedores(): array
    {
        return $this->db->query("SELECT DISTINCT vendedor FROM seguro_mensal WHERE vendedor IS NOT NULL AND TRIM(vendedor)<>'' ORDER BY vendedor")->fetchAll(PDO::FETCH_COLUMN);
    }
    public function vendedorExiste(string $nome): bool
    {
        return in_array($nome, $this->vendedores(), true);
    }
    public function indicadores(): array
    {
        $q = $this->db->prepare("SELECT COALESCE(SUM(situacao='pendente'),0) pendentes, COALESCE(SUM(situacao='em_andamento'),0) andamento, COALESCE(SUM(situacao='concluida'),0) concluidas, COALESCE(SUM(situacao<>'concluida' AND vencimento < :hoje),0) atrasadas FROM tarefa_acompanhamento WHERE excluido_em IS NULL");
        $q->execute(['hoje'=>(new DateTimeImmutable('today',new DateTimeZone('America/Sao_Paulo')))->format('Y-m-d')]);
        return $q->fetch();
    }
    public function contarExcluidas(): int
    {
        return (int) $this->db
            ->query('SELECT COUNT(*) FROM tarefa_acompanhamento WHERE excluido_em IS NOT NULL')
            ->fetchColumn();
    }

    /**
     * @return array{registros:array<int,array<string,mixed>>,total:int,pagina:int,totalPaginas:int}
     */
    public function listar(string $situacao = '', string $busca = '', int $pagina = 1, int $limite = 20): array
    {
        $limite = max(1, min(100, $limite));
        $pagina = max(1, min(10000, $pagina));
        $where = 't.excluido_em IS NULL';
        $params = [];
        if ($situacao !== '') {
            $where .= ' AND t.situacao = :situacao';
            $params['situacao'] = $situacao;
        }
        if ($busca !== '') {
            $where .= " AND t.titulo LIKE :busca ESCAPE '!'";
            $params['busca'] = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $busca) . '%';
        }

        $totalQuery = $this->db->prepare('SELECT COUNT(*) FROM tarefa_acompanhamento t WHERE ' . $where);
        $totalQuery->execute($params);
        $total = (int) $totalQuery->fetchColumn();
        $totalPaginas = max(1, (int) ceil($total / $limite));
        $pagina = min($pagina, $totalPaginas);

        $sql = 'SELECT t.id,t.seguro_mensal_id,t.titulo,t.descricao,t.prioridade,'
            . 't.situacao,t.vencimento,t.categoria,t.responsavel,t.recorrencia,t.versao,t.criado_em,t.atualizado_em,'
            . 's.segurado,s.competencia '
            . 'FROM tarefa_acompanhamento t '
            . 'LEFT JOIN seguro_mensal s ON s.id=t.seguro_mensal_id '
            . 'WHERE ' . $where
            . ' ORDER BY t.vencimento IS NULL,t.vencimento ASC,t.id DESC '
            . 'LIMIT :limite OFFSET :offset';
        $q = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $q->bindValue(':' . $key, $value, PDO::PARAM_STR);
        }
        $q->bindValue(':limite', $limite, PDO::PARAM_INT);
        $q->bindValue(':offset', ($pagina - 1) * $limite, PDO::PARAM_INT);
        $q->execute();

        return [
            'registros' => $q->fetchAll(),
            'total' => $total,
            'pagina' => $pagina,
            'totalPaginas' => $totalPaginas,
        ];
    }

    /** @return array<string,mixed>|null */
    public function buscar(int $id): ?array
    {
        $q = $this->db->prepare(
            'SELECT t.*,s.segurado,s.competencia '
            . 'FROM tarefa_acompanhamento t '
            . 'LEFT JOIN seguro_mensal s ON s.id=t.seguro_mensal_id '
            . 'WHERE t.id=:id AND t.excluido_em IS NULL'
        );
        $q->execute(['id' => $id]);
        return $q->fetch() ?: null;
    }

    /** @return array<int,array{id:int,segurado:string,competencia:string}> */
    public function segurosParaAssociar(): array
    {
        return $this->db->query(
            'SELECT id,segurado,competencia FROM seguro_mensal ORDER BY competencia DESC,segurado LIMIT 500'
        )->fetchAll();
    }

    public function seguroExiste(int $id): bool
    {
        $q = $this->db->prepare('SELECT COUNT(*) FROM seguro_mensal WHERE id=?');
        $q->execute([$id]);
        return (int) $q->fetchColumn() === 1;
    }

    /**
     * @param array{titulo:string,descricao:string,prioridade:string,situacao:string,vencimento:?string,seguro_mensal_id:?int} $data
     */
    public function criar(array $data, int $usuarioId): int
    {
        return $this->transacao(function () use ($data, $usuarioId): int {
            $q = $this->db->prepare(
                'INSERT INTO tarefa_acompanhamento '
                . '(seguro_mensal_id,titulo,descricao,prioridade,situacao,vencimento,categoria,responsavel,recorrencia,criado_por,atualizado_por) '
                . 'VALUES (:seguro,:titulo,:descricao,:prioridade,:situacao,:vencimento,:categoria,:responsavel,:recorrencia,:usuario,:atualizador)'
            );
            $q->execute([
                'seguro' => $data['seguro_mensal_id'],
                'titulo' => $data['titulo'],
                'descricao' => $data['descricao'],
                'prioridade' => $data['prioridade'],
                'situacao' => $data['situacao'],
                'vencimento' => $data['vencimento'],
                'categoria' => $data['categoria'] ?? 'geral',
                'responsavel' => $data['responsavel'] ?? null,
                'recorrencia' => $data['recorrencia'] ?? 'nenhuma',
                'usuario' => $usuarioId,
                'atualizador' => $usuarioId,
            ]);
            $id = (int) $this->db->lastInsertId();
            $this->registrarEvento($id, $usuarioId, 'criacao', 1, null, $this->estado($id));
            return $id;
        });
    }

    public function atualizar(int $id, int $versao, array $data, int $usuarioId): bool
    {
        return $this->transacao(function () use ($id, $versao, $data, $usuarioId): bool {
            $anterior = $this->estado($id, true);
            if ($anterior === null || $anterior['excluido_em'] !== null || (int) $anterior['versao'] !== $versao) {
                return false;
            }
            $q = $this->db->prepare(
                'UPDATE tarefa_acompanhamento '
                . 'SET seguro_mensal_id=:seguro,titulo=:titulo,descricao=:descricao,'
                . 'prioridade=:prioridade,situacao=:situacao,vencimento=:vencimento,categoria=:categoria,responsavel=:responsavel,recorrencia=:recorrencia,'
                . 'atualizado_por=:usuario,versao=versao+1 '
                . 'WHERE id=:id AND versao=:versao AND excluido_em IS NULL'
            );
            $q->execute([
                'seguro' => $data['seguro_mensal_id'],
                'titulo' => $data['titulo'],
                'descricao' => $data['descricao'],
                'prioridade' => $data['prioridade'],
                'situacao' => $data['situacao'],
                'vencimento' => $data['vencimento'],
                'categoria' => $data['categoria'] ?? 'geral',
                'responsavel' => $data['responsavel'] ?? null,
                'recorrencia' => $data['recorrencia'] ?? 'nenhuma',
                'usuario' => $usuarioId,
                'id' => $id,
                'versao' => $versao,
            ]);
            if ($q->rowCount() !== 1) {
                return false;
            }
            $this->registrarEvento($id, $usuarioId, 'edicao', $versao + 1, $anterior, $this->estado($id));
            if ($anterior['situacao'] !== 'concluida' && !$this->jaConcluida($id) && $data['situacao'] === 'concluida' && ($data['recorrencia'] ?? 'nenhuma') !== 'nenhuma' && $data['vencimento']) {
                $agenda = new AgendaRecorrencias([
                    'diaria' => new RecorrenciaDias(1),
                    'semanal' => new RecorrenciaDias(7),
                    'mensal' => new RecorrenciaMensal()
                ]);
                $hoje = new DateTimeImmutable('today', new DateTimeZone('America/Sao_Paulo'));
                $data['situacao'] = 'pendente';
                $data['vencimento'] = $agenda->calcular($data['recorrencia'], $data['vencimento'], $hoje);
                $this->criar($data, $usuarioId);
            }
            return true;
        });
    }

    /** Arquivamento logico sem excluir fisicamente a tarefa ou o seu historico. */
    public function excluir(int $id, int $versao, int $usuarioId): bool
    {
        return $this->transacao(function () use ($id, $versao, $usuarioId): bool {
            $anterior = $this->estado($id, true);
            if ($anterior === null || $anterior['excluido_em'] !== null || (int) $anterior['versao'] !== $versao) {
                return false;
            }
            $q = $this->db->prepare(
                'UPDATE tarefa_acompanhamento '
                . 'SET excluido_em=NOW(),excluido_por=:usuario,atualizado_por=:usuario2,versao=versao+1 '
                . 'WHERE id=:id AND versao=:versao AND excluido_em IS NULL'
            );
            $q->execute([
                'usuario' => $usuarioId,
                'usuario2' => $usuarioId,
                'id' => $id,
                'versao' => $versao,
            ]);
            if ($q->rowCount() !== 1) {
                return false;
            }
            $this->registrarEvento($id, $usuarioId, 'arquivamento', $versao + 1, $anterior, $this->estado($id));
            return true;
        });
    }

    /** Inclui tarefas arquivadas para permitir consultar seus eventos de auditoria. */
    public function buscarComArquivadas(int $id): ?array
    {
        return $this->estado($id);
    }

    /** @return array<int,array<string,mixed>> */
    public function historico(int $id): array
    {
        $q = $this->db->prepare(
            'SELECT h.id,h.tarefa_id,h.usuario_id,u.login AS autor,h.acao,h.versao,'
            . 'h.dados_antes,h.dados_depois,h.criado_em '
            . 'FROM historico_tarefa_acompanhamento h '
            . 'JOIN usuario u ON u.id=h.usuario_id '
            . 'WHERE h.tarefa_id=:id ORDER BY h.versao ASC,h.id ASC'
        );
        $q->execute(['id' => $id]);
        return $q->fetchAll();
    }

    /** @return array<string,mixed>|null */
    private function estado(int $id, bool $travar = false): ?array
    {
        $sql = 'SELECT id,seguro_mensal_id,titulo,descricao,prioridade,situacao,vencimento,categoria,responsavel,recorrencia,'
            . 'versao,criado_por,atualizado_por,excluido_por,criado_em,atualizado_em,excluido_em '
            . 'FROM tarefa_acompanhamento WHERE id=:id' . ($travar ? ' FOR UPDATE' : '');
        $q = $this->db->prepare($sql);
        $q->execute(['id' => $id]);
        return $q->fetch() ?: null;
    }

    private function registrarEvento(
        int $id, int $usuarioId, string $acao, int $versao, ?array $antes, ?array $depois
    ): void {
        if ($depois === null) {
            throw new RuntimeException('Nao foi possivel obter o estado para auditoria.');
        }
        $q = $this->db->prepare(
            'INSERT INTO historico_tarefa_acompanhamento '
            . '(tarefa_id,usuario_id,acao,versao,dados_antes,dados_depois) '
            . 'VALUES (:tarefa,:usuario,:acao,:versao,:antes,:depois)'
        );
        $q->execute([
            'tarefa' => $id,
            'usuario' => $usuarioId,
            'acao' => $acao,
            'versao' => $versao,
            'antes' => $antes === null ? null : json_encode($antes, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'depois' => json_encode($depois, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        ]);
    }

    /** Usa a transacao externa quando o teste ja controla um rollback. */
    private function transacao(callable $operacao): mixed
    {
        $propria = !$this->db->inTransaction();
        if ($propria) {
            $this->db->beginTransaction();
        }
        try {
            $retorno = $operacao();
            if ($propria) {
                $this->db->commit();
            }
            return $retorno;
        } catch (Throwable $e) {
            if ($propria && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
}