<?php
declare(strict_types=1);

require_once '/var/www/src/Auth.php';
Auth::requireLogin();
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Cache-Control: no-store');
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; base-uri 'none'");

require_once '/var/www/src/TarefaService.php';
function escHist(mixed $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

try {
    $id = TarefaService::identificador($_GET['id'] ?? null);
    $repo = new TarefaRepository(Database::connection());
    $tarefa = $repo->buscarComArquivadas($id);
    if ($tarefa === null) {
        http_response_code(404);
    }
    $eventos = $tarefa !== null ? $repo->historico($id) : [];
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    $tarefa = null;
    $eventos = [];
} catch (Throwable $e) {
    error_log('Historico de tarefas: ' . $e->getMessage());
    http_response_code(503);
    exit('Historico indisponivel.');
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Histórico da tarefa — MonitorRenova</title>
<style>
body{font:15px/1.5 system-ui,Arial,sans-serif;max-width:1100px;margin:30px auto;padding:0 18px;background:#f5f8fb;color:#183047}
a{color:#075c73}.card{background:white;border:1px solid #d9e4eb;border-radius:12px;padding:20px;margin-top:18px}
.history-table-scroll{max-width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch;border:1px solid #e1e8ef;border-radius:8px}table{border-collapse:collapse;width:100%;min-width:720px}th,td{border-bottom:1px solid #e1e8ef;padding:12px 8px;text-align:left;vertical-align:top}
th{background:#eef4f8}small{color:#556b79}details{max-width:440px}pre{white-space:pre-wrap;overflow-wrap:anywhere;font-size:12px}
</style>
</head>
<body>
<a href="/tarefas.php">Voltar às tarefas</a>
<?php if ($tarefa === null): ?>
  <main class="card"><h1>Tarefa não encontrada</h1><p>Confira o identificador informado.</p></main>
<?php else: ?>
<main class="card">
  <h1>Histórico da tarefa #<?=escHist($tarefa['id'])?></h1>
  <p><strong><?=escHist($tarefa['titulo'])?></strong> — <?= $tarefa['excluido_em'] !== null ? 'Arquivada' : 'Ativa' ?></p>
  <p><small>Os eventos abaixo permanecem consultáveis após o arquivamento.</small></p>
  <div class="history-table-scroll" role="region" aria-label="Eventos de auditoria da tarefa" tabindex="0">
  <table>
    <thead><tr><th>Versão</th><th>Ação</th><th>Autor</th><th>Data</th><th>Alterações</th></tr></thead>
    <tbody>
    <?php foreach ($eventos as $evento): ?>
      <tr>
        <td><?=escHist($evento['versao'])?></td>
        <td><?=escHist(ucfirst((string) $evento['acao']))?></td>
        <td><?=escHist($evento['autor'])?> <small>(#<?=escHist($evento['usuario_id'])?>)</small></td>
        <td><?=escHist($evento['criado_em'])?></td>
        <td><details><summary>Ver estados</summary>
          <strong>Antes</strong>
          <pre><?=escHist($evento['dados_antes'] ?? '—')?></pre>
          <strong>Depois</strong>
          <pre><?=escHist($evento['dados_depois'])?></pre>
        </details></td>
      </tr>
    <?php endforeach; ?>
    <?php if ($eventos === []): ?><tr><td colspan="5">Nenhum evento registrado nesta tarefa.</td></tr><?php endif; ?>
    </tbody>
  </table>
  </div>
</main>
<?php endif; ?>
</body></html>
