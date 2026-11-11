<?php
declare(strict_types=1);
function h(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
$valor = static function (string $campo, string $padrao = '') use ($formEnviado, $editando): string {
    $fonte = $formEnviado !== null && ($formEnviado['acao'] ?? null) !== 'excluir'
        ? $formEnviado : ($editando ?? []);
    $v = $fonte[$campo] ?? $padrao;
    return is_string($v) || is_int($v) ? (string) $v : '';
};
$emEdicao = $editando !== null;
$csrfToken = Auth::csrf();
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>CRUD de Tarefas — MonitorRenova</title>
<link rel="stylesheet" href="/assets/app.css?v=1">
<style>
.task-wrap{max-width:1200px;margin:0 auto;padding:6px 0 35px}
.task-panel{background:white;border:1px solid #dbe4ee;border-radius:12px;padding:22px;margin:0 0 18px}
.task-panel h2{margin:0 0 16px;font-size:19px;color:#173654}
.task-note{padding:15px;border:1px solid #ccddef;background:#f4f8ff;border-radius:9px;line-height:1.55;margin-bottom:17px}
.task-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
.task-field{display:flex;flex-direction:column;gap:6px}
.task-field.wide{grid-column:1/-1}
.task-field label{font-size:13px;font-weight:650;color:#263c55}
.task-field input,.task-field select,.task-field textarea{width:100%;border:1px solid #bacbdb;border-radius:8px;padding:11px;font:inherit;background:white;color:#193550}
.task-field textarea{min-height:90px;resize:vertical}
.task-actions{display:flex;gap:12px;flex-wrap:wrap;align-items:center;margin-top:16px}
.task-btn{border:0;background:#205f93;color:white;border-radius:8px;padding:11px 18px;font:inherit;font-weight:650;cursor:pointer}
.task-btn.danger{background:#a93c3c}
.task-link{color:#125d9a;font-weight:600}
.task-error{background:#fff0f0;border:1px solid #e7baba;color:#8c3030;padding:13px;border-radius:8px;margin:0 0 16px}
.task-ok{background:#eaf7f0;border:1px solid #b6dcc9;color:#1f6044;padding:13px;border-radius:8px;margin:0 0 16px}
.task-kpis{display:flex;gap:15px;flex-wrap:wrap;margin-bottom:18px}
.task-kpi{background:white;border:1px solid #dbe4ee;border-radius:9px;padding:15px 20px;flex:1;min-width:170px}
.task-kpi strong{display:block;font-size:25px;margin-top:5px;color:#173654}
.task-tablewrap{overflow-x:auto}
.task-table{width:100%;border-collapse:collapse;min-width:850px}
.task-table th,.task-table td{padding:12px 9px;border-bottom:1px solid #e1eaf2;text-align:left;vertical-align:top}
.task-table th{font-size:13px;color:#61738a}
.task-table small{display:block;font-size:12px;color:#63758d}
.task-status{border-radius:10px;background:#edf3f8;padding:5px 9px;display:inline-block;white-space:nowrap}
.task-delete summary{cursor:pointer;color:#9b3939;text-decoration:underline}
.task-delete form{padding:8px 0}
.task-pages{display:flex;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-top:15px}
.task-filter{display:flex;gap:12px;align-items:end;flex-wrap:wrap}
.task-filter .task-field{min-width:180px;flex:1}
@media(max-width:750px){.task-grid{grid-template-columns:1fr}.task-field.wide{grid-column:auto}.task-panel{padding:16px}}
</style>
</head>
<body class="app-shell">
<?php $activePage = 'tarefas'; require __DIR__ . '/partials/sidebar.php'; ?>
<div class="app-content">
  <div class="page-topbar"><span class="topbar-kicker">MonitorRenova / CRUD acadêmico</span><a class="mini-link" href="/">← Visão geral</a></div>
  <main class="page-content">
    <div class="page-titlebar">
      <div>
        <div class="eyebrow">Programação II • PHP + PDO + MySQL</div>
        <h1>Tarefas de Acompanhamento</h1>
        <p>Cadastre, consulte, edite e arquive tarefas associadas a seguros fictícios.</p>
      </div>
    </div>
    <div class="task-wrap">
      <div class="task-note">
        <strong>CRUD completo:</strong> criar (INSERT), consultar (SELECT), atualizar (UPDATE) e excluir logicamente
        (UPDATE com data de arquivamento). Nenhuma apólice é apagada ou modificada.
        Esta funcionalidade não faz cobranças nem envia mensagens.
      </div>

      <?php if ($erro): ?><div class="task-error" role="alert"><?=h($erro)?></div><?php endif; ?>
      <?php if ($mensagem): ?><div class="task-ok" role="status"><?=h($mensagem)?></div><?php endif; ?>

      <div class="task-kpis">
        <div class="task-kpi"><span>Registros ativos com os filtros</span><strong><?=h($total)?></strong></div>
        <div class="task-kpi"><span>Arquivados (preservados)</span><strong><?=h($excluidas)?></strong></div>
      </div>

      <section class="task-panel" id="formulario">
        <h2><?= $emEdicao ? 'Editar tarefa #'.h($editando['id']) : 'Cadastrar nova tarefa' ?></h2>
        <form method="post" action="/tarefas.php<?= $emEdicao ? '?editar='.h($editando['id']) : '' ?>">
          <input type="hidden" name="csrf" value="<?=h($csrfToken)?>">
          <input type="hidden" name="acao" value="<?= $emEdicao ? 'atualizar' : 'criar' ?>">
          <?php if ($emEdicao): ?>
            <input type="hidden" name="id" value="<?=h($editando['id'])?>">
            <input type="hidden" name="versao" value="<?=h($editando['versao'])?>">
          <?php endif; ?>
          <div class="task-grid">
            <div class="task-field wide">
              <label for="titulo">Título da tarefa (3 a 120 caracteres) *</label>
              <input id="titulo" name="titulo" required minlength="3" maxlength="120" value="<?=h($valor('titulo'))?>" placeholder="Ex.: Conferir vencimento de Itachi Uchiha">
            </div>
            <div class="task-field wide">
              <label for="descricao">Descrição (até 500 caracteres)</label>
              <textarea id="descricao" name="descricao" maxlength="500" placeholder="Anotação fictícia, sem dados pessoais reais."><?=h($valor('descricao'))?></textarea>
            </div>
            <div class="task-field">
              <label for="prioridade">Prioridade *</label>
              <select id="prioridade" name="prioridade" required>
                <?php foreach (TarefaService::PRIORIDADES as $chave=>$titulo): ?>
                  <option value="<?=h($chave)?>" <?= $valor('prioridade','media') === $chave ? 'selected' : '' ?>><?=h($titulo)?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="task-field">
              <label for="situacao">Situação *</label>
              <select id="situacao" name="situacao" required>
                <?php foreach (TarefaService::SITUACOES as $chave=>$titulo): ?>
                  <option value="<?=h($chave)?>" <?= $valor('situacao','pendente') === $chave ? 'selected' : '' ?>><?=h($titulo)?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="task-field">
              <label for="vencimento">Prazo da tarefa (opcional)</label>
              <input id="vencimento" type="date" name="vencimento" value="<?=h($valor('vencimento'))?>">
            </div>
            <div class="task-field">
              <label for="seguro_id">Relacionar com seguro fictício (opcional)</label>
              <select id="seguro_id" name="seguro_id">
                <option value="">Sem vínculo</option>
                <?php foreach ($seguros as $seguro): ?>
                  <option value="<?=h($seguro['id'])?>" <?= $valor('seguro_id',$valor('seguro_mensal_id')) === (string)$seguro['id'] ? 'selected' : '' ?>><?=h($seguro['segurado'].' — '.$seguro['competencia'])?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="task-actions">
            <button class="task-btn" type="submit"><?= $emEdicao ? 'Salvar alterações' : 'Cadastrar tarefa' ?></button>
            <?php if ($emEdicao): ?><a class="task-link" href="/tarefas.php">Cancelar edição</a><?php endif; ?>
          </div>
        </form>
      </section>

      <section class="task-panel">
        <h2>Consultar tarefas (READ)</h2>
        <form class="task-filter" method="get" action="/tarefas.php">
          <div class="task-field"><label for="busca">Buscar pelo título</label><input id="busca" name="busca" maxlength="100" value="<?=h($filtroBusca)?>" placeholder="Digite parte do título"></div>
          <div class="task-field"><label for="filtroSituacao">Situação</label>
            <select id="filtroSituacao" name="situacao"><option value="">Todas</option>
              <?php foreach (TarefaService::SITUACOES as $chave=>$titulo): ?>
                <option value="<?=h($chave)?>" <?= $filtroSituacao === $chave ? 'selected' : '' ?>><?=h($titulo)?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="submit" class="task-btn">Filtrar</button>
          <a href="/tarefas.php" class="task-link">Limpar</a>
        </form>
      </section>

      <section class="task-panel">
        <h2>Lista de tarefas (<?=h($total)?>)</h2>
        <div class="task-tablewrap">
          <table class="task-table">
            <thead><tr><th>#</th><th>Tarefa e seguro</th><th>Prioridade</th><th>Prazo</th><th>Situação</th><th>Alteração</th><th>Ações</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td><?=h($r['id'])?></td>
                <td><strong><?=h($r['titulo'])?></strong>
                  <?php if ($r['descricao'] !== ''): ?><small><?=h($r['descricao'])?></small><?php endif; ?>
                  <small><?= $r['segurado'] ? 'Seguro: '.h($r['segurado']).' ('.h($r['competencia']).')' : 'Sem seguro vinculado' ?></small>
                </td>
                <td><?=h(TarefaService::PRIORIDADES[$r['prioridade']] ?? '—')?></td>
                <td><?=h($r['vencimento'] ?: 'Sem prazo')?></td>
                <td><span class="task-status"><?=h(TarefaService::SITUACOES[$r['situacao']] ?? '—')?></span></td>
                <td><small><?=h($r['atualizado_em'])?></small></td>
                <td>
                  <a class="task-link" href="/tarefas.php?editar=<?=h($r['id'])?>#formulario">Editar</a>
                  <a class="task-link" href="/historico-tarefa.php?id=<?=h($r['id'])?>">Histórico</a>
                  <details class="task-delete">
                    <summary>Arquivar</summary>
                    <form method="post" action="/tarefas.php">
                      <input type="hidden" name="csrf" value="<?=h($csrfToken)?>">
                      <input type="hidden" name="acao" value="excluir">
                      <input type="hidden" name="id" value="<?=h($r['id'])?>">
                      <input type="hidden" name="versao" value="<?=h($r['versao'])?>">
                      <button type="submit" class="task-btn danger">Confirmar</button>
                    </form>
                  </details>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if ($rows === []): ?><tr><td colspan="7">Nenhuma tarefa encontrada. Cadastre uma acima para demonstrar o CRUD.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
        <div class="task-pages">
          <span>Página <?=h($pagina)?> de <?=h($paginas)?> — 20 tarefas por página</span>
          <div>
            <?php if ($pagina > 1): ?><a class="task-link" href="/tarefas.php?<?=h(http_build_query(['pagina'=>$pagina-1,'situacao'=>$filtroSituacao,'busca'=>$filtroBusca]))?>">← Anterior</a><?php endif; ?>
            <?php if ($pagina < $paginas): ?><a class="task-link" href="/tarefas.php?<?=h(http_build_query(['pagina'=>$pagina+1,'situacao'=>$filtroSituacao,'busca'=>$filtroBusca]))?>">Próxima →</a><?php endif; ?>
          </div>
        </div>
      </section>
    </div>
  </main>
</div>
</body>
</html>
