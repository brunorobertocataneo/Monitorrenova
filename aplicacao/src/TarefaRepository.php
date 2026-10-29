<?php
declare(strict_types=1);

/**
 * Camada de persistencia das tarefas academicas. Todas as consultas utilizam
 * PDO parametrizado; DELETE e substituido por exclusao logica auditavel.
 */
final class TarefaRepository
{
    public function __construct(private readonly PDO $db)
    {
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
            . 't.situacao,t.vencimento,t.versao,t.criado_em,t.atualizado_em,'
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
        $q = $this->db->prepare(
            'INSERT INTO tarefa_acompanhamento '
            . '(seguro_mensal_id,titulo,descricao,prioridade,situacao,vencimento,criado_por,atualizado_por) '
            . 'VALUES (:seguro,:titulo,:descricao,:prioridade,:situacao,:vencimento,:usuario,:atualizador)'
        );
        $q->execute([
            'seguro' => $data['seguro_mensal_id'],
            'titulo' => $data['titulo'],
            'descricao' => $data['descricao'],
            'prioridade' => $data['prioridade'],
            'situacao' => $data['situacao'],
            'vencimento' => $data['vencimento'],
            'usuario' => $usuarioId,
            'atualizador' => $usuarioId,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * UPDATE por ID e versao: evita sobrescrever edicao concorrente.
     * @param array{titulo:string,descricao:string,prioridade:string,situacao:string,vencimento:?string,seguro_mensal_id:?int} $data
     */
    public function atualizar(int $id, int $versao, array $data, int $usuarioId): bool
    {
        $q = $this->db->prepare(
            'UPDATE tarefa_acompanhamento '
            . 'SET seguro_mensal_id=:seguro,titulo=:titulo,descricao=:descricao,'
            . 'prioridade=:prioridade,situacao=:situacao,vencimento=:vencimento,'
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
            'usuario' => $usuarioId,
            'id' => $id,
            'versao' => $versao,
        ]);
        return $q->rowCount() === 1;
    }

    /** Exclusao logica: o registro e a autoria permanecem auditaveis no MySQL. */
    public function excluir(int $id, int $versao, int $usuarioId): bool
    {
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
        return $q->rowCount() === 1;
    }
}
