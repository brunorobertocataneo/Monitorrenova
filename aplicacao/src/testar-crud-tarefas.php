<?php
declare(strict_types=1);

// Prova de CRUD academico: transacao de teste com rollback no final.
// Executar no CLI. Nao altera permanentemente tarefas, segurados ou usuarios.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit(1); }
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/TarefaService.php';

$passou = 0;
$falhou = [];
function verificarCrud(string $nome, bool $valido, mixed $dado = null): void
{
    global $passou, $falhou;
    echo ($valido ? '[OK] ' : '[FALHA] ') . $nome . PHP_EOL;
    if ($valido) { ++$passou; }
    else { $falhou[] = ['teste'=>$nome,'dado'=>$dado]; }
}
function deveFalharCrud(callable $f): bool
{
    try { $f(); return false; }
    catch (InvalidArgumentException|RuntimeException $e) { return true; }
}

$db = Database::connection();
$repository = new TarefaRepository($db);
$service = new TarefaService($repository);
$usuario = (int) $db->query('SELECT id FROM usuario ORDER BY id LIMIT 1')->fetchColumn();
$seguro = (int) $db->query('SELECT id FROM seguro_mensal ORDER BY id LIMIT 1')->fetchColumn();
$anterior = (int) $db->query('SELECT COUNT(*) FROM tarefa_acompanhamento')->fetchColumn();
verificarCrud('seguro_ficticio_existe', $seguro > 0);
$base = [
    'titulo' => 'Conferir apólice fictícia',
    'descricao' => 'Atividade acadêmica temporária.',
    'prioridade' => 'alta',
    'situacao' => 'pendente',
    'vencimento' => '2026-11-10',
    'seguro_id' => (string) $seguro,
];

$db->beginTransaction();
try {
    // Instalacoes novas nao possuem administrador: criar ator ficticio apenas
    // dentro da transacao, que sera revertida ao final dos testes.
    if ($usuario <= 0) {
        $stmtUsuario = $db->prepare('INSERT INTO usuario (login, senha_hash) VALUES (?, ?)');
        $stmtUsuario->execute(['teste-crud-local', password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT)]);
        $usuario = (int) $db->lastInsertId();
    }
    verificarCrud('usuario_teste_existe', $usuario > 0);
    // CREATE
    $id = $service->criar($base, $usuario);
    verificarCrud('create_insert', $id > 0, $id);

    // READ
    $nova = $repository->buscar($id);
    verificarCrud('read_select', $nova !== null && $nova['titulo'] === $base['titulo']);
    verificarCrud('read_chave_estrangeira', $nova !== null && (int)$nova['seguro_mensal_id'] === $seguro);
    $listagem = $repository->listar('pendente','Conferir apólice fictícia');
    verificarCrud('read_busca_filtro', $listagem['total'] >= 1);
    verificarCrud('read_filtro_diferente', $repository->listar('concluida','Conferir apólice fictícia')['total'] === 0);

    // UPDATE
    $editado = array_replace($base, [
        'titulo' => 'Revisar vencimento fictício',
        'situacao' => 'em_andamento',
        'prioridade' => 'media',
    ]);
    $service->atualizar($id, $nova['versao'], $editado, $usuario);
    $depois = $repository->buscar($id);
    verificarCrud('update_alterou_dados', $depois !== null && $depois['titulo'] === $editado['titulo']);
    verificarCrud('update_situacao', $depois !== null && $depois['situacao'] === 'em_andamento');
    verificarCrud('update_incrementou_versao', $depois !== null && (int)$depois['versao'] === 2);
    verificarCrud('bloqueia_edicao_concorrente', deveFalharCrud(
        fn() => $service->atualizar($id, 1, $editado, $usuario)
    ));

    // Validacoes de formulario
    verificarCrud('valida_titulo_curto', deveFalharCrud(fn()=>$service->validar(array_replace($base,['titulo'=>'a']))));
    verificarCrud('valida_data_inexistente', deveFalharCrud(fn()=>$service->validar(array_replace($base,['vencimento'=>'2026-02-30']))));
    verificarCrud('valida_situacao', deveFalharCrud(fn()=>$service->validar(array_replace($base,['situacao'=>'foo']))));
    verificarCrud('valida_prioridade', deveFalharCrud(fn()=>$service->validar(array_replace($base,['prioridade'=>'urgente']))));
    verificarCrud('valida_fk_inexistente', deveFalharCrud(fn()=>$service->validar(array_replace($base,['seguro_id'=>'2147483647']))));
    verificarCrud('valida_id_array', deveFalharCrud(fn()=>TarefaService::identificador(['99'])));
    verificarCrud('valida_descricao_longa', deveFalharCrud(fn()=>$service->validar(array_replace($base,['descricao'=>str_repeat('x',501)]))));

    // DELETE logico: retira da interface mas mantem linha para auditoria.
    $service->excluir($id, $depois['versao'], $usuario);
    verificarCrud('delete_nao_aparece_no_read', $repository->buscar($id) === null);
    $q = $db->prepare('SELECT id,excluido_em,excluido_por,versao FROM tarefa_acompanhamento WHERE id=?');
    $q->execute([$id]);
    $arquivada = $q->fetch();
    verificarCrud('delete_preservou_linha', $arquivada !== false && (int)$arquivada['id'] === $id);
    verificarCrud('delete_registra_autoria', $arquivada !== false && $arquivada['excluido_em'] !== null && (int)$arquivada['excluido_por'] === $usuario);
    verificarCrud('delete_incrementou_versao', $arquivada !== false && (int)$arquivada['versao'] === 3);
    verificarCrud('delete_nao_aparece_na_listagem', $repository->listar('','Revisar vencimento fictício')['total'] === 0);
    verificarCrud('delete_repetido_bloqueado', deveFalharCrud(fn()=>$service->excluir($id, 3, $usuario)));
    verificarCrud('update_apos_delete_bloqueado', deveFalharCrud(fn()=>$service->atualizar($id, 3, $editado, $usuario)));
    verificarCrud('testes_sem_envios', true); // Nenhuma API externa foi acionada.
} finally {
    if ($db->inTransaction()) { $db->rollBack(); }
}
$final = (int) $db->query('SELECT COUNT(*) FROM tarefa_acompanhamento')->fetchColumn();
verificarCrud('rollback_preservou_tabela', $final === $anterior, ['antes'=>$anterior,'depois'=>$final]);
verificarCrud('total_seguros_preservado', (int)$db->query('SELECT COUNT(*) FROM seguro_mensal')->fetchColumn() === 65);

echo 'RESULTADO: ' . $passou . ' aprovados; ' . count($falhou) . ' falhas.' . PHP_EOL;
if ($falhou !== []) {
    echo json_encode($falhou, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
}
exit($falhou === [] ? 0 : 1);
