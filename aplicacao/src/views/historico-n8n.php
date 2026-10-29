<?php
/**
 * View do módulo historico-n8n. Apenas apresentação e escape HTML.
 * Os Controllers e o Repository fazem a validação e o acesso ao banco.
 */
function h(mixed $valor):string {
    return htmlspecialchars((string)$valor,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
}
?><!doctype html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Histórico n8n — MonitorRenova</title>
<style>
*{box-sizing:border-box}
body{font:15px system-ui,-apple-system,"Segoe UI",sans-serif;background:#f2f5fa;color:#192e46;margin:0}
header{background:#132b47;color:#fff;padding:25px max(20px,calc((100vw - 1120px)/2))}
header a{color:#cbe3ff}h1{font-size:25px;margin:9px 0 5px}
main{max-width:1150px;padding:0 20px;margin:23px auto}
.card{background:#fff;border:1px solid #dfe7f0;border-radius:12px;padding:21px;margin-bottom:18px}
.muted{color:#65758a}.intro{background:#fff7e5;border:1px solid #e8d8b4;border-radius:10px;padding:14px;margin-bottom:18px}
.metrics{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}
.metric{display:block;font-size:26px;font-weight:750;margin-top:6px}
.tablewrap{overflow-x:auto}table{width:100%;border-collapse:collapse;min-width:850px}
th,td{text-align:left;border-bottom:1px solid #e9eef5;padding:12px 10px;font-size:13px}
th{background:#f7f9fc;color:#58708b;font-weight:650}
.status{display:inline-block;padding:4px 8px;border-radius:20px;background:#eef2f7;color:#40536c;font-size:12px}
.status.success{background:#e6f4eb;color:#286e45}
.status.error{background:#fce9e9;color:#9f3131}
.status.running{background:#fff1d5;color:#775518}
a{color:#125ca3}.actions{display:flex;gap:15px;flex-wrap:wrap;align-items:end}
label{display:block;font-size:13px;margin-bottom:5px}
select,button{font:inherit;border:1px solid #c7d4e2;border-radius:8px;padding:9px;background:white}
button{background:#155a92;color:white;border-color:#155a92;cursor:pointer}
@media(max-width:700px){.metrics{grid-template-columns:1fr}}
</style>
<link rel="stylesheet" href="/assets/app.css?v=1">
<style>
/* Ajustes mínimos: conteúdo original preservado dentro da nova estrutura */
.app-shell .module-layout .card,.app-shell .module-layout .panel{box-shadow:0 3px 14px #19365009}
.app-shell .module-layout{min-width:0}
.app-shell .module-layout table{min-width:660px}
.app-shell .module-layout .stats,.app-shell .module-layout .grid,.app-shell .module-layout .metrics{gap:13px}
.app-shell .module-layout h2{font-size:18px}
.app-shell .module-layout a:focus-visible{outline:3px solid #61aac4;outline-offset:2px}
@media(max-width:700px){.app-shell .module-layout .grid{grid-template-columns:1fr}.app-shell .module-layout .stats{grid-template-columns:1fr}.app-shell .module-layout .metrics{grid-template-columns:repeat(2,minmax(0,1fr))}}
</style>
</head>
<body class="app-shell">
<?php $activePage='n8n'; require __DIR__.'/partials/sidebar.php'; ?>
<div class="app-content">
<div class="page-topbar"><span class="topbar-kicker">MonitorRenova / Monitoramento de automações</span><a class="mini-link" href="/">← Visão geral</a></div>
<main class="page-content">
<div class="page-titlebar"><div><div class="eyebrow">Monitoramento de automações</div><h1>Histórico de execuções do n8n</h1><p>Execuções de WhatsApp e sincronização, com evidências reais do MySQL.</p></div></div>
<div class="module-layout">
<div class="intro">
<strong>Importante:</strong> “Aceito pela Evolution API” significa que a API aceitou
a solicitação, mas não comprova entrega ou leitura no celular.
“Sem encerramento registrado” indica que o n8n não salvou o resultado final.
Quando houver ID de execução vinculado à transação, o painel exibirá
“MySQL atualizado e confirmado”, independentemente desse status do n8n.
A tela não dispara mensagens e não confirma o estado atual das apólices.
</div>
<div class="metrics">
<section class="card"><span class="muted">Execuções monitoradas</span><strong class="metric"><?=h($resumo['total'])?></strong></section>
<section class="card"><span class="muted">Aceites da API registrados</span><strong class="metric"><?=h($resumo['aceitosApi'])?></strong></section>
<section class="card"><span class="muted">Sincronizações confirmadas no MySQL</span><strong class="metric"><?=h($resumo['mysqlConfirmadas'])?></strong></section>
<section class="card"><span class="muted">Falhas no n8n</span><strong class="metric"><?=h($resumo['falhas'])?></strong></section>
</div>
<section class="card">
<h2>Coleta do histórico</h2>
<p>
<strong><?=h($resumo['coletadoEm']??'Ainda não realizada')?></strong> (horário de Brasília).
<?php if(!$resumo['coletaRecente']):?>
<span class="status running">Coleta desatualizada</span>
<?php else:?>
<span class="status success">Coleta recente</span>
<?php endif;?>
</p>
<p class="muted">O coletor local transfere somente metadados para o MySQL. Os dados não são uma consulta ao vivo do n8n.</p>
</section>
<section class="card">
<h2>Execuções recentes</h2>
<form method="get" class="actions">
<div>
<label for="workflow">Automação</label>
<select id="workflow" name="workflow">
<option value="">Todas</option>
<?php foreach(N8nHistoricoRepository::WORKFLOW_NOMES as $id=>$nome):?>
<option value="<?=h($id)?>" <?=$id===$workflow?'selected':''?>><?=h($nome)?></option>
<?php endforeach;?>
</select>
</div>
<button type="submit">Filtrar</button>
<a href="/historico-n8n.php">Limpar filtro</a>
</form>
<div class="tablewrap">
<table>
<thead><tr><th>ID n8n</th><th>Automação</th><th>Origem</th><th>Início</th><th>Fim</th><th>Situação no n8n</th><th>Resultado / evidência</th><th>Commit MySQL</th></tr></thead>
<tbody>
<?php foreach($execucoes as $e):?>
<tr>
<td>#<?=h($e['execution_id'])?></td>
<td><?=h(N8nHistoricoRepository::nomeWorkflow($e['workflow_id']))?></td>
<td><?=h($e['origemHistorico']==='mysql'?'Histórico MySQL':($e['origem']==='trigger'?'Agendado':($e['origem']==='cli'?'Teste CLI':$e['origem'])))?></td>
<td><?=h($e['iniciado_em'])?></td>
<td><?=h($e['terminado_em']??'—')?></td>
<td><span class="status <?=h($e['situacao'])?>"><?=h(N8nHistoricoRepository::situacao($e['situacao']))?></span></td>
<td><?=h(N8nHistoricoRepository::resultado($e['resultado']))?></td>
<td><?=h($e['mysqlConfirmadoEm']??'—')?><?php if($e['mysqlRegistros']!==null):?> (<?=h($e['mysqlRegistros'])?> registros)<?php endif;?></td>
</tr>
<?php endforeach;?>
<?php if(!$execucoes):?><tr><td colspan="8">Nenhuma execução registrada para esse filtro.</td></tr><?php endif;?>
</tbody></table>
</div>
</section>
<p class="muted">A sincronização Google Sheets → MySQL não envia WhatsApp. O resumo das 9h é destinado apenas ao próprio responsável; cobranças automáticas a terceiros não estão autorizadas.</p>
</div>
</main>
</div>
</body></html>
