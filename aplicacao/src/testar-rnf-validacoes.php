<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { exit(1); }
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/TarefaService.php';

$db = Database::connection();
$repo = new TarefaRepository($db);
$service = new TarefaService($repo);
$ok = 0;
$fail = [];
function assertRNF(string $name, bool $result): void {
    global $ok, $fail;
    echo ($result ? '[OK] ' : '[FALHA] ') . $name . PHP_EOL;
    if ($result) { ++$ok; } else { $fail[] = $name; }
}
function rejected(callable $task): bool {
    try { $task(); return false; } catch (InvalidArgumentException|RuntimeException $e) { return true; }
}
function countsRNF(PDO $db): array {
    $out = [];
    foreach (['usuario','tarefa_acompanhamento','historico_tarefa_acompanhamento'] as $name) {
        $out[$name] = (int)$db->query('SELECT COUNT(*) FROM ' . $name)->fetchColumn();
    }
    return $out;
}
$before = countsRNF($db);
$db->beginTransaction();
try {
    $q = $db->prepare('INSERT INTO usuario (login,senha_hash) VALUES (?,?)');
    $q->execute(['test-rnf-' . bin2hex(random_bytes(6)), password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT)]);
    $user = (int)$db->lastInsertId();
    $base = [
        'titulo'=>'Validar regras de entrada',
        'descricao'=>'Teste com rollback',
        'prioridade'=>'media',
        'situacao'=>'pendente',
        'vencimento'=>'',
        'seguro_id'=>'',
    ];
    $id = $service->criar($base, $user);
    $state = $repo->buscar($id);
    assertRNF('RNF01 titulo abaixo do minimo', rejected(fn()=>$service->atualizar($id, 1, array_replace($base, ['titulo'=>'a']), $user)));
    assertRNF('RNF01 titulo acima do maximo', rejected(fn()=>$service->atualizar($id, 1, array_replace($base, ['titulo'=>str_repeat('A',121)]), $user)));
    assertRNF('RNF01 data inexistente', rejected(fn()=>$service->atualizar($id, 1, array_replace($base, ['vencimento'=>'2026-02-30']), $user)));
    assertRNF('RNF01 data formato invalido', rejected(fn()=>$service->atualizar($id, 1, array_replace($base, ['vencimento'=>'10/11/2026']), $user)));
    assertRNF('RNF01 prioridade fora dos valores', rejected(fn()=>$service->atualizar($id, 1, array_replace($base, ['prioridade'=>'urgente']), $user)));
    assertRNF('RNF01 situacao fora dos valores', rejected(fn()=>$service->atualizar($id, 1, array_replace($base, ['situacao'=>'cancelada']), $user)));
    assertRNF('RNF01 seguro inexistente', rejected(fn()=>$service->atualizar($id, 1, array_replace($base, ['seguro_id'=>'2147483647']), $user)));
    assertRNF('RNF01 descricao longa', rejected(fn()=>$service->atualizar($id, 1, array_replace($base, ['descricao'=>str_repeat('D',501)]), $user)));
    assertRNF('RNF01 nenhuma edicao invalida persistida', (int)($repo->buscar($id)['versao'] ?? 0) === 1 && count($repo->historico($id))===1);
    assertRNF('RNF01 vencimento e seguro opcionais', $state!==null && $state['vencimento']===null && $state['seguro_mensal_id']===null);
    $base['titulo']='Versao dois atualizada';
    $service->atualizar($id,1,$base,$user);
    assertRNF('RNF05 update version antiga rejeitada', rejected(fn()=>$service->atualizar($id,1,$base,$user)));
    assertRNF('RNF05 archive versao antiga rejeitada', rejected(fn()=>$service->excluir($id,1,$user)));
    assertRNF('RNF05 sem sobrescrita nem novo evento', (int)$repo->buscar($id)['versao']===2 && count($repo->historico($id))===2);
    $service->excluir($id,2,$user);
    assertRNF('RNF05 arquivamento correto', $repo->buscar($id)===null && $repo->buscarComArquivadas($id)!==null);
    assertRNF('RNF05 arquivo versao antiga rejeitado', rejected(fn()=>$service->excluir($id,2,$user)));
    assertRNF('RNF05 historico preservado', count($repo->historico($id))===3);
} finally {
    if ($db->inTransaction()) { $db->rollBack(); }
}
assertRNF('SUITE rollback preserva todas contagens', countsRNF($db)===$before);
echo 'RNF NEGATIVOS: ' . $ok . ' aprovados; ' . count($fail) . ' falhas.' . PHP_EOL;
exit($fail===[] ? 0 : 1);
