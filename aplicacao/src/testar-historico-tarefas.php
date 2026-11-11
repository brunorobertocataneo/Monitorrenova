<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { exit(1); }
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/TarefaService.php';

$db = Database::connection();
$repo = new TarefaRepository($db);
$service = new TarefaService($repo);
$passaram = 0;
$falharam = [];
function validarHistorico(string $titulo, bool $passou): void {
    global $passaram, $falharam;
    echo ($passou ? '[OK] ' : '[FALHA] ') . $titulo . PHP_EOL;
    if ($passou) { ++$passaram; } else { $falharam[] = $titulo; }
}
$antes = [
    'tarefas' => (int) $db->query('SELECT COUNT(*) FROM tarefa_acompanhamento')->fetchColumn(),
    'eventos' => (int) $db->query('SELECT COUNT(*) FROM historico_tarefa_acompanhamento')->fetchColumn(),
    'usuarios' => (int) $db->query('SELECT COUNT(*) FROM usuario')->fetchColumn(),
];
$db->beginTransaction();
try {
    $login = 'teste-auditoria-' . bin2hex(random_bytes(5));
    $q = $db->prepare('INSERT INTO usuario (login,senha_hash) VALUES (?,?)');
    $q->execute([$login, password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT)]);
    $usuario = (int) $db->lastInsertId();
    $dados = [
        'titulo'=>'Auditoria temporaria',
        'descricao'=>'Teste academico revertido no fim',
        'prioridade'=>'media',
        'situacao'=>'pendente',
        'vencimento'=>'',
        'seguro_id'=>'',
    ];
    $id = $service->criar($dados, $usuario);
    $eventos = $repo->historico($id);
    validarHistorico('criacao_registra_1_evento', count($eventos) === 1);
    validarHistorico('criacao_autor_e_data', (int)($eventos[0]['usuario_id']??0) === $usuario && ($eventos[0]['autor']??'') === $login && !empty($eventos[0]['criado_em']));
    validarHistorico('criacao_versao_e_snapshot', ($eventos[0]['acao']??'') === 'criacao' && (int)($eventos[0]['versao']??0) === 1 && ($eventos[0]['dados_antes']??null) === null && json_decode((string)$eventos[0]['dados_depois'],true)['titulo'] === $dados['titulo']);
    $dados['titulo'] = 'Auditoria temporaria editada';
    $service->atualizar($id, 1, $dados, $usuario);
    $eventos = $repo->historico($id);
    validarHistorico('edicao_registra_2_eventos', count($eventos) === 2);
    validarHistorico('edicao_snapshots', ($eventos[1]['acao']??'') === 'edicao' && (int)($eventos[1]['versao']??0) === 2 && json_decode((string)$eventos[1]['dados_antes'],true)['titulo'] === 'Auditoria temporaria' && json_decode((string)$eventos[1]['dados_depois'],true)['titulo'] === $dados['titulo']);
    try { $service->atualizar($id, 1, $dados, $usuario); validarHistorico('conflito_sem_evento',false); }
    catch (RuntimeException $e) { validarHistorico('conflito_sem_evento',count($repo->historico($id)) === 2); }
    $service->excluir($id, 2, $usuario);
    $eventos = $repo->historico($id);
    validarHistorico('arquivamento_registra_3_eventos', count($eventos) === 3);
    validarHistorico('arquivamento_autoria_e_estado', ($eventos[2]['acao']??'') === 'arquivamento' && (int)($eventos[2]['versao']??0) === 3 && (int)($eventos[2]['usuario_id']??0) === $usuario && json_decode((string)$eventos[2]['dados_depois'],true)['excluido_em'] !== null);
    validarHistorico('arquivada_permanece_consultavel', $repo->buscar($id) === null && $repo->buscarComArquivadas($id) !== null && count($repo->historico($id)) === 3);
} finally {
    if ($db->inTransaction()) { $db->rollBack(); }
}
$depois = [
    'tarefas' => (int) $db->query('SELECT COUNT(*) FROM tarefa_acompanhamento')->fetchColumn(),
    'eventos' => (int) $db->query('SELECT COUNT(*) FROM historico_tarefa_acompanhamento')->fetchColumn(),
    'usuarios' => (int) $db->query('SELECT COUNT(*) FROM usuario')->fetchColumn(),
];
validarHistorico('rollback_preserva_tabelas', $depois === $antes);
echo "HISTORICO: $passaram aprovados; " . count($falharam) . " falhas." . PHP_EOL;
exit($falharam === [] ? 0 : 1);
