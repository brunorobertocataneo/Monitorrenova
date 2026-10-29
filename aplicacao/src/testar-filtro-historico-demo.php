<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit(1); }
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/SeguroController.php';
$db = Database::connection();
$controller = new SeguroController(new SeguroRepository($db));
$tests = [];
function verificar(string $nome, bool $ok, mixed $observado): void {
    global $tests;
    $tests[$nome] = ['ok'=>$ok,'observado'=>$observado];
}
try {
    $atual = $controller->index([]);
    verificar('padrao_competencia_ativa', $atual['total'] === 50 && $atual['modoHistorico'] === false, $atual['total']);

    $out = $controller->index(['de'=>'2026-10-01','ate'=>'2026-10-31']);
    verificar('outubro_total', $out['total'] === 15, $out['total']);
    verificar('outubro_cartao', (int)$out['stats']['total'] === 15, $out['stats']['total']);
    verificar('outubro_todas_datas', count($out['rows']) === 15 && count(array_filter($out['rows'], static fn($r) => $r['vencimento'] >= '2026-10-01' && $r['vencimento'] <= '2026-10-31')) === 15, count($out['rows']));
    verificar('modo_historico_ativado', $out['modoHistorico'] === true, $out['modoHistorico']);
    verificar('outubro_personagem', $controller->index(['de'=>'2026-10-01','ate'=>'2026-10-31','busca'=>'Tanjiro Kamado'])['total'] === 1, 'Tanjiro Kamado');
    verificar('outubro_vendedor', $controller->index(['de'=>'2026-10-01','ate'=>'2026-10-31','vendedor'=>'Ada Lovelace'])['total'] === 3, 'Ada Lovelace');

    $nov = $controller->index(['de'=>'2026-11-01','ate'=>'2026-11-30']);
    verificar('novembro_total', $nov['total'] === 50, $nov['total']);
    verificar('novembro_cartao', (int)$nov['stats']['total'] === 50, $nov['stats']['total']);

    $ambos = $controller->index(['de'=>'2026-10-01','ate'=>'2026-11-30']);
    verificar('outubro_novembro_total', $ambos['total'] === 65, $ambos['total']);
    verificar('outubro_novembro_cartao', (int)$ambos['stats']['total'] === 65, $ambos['stats']['total']);
    verificar('outubro_novembro_paginacao', $ambos['totalPages'] === 3 && $controller->index(['de'=>'2026-10-01','ate'=>'2026-11-30','pagina'=>'3'])['total'] === 65 && count($controller->index(['de'=>'2026-10-01','ate'=>'2026-11-30','pagina'=>'3'])['rows']) === 5, $ambos['totalPages']);
    verificar('outubro_novembro_vendedor', $controller->index(['de'=>'2026-10-01','ate'=>'2026-11-30','vendedor'=>'Ada Lovelace'])['total'] === 13, 'Ada Lovelace');

    verificar('somente_data_inicial', $controller->index(['de'=>'2026-10-01'])['total'] === 65, '>= 01/10/2026');
    verificar('somente_data_final', $controller->index(['ate'=>'2026-10-31'])['total'] === 15, '<= 31/10/2026');
    verificar('periodo_invertido', $controller->index(['de'=>'2026-11-30','ate'=>'2026-10-01'])['total'] === 0, 'intervalo invertido');
} catch (Throwable $e) {
    verificar('erro_na_consulta', false, $e->getMessage());
}
$aprovados = count(array_filter($tests, static fn($item) => $item['ok']));
echo json_encode(['aprovados'=>$aprovados,'total'=>count($tests),'detalhes'=>$tests],JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) . PHP_EOL;
exit($aprovados === count($tests) ? 0 : 1);
