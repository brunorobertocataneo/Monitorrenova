<?php
/**
 * View HTML de vencimentos. Apenas apresenta dados do Controller.
 * Arquivo fora de /public, para impedir acesso sem login.
 * Escapamos nomes provenientes da planilha com htmlspecialchars.
 */
function h(mixed $valor):string {
    return htmlspecialchars((string)$valor, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');
}
?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Vencimentos — Monitor Renova</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#f2f5fa;font:15px system-ui,-apple-system,'Segoe UI',sans-serif;color:#182d45}header{background:#132c48;color:white;padding:24px max(20px,calc((100vw - 1120px)/2))}header a{color:#d8eafa}header h1{margin:8px 0;font-size:26px}main{max-width:1160px;margin:24px auto;padding:0 20px}.panel{background:#fff;border:1px solid #e2e8ef;border-radius:12px;padding:21px;margin-bottom:20px}.note{background:#fff5dd;border:1px solid #ead5a6;padding:15px;border-radius:10px;margin-bottom:20px}.muted{color:#65768c}.cards{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}.metric{font-size:29px;font-weight:760;margin-top:7px}.layout{display:grid;grid-template-columns:1fr 1fr;gap:18px}.rowbar{display:grid;grid-template-columns:minmax(95px,1.6fr) 2fr 45px;align-items:center;gap:10px;margin:12px 0;font-size:13px}.track{background:#edf2f8;border-radius:6px;height:18px;overflow:hidden}.fill{background:#3d77af;height:100%;border-radius:6px}.fill.product{background:#48a29b}h2{margin:0 0 16px;font-size:19px}form{display:flex;flex-wrap:wrap;align-items:end;gap:12px}input,select,button{padding:9px;border:1px solid #ccd8e4;border-radius:7px;font:inherit;background:#fff}button{background:#184f83;color:#fff;cursor:pointer}label{display:block;font-size:13px;margin-bottom:5px}.tablewrap{overflow:auto}table{width:100%;border-collapse:collapse}th,td{padding:12px;text-align:left;border-bottom:1px solid #e7edf4;white-space:nowrap}th{font-size:13px;color:#65768c}a{color:#1565ae;text-decoration:none}a:hover{text-decoration:underline}.dates{display:flex;gap:5px;align-items:end;min-height:180px;border-bottom:1px solid #cbd6e3}.dateitem{flex:1;text-align:center;min-width:12px}.datebar{background:#3778af;border-radius:4px 4px 0 0;margin:0 auto;min-height:2px;max-width:30px}.dateitem small{display:block;color:#65768c;font-size:10px;margin-top:5px}@media(max-width:800px){.cards{grid-template-columns:repeat(2,1fr)}.layout{grid-template-columns:1fr}}@media(max-width:500px){.cards{grid-template-columns:1fr 1fr}.rowbar{grid-template-columns:95px 1fr 30px}}
.shortcuts{display:flex;flex-wrap:wrap;gap:9px;margin:-2px 0 16px}.shortcuts a{display:inline-flex;padding:8px 11px;background:#edf4ff;border:1px solid #c8d9ed;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none}.shortcuts a:hover,.shortcuts a:focus-visible{background:#deedff;outline:2px solid #94bde8;outline-offset:1px}</style>
<link rel="stylesheet" href="/assets/app.css?v=1">
<style>
/* O gráfico diário pode ter dezenas de barras. Em telas pequenas,
   rolar somente o gráfico, evitando deslocar o aplicativo inteiro. */
.app-shell .dates{max-width:100%;overflow-x:auto;overflow-y:hidden;overscroll-behavior-x:contain;padding-bottom:8px}
.app-shell .dateitem{flex:1 0 18px}
.app-shell .layout,.app-shell .panel{min-width:0}
.app-shell .rowbar>span{min-width:0;overflow-wrap:anywhere}
@media(max-width:560px){.app-shell .page-content>main{max-width:100%;margin:0;padding:0}}
</style>
</head><body class="app-shell">
<?php $activePage='vencimentos'; require __DIR__.'/partials/sidebar.php'; ?>
<div class="app-content">
<div class="page-topbar"><span class="topbar-kicker">MonitorRenova / Dashboard de vencimentos</span>
<a class="mini-link" href="/">← Visão geral</a></div>
<div class="page-content">
<div class="page-titlebar"><div>
<div class="eyebrow">Operação de seguros</div>
<h1>Dashboard de vencimentos</h1>
<p>Analise os vencimentos por período, vendedor e produto.</p>
</div><a class="btn-link" href="/competencias.php">Competências aprovadas ↗</a></div><main>
<div class="note"><strong>Importante:</strong> estes dados vêm do espelho do Google Sheets no MySQL, atualizado periodicamente; pode haver atraso. Os números de renovados e cobrados dependem do preenchimento da planilha. <strong>“Sem confirmação” não significa que o seguro esteja ativo ou que deva ser cobrado.</strong> Não há envio de mensagens nesta tela.</div>
<section class="panel"><h2>Período de análise</h2>
<div class="shortcuts" aria-label="Atalhos de período">
<?php foreach($atalhos as $atalho):?>
<a href="?<?=h(http_build_query(['de'=>$atalho['de'],'ate'=>$atalho['ate'],'vendedor'=>$vendedor]))?>"><?=h($atalho['label'])?></a>
<?php endforeach;?>
</div>
<form method="get">
<div><label for="de">De</label><input id="de" type="date" name="de" value="<?=h($de)?>"></div>
<div><label for="ate">Até</label><input id="ate" type="date" name="ate" value="<?=h($ate)?>"></div>
<div><label for="vendedor">Vendedor</label><select id="vendedor" name="vendedor"><option value="">Todos</option><?php foreach($sellers as $v):?><option value="<?=h($v)?>" <?=$v===$vendedor?'selected':''?>><?=h($v)?></option><?php endforeach;?></select></div>
<button type="submit">Aplicar filtros</button></form>
<?php if($estadoSheet):?>
<p class="muted"><strong>Última sincronização do Google Sheets:</strong>
  <?=h($estadoSheet['horario'])?> (horário de Brasília) · <?=h($estadoSheet['total'])?> seguros
  <?php if(!$estadoSheet['recente']):?> · <strong>Sincronização desatualizada; confira Docker e n8n.</strong><?php endif;?>
</p>
<?php else:?><p class="muted">Google Sheets ainda não sincronizado. Exibindo dados locais.</p><?php endif;?>
<?php if($import):?><p class="muted">Última importação histórica manual: <?=h($import['iniciado_em'])?> · <?=h($import['total_registros'])?> registros.</p><?php endif;?>
</section>
<div class="cards">
<div class="panel"><span class="muted">Vencimentos no período</span><div class="metric"><?=h($total)?></div></div>
<div class="panel"><span class="muted">Marcados renovados</span><div class="metric"><?=h($stats['renovados']??0)?></div></div>
<div class="panel"><span class="muted">Marcados cobrados</span><div class="metric"><?=h($stats['cobrados']??0)?></div></div>
<div class="panel"><span class="muted">Sem confirmação atual</span><div class="metric"><?=h($stats['sem_confirmacao']??0)?></div></div>
</div>
<div class="layout">
<section class="panel"><h2>Vencimentos por vendedor</h2><?php foreach($bySeller as $r):?><div class="rowbar"><span title="<?=h($r['vendedor'])?>"><?=h($r['vendedor'])?></span><div class="track"><div class="fill" style="width:<?=round((int)$r['total']/$maxSeller*100,1)?>%"></div></div><strong><?=h($r['total'])?></strong></div><?php endforeach;?><?php if(!$bySeller):?><p class="muted">Nenhum registro nesse período.</p><?php endif;?></section>
<section class="panel"><h2>Distribuição por produto</h2><?php foreach($byProduct as $r):?><div class="rowbar"><span><?=h($r['produto'])?></span><div class="track"><div class="fill product" style="width:<?=round((int)$r['total']/$maxProduct*100,1)?>%"></div></div><strong><?=h($r['total'])?></strong></div><?php endforeach;?></section>
</div>
<section class="panel"><h2>Vencimentos por dia</h2><?php if($byDate):?><div class="dates"><?php foreach($byDate as $r):?><div class="dateitem" title="<?=h($r['vencimento'])?> · <?=h($r['total'])?> vencimentos"><div class="datebar" style="height:<?=max(3,round((int)$r['total']/$maxDate*150,1))?>px"></div><small><?=h(substr($r['vencimento'],8,2))?></small></div><?php endforeach;?></div><p class="muted">Cada coluna representa um dia com vencimentos. Passe o mouse para ver a quantidade.</p><?php else:?><p class="muted">Nenhum vencimento encontrado.</p><?php endif;?></section>
<section class="panel"><h2>Resumo por vendedor</h2><p class="muted">Clique no nome do vendedor para consultar os respectivos segurados no período.</p><div class="tablewrap"><table><thead><tr><th>Vendedor</th><th>Total</th><th>Renovados</th><th>Não vão renovar</th><th>Cobrados</th><th>Sem status atual</th></tr></thead><tbody>
<?php foreach($bySeller as $r):?><tr><td><a href="/seguros.php?<?=h(http_build_query(['vendedor'=>$r['vendedor'],'de'=>$de,'ate'=>$ate]))?>"><?=h($r['vendedor'])?></a></td><td><?=h($r['total'])?></td><td><?=h($r['renovados'])?></td><td><?=h($r['nao_renovam'])?></td><td><?=h($r['cobrados'])?></td><td><?=h($r['sem_status'])?></td></tr><?php endforeach;?></tbody></table></div></section>
<p class="muted">Os campos com situação histórica de apólice não são usados como confirmação atual. <a href="/seguros.php">Consultar seguros individualmente</a>.</p>
</main>
</div>
</div>
</body></html>