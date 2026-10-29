<?php
/**
 * View do módulo de seguros.
 *
 * Recebe os dados prontos do SeguroController e apenas os apresenta.
 * Esta view não contém SQL nem altera a situação das apólices.
 * O arquivo fica em /var/www/src/views, fora da pasta pública do servidor.
 *
 * @var string $vendedor
 * @var string $produto
 * @var ?string $de
 * @var ?string $ate
 * @var string $busca
 * @var int $pagina
 * @var int $limite
 * @var array<int,array<string,mixed>> $rows
 */
function h(mixed $valor): string {
    // Protege o HTML contra conteúdo malicioso em nomes e filtros.
    return htmlspecialchars((string)$valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function query(array $valores): string {
    // Mantém os filtros ao navegar entre páginas do resultado.
    return http_build_query($valores);
}
?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Seguros — Monitor Renova</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#f3f6fb;color:#192d45;font:15px system-ui,-apple-system,Segoe UI,sans-serif}header{background:#122a46;padding:22px max(20px,calc((100vw - 1140px)/2));color:white}header a{color:#d6e9ff}header h1{margin:8px 0 4px;font-size:26px}main{max-width:1180px;margin:24px auto;padding:0 20px}.panel{background:white;border:1px solid #e0e8f0;border-radius:13px;padding:20px;margin-bottom:18px}.muted{color:#65768c}.notice{background:#fff7e6;border:1px solid #eedbad;padding:15px;border-radius:9px;margin-bottom:18px}.grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}.metric{font-size:26px;font-weight:750;margin:7px 0}form{display:grid;grid-template-columns:2fr 1.4fr 1fr 1fr;gap:12px;align-items:end}label{display:block;font-size:13px;margin-bottom:5px;color:#5a6b7f}input,select,button{font:inherit;width:100%;padding:9px;border:1px solid #cbd8e4;border-radius:7px;background:white}button{background:#1d568a;color:white;border-color:#1d568a;cursor:pointer}.tablewrap{overflow:auto}table{width:100%;border-collapse:collapse;min-width:1020px}th,td{text-align:left;padding:11px;border-bottom:1px solid #e8eef4;font-size:13px}th{color:#5a6e85;background:#f7f9fc}td{vertical-align:top}.pill{display:inline-block;border-radius:20px;padding:3px 8px;background:#edf1f6;font-size:12px}.pagination{display:flex;gap:10px;justify-content:space-between;align-items:center;margin-top:15px}a{color:#175caa;text-decoration:none}a:hover{text-decoration:underline}@media(max-width:850px){.grid{grid-template-columns:1fr 1fr}form{grid-template-columns:1fr 1fr}}@media(max-width:550px){form{grid-template-columns:1fr}}
</style>
<link rel="stylesheet" href="/assets/app.css?v=1">
</head><body class="app-shell">
<?php $activePage='seguros'; require __DIR__.'/partials/sidebar.php'; ?>
<div class="app-content">
<div class="page-topbar"><span class="topbar-kicker">MonitorRenova / Consulta de seguros</span>
<a class="mini-link" href="/">← Visão geral</a></div>
<div class="page-content">
<div class="page-titlebar"><div>
<div class="eyebrow">Operação de seguros</div>
<h1>Consulta de seguros</h1>
<p>Pesquise apólices por nome, vendedor, produto ou vencimento.</p>
</div><a class="btn-link" href="/competencias.php">Competências aprovadas ↗</a></div>
<main><?php if(!empty($ultimaImportacao)):?><p class="muted"><strong>Último lote histórico importado:</strong> <?=h($ultimaImportacao['iniciado_em'])?> · <?=h($ultimaImportacao['total_registros'])?> registros · fonte: cópia de testes do Google Sheets.</p><?php endif;?><div class="notice"><strong>Atenção:</strong> este painel consulta a cópia sincronizada no MySQL, atualizada periodicamente pelo n8n; pode haver atraso. O campo <strong>status do PDF</strong> representa a situação informada em 26/08/2026, e não confirma que a apólice continua ativa. Esta tela é somente leitura e não envia mensagens.</div>
<div class="grid" style="margin-bottom:18px">
<div class="panel"><span class="muted">Seguros importados</span><div class="metric"><?=h($stats['total'])?></div></div>
<div class="panel"><span class="muted">Com produto</span><div class="metric"><?=h($stats['com_produto']??0)?></div></div>
<div class="panel"><span class="muted">Com status histórico</span><div class="metric"><?=h($stats['status_pdf']??0)?></div></div>
<div class="panel"><span class="muted">Status atual confirmado</span><div class="metric"><?=h($stats['status_atual']??0)?></div></div></div>
<section class="panel"><h2>Filtros</h2><form method="get">
<div><label for="vendedor">Vendedor</label><select name="vendedor" id="vendedor"><option value="">Todos</option><?php foreach($vendors as $v):?><option value="<?=h($v)?>" <?=$v===$vendedor?'selected':''?>><?=h($v)?></option><?php endforeach;?></select></div>
<div><label for="produto">Produto</label><select name="produto" id="produto"><option value="">Todos</option><?php foreach($products as $p):?><option value="<?=h($p)?>" <?=$p===$produto?'selected':''?>><?=h($p)?></option><?php endforeach;?></select></div>
<div><label for="de">Vencimento de</label><input type="date" name="de" id="de" value="<?=h($de)?>"></div>
<div><label for="ate">Vencimento até</label><input type="date" name="ate" id="ate" value="<?=h($ate)?>"></div>
<div><label for="busca">Nome do segurado</label><input name="busca" id="busca" maxlength="100" value="<?=h($busca)?>" placeholder="Buscar por nome"></div>
<div><button type="submit">Aplicar filtros</button></div><div><a href="/seguros.php">Limpar filtros</a></div>
</form></section>
<section class="panel"><h2>Registros encontrados: <?=h($total)?></h2><div class="tablewrap"><table><thead><tr><th>Vencimento</th><th>Segurado</th><th>Vendedor</th><th>Produto</th><th>Renovado</th><th>Não vai renovar</th><th>Cobrado</th><th>Status atual</th><th>Status no PDF</th><th>Prêmio no PDF</th></tr></thead><tbody>
<?php foreach($rows as $r):?><tr>
<td><?=h(date('d/m/Y',strtotime($r['vencimento'])))?></td><td><?=h($r['segurado'])?></td><td><?=h($r['vendedor'])?></td><td><?=h($r['produto']??'—')?></td>
<td><?=((int)$r['renovado'])?'Sim':'Não informado'?></td><td><?=((int)$r['nao_vai_renovar'])?'Sim':'Não informado'?></td><td><?=((int)$r['cobrado'])?'Sim':'Não informado'?></td>
<td><?=h($r['status_atual']??'Não verificado')?></td><td><span class="pill"><?=h($r['status_pdf']??'Não consta')?></span></td>
<td><?=($r['premio_pdf']!==null)?'R$ '.h(number_format((float)$r['premio_pdf'],2,',','.')):'—'?></td></tr><?php endforeach;?>
<?php if(!$rows):?><tr><td colspan="10">Nenhum seguro encontrado com esses filtros.</td></tr><?php endif;?></tbody></table></div>
<div class="pagination"><div>Exibindo até <?=h($limite)?> registros por página · página <?=h($pagina)?> de <?=h($totalPages)?></div><div>
<?php if($pagina>1):?><a href="?<?=h(query(array_merge($paramsUrl,['pagina'=>$pagina-1])))?>">← Anterior</a><?php endif;?>
<?php if($pagina<$totalPages):?><a href="?<?=h(query(array_merge($paramsUrl,['pagina'=>$pagina+1])))?>">Próxima →</a><?php endif;?></div></div></section>
<p class="muted">Dados no MySQL local, sincronizados em lotes; histórico preservado. Esta tela não altera apólices.</p>
</main>
</div>
</div>
</body></html>