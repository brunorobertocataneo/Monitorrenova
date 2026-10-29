<?php
/**
 * View do módulo validacoes. Exibe os dados previamente preparados pelo Controller.
 * Todos os valores dinâmicos são protegidos por htmlspecialchars.
 */
function h(mixed $val):string {
 return htmlspecialchars((string)$val,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
}
?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Validação de apólices — Monitor Renova</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#f3f6fb;color:#19324a;font:15px system-ui,-apple-system,Segoe UI,sans-serif}
header{background:#122a46;color:#fff;padding:23px max(20px,calc((100vw - 1120px)/2))}
header a{color:#d5ecfc}header h1{font-size:25px;margin:9px 0 0}
main{max-width:1150px;margin:25px auto;padding:0 18px}.card{border:1px solid #e0e9f1;border-radius:12px;background:white;padding:21px;margin-bottom:17px}
.note{background:#fff5de;border:1px solid #ebd6aa;border-radius:9px;padding:15px;margin-bottom:18px}
.grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:13px}
.metric{font-size:28px;font-weight:750;margin:7px 0 0}.muted{color:#60758a}
form{display:flex;flex-wrap:wrap;gap:12px;align-items:end}label{display:block;font-size:13px;margin-bottom:7px}
select,button{border:1px solid #bacbdc;border-radius:8px;padding:9px;font:inherit;background:white}
button{background:#155c8c;color:#fff;border-color:#155c8c;cursor:pointer}
h2{font-size:19px;margin:0 0 13px}a{color:#155a9a;text-decoration:none}a:hover{text-decoration:underline}
table{width:100%;border-collapse:collapse;min-width:900px}th,td{border-bottom:1px solid #e9eef4;text-align:left;padding:11px;font-size:13px;vertical-align:top}
th{color:#69788a}.table-wrap{overflow:auto}.pager{display:flex;justify-content:space-between;gap:14px;flex-wrap:wrap;margin-top:15px}
@media(max-width:800px){.grid{grid-template-columns:repeat(2,1fr)}}@media(max-width:480px){.grid{grid-template-columns:1fr}}
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
<?php $activePage='validacoes';require __DIR__.'/partials/sidebar.php';?>
<div class="app-content">
<div class="page-topbar"><span class="topbar-kicker">MonitorRenova / Validação de apólices</span><a class="mini-link" href="/">← Visão geral</a></div>
<main class="page-content">
<div class="page-titlebar"><div>
<div class="eyebrow">Consulta protegida</div><h1>Validação de apólices</h1><p>Conferências manuais e evidências de consultas recentes.</p>
</div></div>
<div class="module-layout">
<p class="hint" style="margin:0 0 14px"><strong>Origem:</strong> registros de conferência e importações mensais históricas. Este módulo não indica automaticamente a situação atual da apólice nem utiliza a competência sincronizada sem uma revisão.</p>
<div class="note"><strong>Sem liberação de cobranças.</strong> Uma consulta registrada aqui não autoriza envio de WhatsApp. Esta página não altera planilhas, não emite cobranças e não considera dados históricos como situação atual. A fonte consultada deve ser verificada por uma pessoa autorizada.</div>
<section class="card"><form method="get"><div><label for="mes">Competência</label><select id="mes" name="mes"><?php foreach($months as $m):?><option value="<?=h($m)?>" <?=$m===$month?'selected':''?>><?=h($m)?></option><?php endforeach;?></select></div>
<div><label for="filtro">Classificação da conferência</label><select id="filtro" name="filtro"><?php foreach($labels as $key=>$text):?><option value="<?=h($key)?>" <?=$key===$filter?'selected':''?>><?=h($text)?></option><?php endforeach;?></select></div><button type="submit">Filtrar</button></form>
<p class="muted">Confirmações com mais de 7 dias são mostradas como desatualizadas. Alterações nos dados importados também exigem nova verificação.</p></section>
<div class="grid">
<?php foreach(['todos'=>'Apólices do mês','sem_registro'=>'Sem consulta atual','antiga'=>'Consulta desatualizada','ativa'=>'Ativas verificadas','alterada'=>'Dados mudaram','indeterminada'=>'Não confirmadas','cancelada'=>'Canceladas verificadas','recusada'=>'Recusadas verificadas'] as $key=>$text):?>
<div class="card"><div class="muted"><?=h($text)?></div><div class="metric"><?=h($stats[$key])?></div></div>
<?php endforeach;?></div>
<section class="card"><h2><?=h($labels[$filter])?> — <?=h($total)?> registros</h2>
<div class="table-wrap"><table><thead><tr><th>Linha</th><th>Segurado</th><th>Vendedor</th><th>Vencimento</th><th>Produto</th><th>Consulta mais recente</th><th>Situação da conferência</th><th>Ação</th></tr></thead><tbody>
<?php foreach($rows as $record):?><tr>
<td><?=h($record['linha_origem'])?></td>
<td><?=h($record['segurado'])?></td>
<td><?=h($record['vendedor'])?></td>
<td><?=h($record['vencimento'])?></td>
<td><?=h(trim((string)($record['produto']??''))?:'Não informado')?></td>
<td><?=h($record['data_consulta']??'Nunca consultada')?></td>
<td><?=h($labels[$record['categoria']])?></td>
<td><a href="/validar-status.php?id=<?=h($record['id'])?>">Verificar na fonte →</a></td>
</tr><?php endforeach;?>
<?php if(!$rows):?><tr><td colspan="8" class="muted">Nenhuma apólice neste filtro.</td></tr><?php endif;?>
</tbody></table></div>
<div class="pager"><span class="muted">Página <?=h($page)?> de <?=h($pages)?> · 30 seguros por página</span><div>
<?php if($page>1):?><a href="/validacoes.php?<?=h(http_build_query($urlParams+['pagina'=>$page-1]))?>">← Anterior</a><?php endif;?>&nbsp;
<?php if($page<$pages):?><a href="/validacoes.php?<?=h(http_build_query($urlParams+['pagina'=>$page+1]))?>">Próxima →</a><?php endif;?>
</div></div></section></div>
</main>
</div>
</body></html>
