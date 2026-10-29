<?php
/**
 * View do módulo meses. Exibe os dados previamente preparados pelo Controller.
 * Todos os valores dinâmicos são protegidos por htmlspecialchars.
 */
function h(mixed $val):string {
 return htmlspecialchars((string)$val,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
}
?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Histórico mensal — Monitor Renova</title>
<style>
*{box-sizing:border-box}body{font:15px system-ui,-apple-system,Segoe UI,sans-serif;background:#f2f5fa;color:#192e46;margin:0}header{background:#122a46;color:white;padding:22px max(20px,calc((100vw - 1100px)/2))}header a{color:#d7e8fb}h1{margin:8px 0 0;font-size:25px}h2{margin:0 0 16px;font-size:19px}main{max-width:1140px;padding:0 20px;margin:24px auto}.panel{background:white;border:1px solid #e2e8f0;border-radius:12px;padding:22px;margin-bottom:20px}.notice{background:#fff5df;border:1px solid #f1ddb1;border-radius:10px;padding:15px;margin-bottom:20px}.grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}.metric{font-size:28px;font-weight:750;margin:6px 0}.muted{color:#64758a}select,button{padding:9px;border:1px solid #cad6e3;border-radius:7px;font:inherit}button{background:#1f578c;color:white}form{display:flex;flex-wrap:wrap;gap:12px;align-items:end}label{display:block;font-size:13px;margin-bottom:5px}.barrow{display:grid;grid-template-columns:minmax(110px,2fr) 3fr 50px;gap:12px;align-items:center;margin:12px 0}.track{height:18px;background:#edf2f8;border-radius:5px;overflow:hidden}.fill{height:100%;background:#3d77af;border-radius:5px}.tablewrap{overflow:auto}table{border-collapse:collapse;width:100%}th,td{padding:11px;text-align:left;border-bottom:1px solid #e8edf3;white-space:nowrap}th{color:#63768b;font-size:13px}a{color:#145fa7;text-decoration:none}a:hover{text-decoration:underline}@media(max-width:750px){.grid{grid-template-columns:repeat(2,1fr)}}
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
<?php $activePage='meses';require __DIR__.'/partials/sidebar.php';?>
<div class="app-content">
<div class="page-topbar"><span class="topbar-kicker">MonitorRenova / Histórico mensal de seguros</span><a class="mini-link" href="/">← Visão geral</a></div>
<main class="page-content">
<div class="page-titlebar"><div>
<div class="eyebrow">Consulta protegida</div><h1>Histórico mensal de seguros</h1><p>Arquivo sincronizado e importações manuais, em consultas separadas.</p>
</div></div>
<div class="module-layout">

<div class="notice"><strong>Somente consulta.</strong> <?php if($fonte==='planilha'):?>Mostrando o arquivo mensal sincronizado do Google Sheets. A cópia acompanha os lotes validados no MySQL e não substitui a confirmação das apólices.<?php else:?>Mostrando o histórico separado de importações manuais, que não é atualizado automaticamente.<?php endif;?> Esta página não envia WhatsApp. <a href="/competencias.php">Abrir gerenciador de competências →</a></div>
<section class="panel"><h2>Selecionar mês</h2><form method="get"><div><label for="fonte">Origem do histórico</label><select name="fonte" id="fonte"><option value="planilha" <?=$fonte==='planilha'?'selected':''?>>Google Sheets (arquivo sincronizado)</option><option value="manual" <?=$fonte==='manual'?'selected':''?>>Importações manuais</option></select></div><div><label for="mes">Competência</label><select name="mes" id="mes"><?php foreach($months as $m):?><option value="<?=h($m['competencia'])?>" <?=$m['competencia']===$month?'selected':''?>><?=h($m['competencia'])?> — <?=h($m['total'])?> seguros</option><?php endforeach;?></select></div>
<div><label for="vendedor">Vendedor</label><select name="vendedor" id="vendedor"><option value="">Todos</option><?php foreach($vendors as $v):?><option value="<?=h($v)?>" <?=$v===$vendor?'selected':''?>><?=h($v)?></option><?php endforeach;?></select></div><button type="submit">Consultar</button></form></section>
<div class="grid">
<div class="panel"><span class="muted">Registros no mês</span><div class="metric"><?=h($stats['total'])?></div></div>
<div class="panel"><span class="muted">Produtos identificados</span><div class="metric"><?=h($stats['produtos'])?></div></div>
<div class="panel"><span class="muted">Sem situação atual</span><div class="metric"><?=h($stats['sem_status'])?></div></div>
<div class="panel"><span class="muted">Marcados renovados</span><div class="metric"><?=h($stats['renovados'])?></div></div>
</div>
<section class="panel"><h2>Vencimentos por vendedor — <?=h($month??'sem dados')?></h2>
<?php foreach($byVendor as $v):?><div class="barrow"><span><?=h($v['vendedor'])?></span><div class="track"><div class="fill" style="width:<?=round(100*(int)$v['total']/$max,1)?>%"></div></div><strong><?=h($v['total'])?></strong></div><?php endforeach;?>
<?php if(!$byVendor):?><p class="muted">Nenhum registro para este mês.</p><?php endif;?></section>
<section class="panel"><h2>Distribuição por produto</h2><div class="tablewrap"><table><thead><tr><th>Produto</th><th>Seguros</th></tr></thead><tbody><?php foreach($byProduct as $r):?><tr><td><?=h($r['produto'])?></td><td><?=h($r['total'])?></td></tr><?php endforeach;?></tbody></table></div></section>
<section class="panel"><h2>Primeiros 30 seguros do mês</h2><div class="tablewrap"><table><thead><tr><th>Vencimento</th><th>Segurado</th><th>Vendedor</th><th>Produto</th><th>Situação atual</th></tr></thead><tbody>
<?php foreach($rows as $r):?><tr><td><?=h($r['vencimento'])?></td><td><?=h($r['segurado'])?></td><td><?=h($r['vendedor'])?></td><td><?=h($r['produto']??'Não informado')?></td><td><?=h($r['status_atual']??'Não verificada')?></td></tr><?php endforeach;?>
</tbody></table></div></section>
<section class="panel"><h2>Últimas importações manuais</h2><div class="tablewrap"><table><thead><tr><th>Mês</th><th>Registros</th><th>Data</th></tr></thead><tbody><?php foreach($lots as $r):?><tr><td><?=h($r['competencia'])?></td><td><?=h($r['registros'])?></td><td><?=h($r['criado_em'])?></td></tr><?php endforeach;?><?php if(!$lots):?><tr><td colspan="3">Outubro foi migrado do banco existente; novas importações aparecerão aqui.</td></tr><?php endif;?></tbody></table></div></section>
</div>
</main>
</div>
</body></html>
