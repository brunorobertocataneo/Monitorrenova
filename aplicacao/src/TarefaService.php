<?php
declare(strict_types=1);

require_once __DIR__ . '/TarefaRepository.php';

/**
 * Regras de negocio independentes do HTML e do SQL.
 * Escopo exclusivo do CRUD academico, sem modificar apolices.
 */
final class TarefaService
{
    public const PRIORIDADES = [
        'baixa' => 'Baixa',
        'media' => 'Média',
        'alta' => 'Alta',
    ];
    public const SITUACOES = [
        'pendente' => 'Pendente',
        'em_andamento' => 'Em andamento',
        'concluida' => 'Concluída',
    ];

    public function __construct(private readonly TarefaRepository $repository)
    {
    }

    /** @return array{titulo:string,descricao:string,prioridade:string,situacao:string,vencimento:?string,seguro_mensal_id:?int} */
    public function validar(array $form): array
    {
        $titulo = $this->texto($form['titulo'] ?? null, 120, 'Título', 3);
        $descricao = $this->texto($form['descricao'] ?? null, 500, 'Descrição');
        $prioridade = $form['prioridade'] ?? null;
        $situacao = $form['situacao'] ?? null;
        if (!is_string($prioridade) || !isset(self::PRIORIDADES[$prioridade])) {
            throw new InvalidArgumentException('Selecione uma prioridade válida.');
        }
        if (!is_string($situacao) || !isset(self::SITUACOES[$situacao])) {
            throw new InvalidArgumentException('Selecione uma situação válida.');
        }

        $date = $form['vencimento'] ?? '';
        if (!is_string($date)) {
            throw new InvalidArgumentException('Data inválida.');
        }
        $date = trim($date);
        if ($date !== '') {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/D', $date)) {
                throw new InvalidArgumentException('Informe uma data válida.');
            }
            $obj = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
            if (!$obj || $obj->format('Y-m-d') !== $date) {
                throw new InvalidArgumentException('A data informada não existe no calendário.');
            }
        }

        $seguroId = $form['seguro_id'] ?? '';
        if (!is_string($seguroId) && !is_int($seguroId)) {
            throw new InvalidArgumentException('Seguro vinculado inválido.');
        }
        $seguroId = trim((string) $seguroId);
        $seguro = null;
        if ($seguroId !== '') {
            $seguro = self::identificador($seguroId);
            if (!$this->repository->seguroExiste($seguro)) {
                throw new InvalidArgumentException('O seguro selecionado não existe.');
            }
        }
        return [
            'titulo' => $titulo,
            'descricao' => $descricao,
            'prioridade' => $prioridade,
            'situacao' => $situacao,
            'vencimento' => $date === '' ? null : $date,
            'seguro_mensal_id' => $seguro,
        ];
    }

    public static function identificador(mixed $valor): int
    {
        if (!is_int($valor) && !is_string($valor)) {
            throw new InvalidArgumentException('Identificador inválido.');
        }
        $val = (string) $valor;
        if (!preg_match('/^[1-9]\d{0,9}$/D', $val) || (int) $val > 2147483647) {
            throw new InvalidArgumentException('Identificador inválido.');
        }
        return (int) $val;
    }

    public function criar(array $form, int $usuarioId): int
    {
        self::exigirUsuario($usuarioId);
        return $this->repository->criar($this->validar($form), $usuarioId);
    }

    public function atualizar(mixed $id, mixed $versao, array $form, int $usuarioId): void
    {
        self::exigirUsuario($usuarioId);
        $tarefaId = self::identificador($id);
        $numeroVersao = self::identificador($versao);
        $dados = $this->validar($form);
        if (!$this->repository->atualizar($tarefaId, $numeroVersao, $dados, $usuarioId)) {
            throw new RuntimeException('Tarefa alterada ou excluída. Recarregue a página antes de salvar.');
        }
    }

    public function excluir(mixed $id, mixed $versao, int $usuarioId): void
    {
        self::exigirUsuario($usuarioId);
        $tarefaId = self::identificador($id);
        $numeroVersao = self::identificador($versao);
        if (!$this->repository->excluir($tarefaId, $numeroVersao, $usuarioId)) {
            throw new RuntimeException('Tarefa alterada ou já excluída. Recarregue a página.');
        }
    }

    private function texto(mixed $valor, int $max, string $nome, int $min = 0): string
    {
        if (!is_string($valor)) {
            throw new InvalidArgumentException($nome . ' inválido.');
        }
        $valor = trim($valor);
        $len = mb_strlen($valor, 'UTF-8');
        if ($len < $min || $len > $max || preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f]/', $valor)) {
            throw new InvalidArgumentException($nome . ' deve ter entre ' . $min . ' e ' . $max . ' caracteres válidos.');
        }
        return $valor;
    }

    private static function exigirUsuario(int $usuarioId): void
    {
        if ($usuarioId < 1) {
            throw new InvalidArgumentException('Usuário não autenticado.');
        }
    }
}
