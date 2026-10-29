<?php
/**
 * View do módulo prontidao. Exibe os dados previamente preparados pelo Controller.
 * Todos os valores dinâmicos são protegidos por htmlspecialchars.
 */
function h(mixed $val):string {
 return htmlspecialchars((string)$val,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
}
?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Prontidão de renovações — Monitor Renova</title>
<style>*{box-sizing:border-box}body{margin:0;background:#f3f6fb;color:#182d45;font:15px system-ui,-apple-system,Segoe UI,sans-serif}
header{background:#122a46;color:white;padding:23px max(20px,calc((100vw - 1100px)/2))}header h1{font-size:24px;margin:9px 0 0}header a{color:#d8ebfc}
main{max-width:1140px;margin:24px auto;padding:0 18px}.panel{background:white;border-radius:12px;padding:22px;border:1px solid #e0e8f1;margin-bottom:18px}
.note{border-radius:9px;padding:15px;margin-bottom:16px;border:1px solid #e5cf9b;background:#fff6dd}.grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}
.metric{font-weight:750;font-size:29px;margin:8px 0 0}.muted{color:#64758a}h2{margin:0 0 13px;font-size:19px}a{color:#1a63a4;text-decoration:none}a:hover{text-decoration:underline}
select,button{border:1px solid #c5d5e6;padding:10px;font:inherit;border-radius:8px}button{background:#1d5f93;color:white;cursor:pointer}table{width:100%;border-collapse:collapse}th,td{padding:11px;border-bottom:1px solid #e5ecf3;text-align:left}th{color:#63778c}
@media(max-width:750px){.grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:500px){.grid{grid-template-columns:1fr}}
</style>
<link rel="stylesheet" href="/assets/app.css?v=1">
<style>
/* Ajuste visual isolado às telas antigas */
.app-shell .module-layout .panel,.app-shell .module-layout .card{box-shadow:0 3px 14px #19365009}
.app-shell .module-layout table{min-width:690px}
.app-shell .module-layout{min-width:0}
.app-shell .module-layout .grid,.app-shell .module-layout .cards{gap:12px}
.app-shell .module-layout .note,.app-shell .module-layout .notice{line-height:1.6}
@media(max-width:700px){.app-shell .module-layout .grid,.app-shell .module-layout .cards{grid-template-columns:repeat(2,minmax(0,1fr))}}
</style>
</head>
<body class="app-shell">
<?php $activePage='prontidao';require __DIR__.'/partials/sidebar.php';?>
<div class="app-content">
<div class="page-topbar"><span class="topbar-kicker">MonitorRenova / Prontidão das renovações</span><a class="mini-link" href="/">← Visão geral</a></div>
<main class="page-content">
<div class="page-titlebar"><div>
<div class="eyebrow">Consulta protegida</div><h1>Prontidão das renovações</h1><p>Auditorias internas históricas sem liberação de cobrança.</p>
</div></div>
<div class="module-layout">
<p class="hint" style="margin:0 0 14px"><strong>Origem:</strong> registros de conferência e importações mensais históricas. Este módulo não indica automaticamente a situação atual da apólice nem utiliza a competência sincronizada sem uma revisão.</p>
<div class="note"><strong>Nenhum seguro foi liberado para cobrança.</strong> Os números vêm de uma cópia mensal e não comprovam a situação atual das apólices. A auditoria completa do n8n, incluindo consulta do histórico no Google Sheets, é uma etapa separada e ainda precisa ser concluída. Nenhuma mensagem foi enviada por esta auditoria.</div>
<section class="panel"><form method="get"><label for="mes">Competência: </label><select id="mes" name="mes"><?php foreach($months as $m):?><option value="<?=h($m)?>" <?=$m===$mes?'selected':''?>><?=h($m)?></option><?php endforeach;?></select> <button type="submit">Visualizar</button></form>
<?php if($latest):?><p class="muted">Última fotografia registrada: <?=h($latest['criado_em'])?> · auditoria #<?=h($latest['id'])?>. Um arquivo sem alterações não cria auditoria duplicada.</p><?php endif;?></section>
<?php if($latest):?>
<div class="grid"><?php foreach($labels as $key=>$label):?>
<div class="panel"><div class="muted"><?=h($label)?></div><div class="metric"><?=h($latest[$key]??0)?></div></div>
<?php endforeach;?></div>
<section class="panel"><h2>O que falta antes de automatizar</h2>
<p><strong>1. Validar a situação atual das apólices.</strong> A ausência de dados não deve ser interpretada como apólice ativa.</p>
<p><strong>2. Resolver divergências e dados de produto.</strong> Alguns indicadores se sobrepõem, portanto não devem ser somados.</p>
<p><strong>3. Concluir a leitura do histórico de cobranças no n8n.</strong> Só depois será possível verificar riscos de duplicidade e reenvios com informações atualizadas.</p>
<p><strong>4. Revisar destinatários e obter autorização de envio.</strong> O teste de conexão do WhatsApp não autoriza disparos automáticos.</p>
<p><a href="/validacoes.php?mes=<?=h($mes)?>">Confirmar situação na fonte →</a> · <a href="/conferencia.php?mes=<?=h($mes)?>">Ir à conferência de dados →</a> · <a href="/integracoes.php">Ver integrações →</a></p>
</section>
<section class="panel"><h2>Histórico de auditorias locais</h2><div style="overflow:auto"><table><thead><tr><th>ID</th><th>Registrada em</th><th>Seguros</th><th>Sem status</th><th>Sem produto</th><th>Liberações</th><th>Envios</th></tr></thead><tbody>
<?php foreach($history as $r):?><tr><td><?=h($r['id'])?></td><td><?=h($r['criado_em'])?></td><td><?=h($r['registros'])?></td><td><?=h($r['sem_status_atual'])?></td><td><?=h($r['sem_produto'])?></td><td><?=h($r['liberados_envio'])?></td><td><?=h($r['mensagens_enviadas'])?></td></tr><?php endforeach;?>
</tbody></table></div></section>
<?php else:?><section class="panel"><p>Nenhuma auditoria local registrada.</p></section><?php endif;?>
</div>
</main>
</div>
</body></html>
