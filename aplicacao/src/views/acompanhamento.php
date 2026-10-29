<?php
/**
 * View do módulo acompanhamento. Exibe os dados previamente preparados pelo Controller.
 * Todos os valores dinâmicos são protegidos por htmlspecialchars.
 */
function h(mixed $val):string {
 return htmlspecialchars((string)$val,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
}
?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Acompanhamento de revisões — Monitor Renova</title>
<style>*{box-sizing:border-box}body{font:15px system-ui,-apple-system,Segoe UI,sans-serif;background:#f2f5fa;color:#192d45;margin:0}header{background:#122a46;color:white;padding:24px max(20px,calc((100vw - 1110px)/2))}header a{color:#d1e7ff}header h1{margin:7px 0 0;font-size:25px}main{max-width:1150px;margin:24px auto;padding:0 18px}.panel{background:white;border:1px solid #e1e8ef;border-radius:12px;padding:20px;margin-bottom:20px}.cards{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px}.metric{font-size:27px;font-weight:750;margin-top:8px}.muted{color:#62748a}.note{background:#fff5df;border:1px solid #ebd5ab;border-radius:9px;padding:15px;margin-bottom:20px}form{display:flex;flex-wrap:wrap;align-items:end;gap:12px}select,button{padding:9px;font:inherit;border:1px solid #c9d7e5;border-radius:7px}button{background:#1b568d;color:white;border-color:#1b568d;cursor:pointer}label{font-size:13px;display:block;margin-bottom:6px}.tablewrap{overflow:auto}table{border-collapse:collapse;min-width:860px;width:100%}th,td{text-align:left;padding:12px;border-bottom:1px solid #e7edf4}th{color:#62748a;font-size:13px}a{color:#155eaa;text-decoration:none}a:hover{text-decoration:underline}.pages{display:flex;gap:15px;justify-content:space-between;flex-wrap:wrap;margin-top:15px}@media(max-width:850px){.cards{grid-template-columns:repeat(2,1fr)}}</style>
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
<?php $activePage='acompanhamento';require __DIR__.'/partials/sidebar.php';?>
<div class="app-content">
<div class="page-topbar"><span class="topbar-kicker">MonitorRenova / Acompanhamento de revisões</span><a class="mini-link" href="/">← Visão geral</a></div>
<main class="page-content">
<div class="page-titlebar"><div>
<div class="eyebrow">Consulta protegida</div><h1>Acompanhamento de revisões</h1><p>Situação das revisões internas, separada da situação das apólices.</p>
</div></div>
<div class="module-layout">
<p class="hint" style="margin:0 0 14px"><strong>Origem:</strong> registros de conferência e importações mensais históricas. Este módulo não indica automaticamente a situação atual da apólice nem utiliza a competência sincronizada sem uma revisão.</p>
<div class="note">A situação da revisão <strong>não confirma que a apólice está ativa ou autorizada para cobrança</strong>. Esta tela apenas mostra o andamento das verificações internas. Não altera o Google Sheets nem envia mensagens.</div>
<section class="panel"><form method="get"><div><label for="mes">Mês</label><select id="mes" name="mes"><?php foreach($months as $m):?><option value="<?=h($m)?>" <?=$m===$mes?'selected':''?>><?=h($m)?></option><?php endforeach;?></select></div>
<div><label for="situacao">Andamento</label><select id="situacao" name="situacao"><option value="">Todos</option><?php foreach($statuses as $key=>$title):?><option value="<?=h($key)?>" <?=$filter===$key?'selected':''?>><?=h($title)?></option><?php endforeach;?></select></div><button type="submit">Filtrar</button></form></section>
<div class="cards"><?php foreach($statuses as $key=>$title):?><div class="panel"><div class="muted"><?=h($title)?></div><div class="metric"><?=h($counts[$key])?></div></div><?php endforeach;?></div>
<section class="panel"><h2><?=h($filter===''?'Todas as situações':$statuses[$filter])?> — <?=h($total)?> seguros</h2><div class="tablewrap"><table><thead><tr><th>Linha</th><th>Segurado</th><th>Vendedor</th><th>Vencimento</th><th>Revisão</th><th>Última revisão</th><th>Ação</th></tr></thead><tbody>
<?php foreach($rows as $r):?><tr><td><?=h($r['linha_origem'])?></td><td><?=h($r['segurado'])?></td><td><?=h($r['vendedor'])?></td><td><?=h($r['vencimento'])?></td><td><?=h($statuses[$r['situacao']]??'Não informado')?></td><td><?=h($r['atualizado_em']??'—')?></td><td><a href="/revisar.php?id=<?=h($r['id'])?>">Revisar →</a></td></tr><?php endforeach;?>
<?php if(!$rows):?><tr><td colspan="7">Nenhum seguro neste filtro.</td></tr><?php endif;?></tbody></table></div>
<div class="pages"><span class="muted">Página <?=h($page)?> de <?=h($pages)?> · até 30 por página</span><div>
<?php if($page>1):?><a href="?<?=h(http_build_query(array_merge($qs,['pagina'=>$page-1])))?>">← Anterior</a><?php endif;?>
<?php if($page<$pages):?><a href="?<?=h(http_build_query(array_merge($qs,['pagina'=>$page+1])))?>">Próxima →</a><?php endif;?>
</div></div></section></div>
</main>
</div>
</body></html>
