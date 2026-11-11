<?php
declare(strict_types=1);
// Confere a renderizacao HTML da View com dados reais do MySQL.
// Teste CLI sem POST, sem gravacao e sem credenciais no output.
if (PHP_SAPI !== 'cli') { exit(1); }
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/TarefaService.php';

$repository = new TarefaRepository(Database::connection());
$erro = null;
$mensagem = null;
$formEnviado = null;
$editando = null;
$filtroSituacao = '';
$filtroBusca = '';
$dados = $repository->listar();
$rows = $dados['registros'];
$total = $dados['total'];
$pagina = $dados['pagina'];
$paginas = $dados['totalPaginas'];
$seguros = $repository->segurosParaAssociar();
$excluidas = $repository->contarExcluidas();

ob_start();
require __DIR__ . '/views/tarefas.php';
$html = ob_get_clean();
$testes = [
    'pagina_html' => str_contains($html, '<html lang="pt-BR">'),
    'form_criar' => str_contains($html, 'name="acao" value="criar"'),
    'filtro_consulta' => str_contains($html, 'Consultar tarefas (READ)'),
    'opcao_seguro' => str_contains($html, 'name="seguro_id"'),
    'tabela_sql' => str_contains($html, '<table class="task-table">'),
    'token_csrf' => str_contains($html, 'name="csrf"'),
    'link_historico' => str_contains((string) file_get_contents(__DIR__ . '/views/tarefas.php'), '/historico-tarefa.php?id='),
    'sem_html_cru_dados' => !str_contains($html, '<script>alert('),
];
$ok = count(array_filter($testes));
foreach($testes as $nome=>$passou) {
    echo ($passou ? '[OK] ' : '[FALHA] ') . $nome . PHP_EOL;
}
echo 'RENDER: ' . $ok . '/' . count($testes) . PHP_EOL;
exit($ok === count($testes) ? 0 : 1);
