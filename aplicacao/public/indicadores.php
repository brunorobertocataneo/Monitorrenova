<?php
declare(strict_types=1);
require_once '/var/www/src/Auth.php'; Auth::requireLogin();
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
require_once '/var/www/src/MonitorRepository.php';
function h(mixed $v): string{return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
try {
    $repo=new MonitorRepository(Database::connection());
    $status=$repo->resumoPorStatus();
    $dias=$repo->ultimosDias(7);
    $automacoes=$repo->automacoes();
    $kpi=$repo->indicadores();
}catch(Throwable $e){error_log($e->getMessage());http_response_code(503);exit('Não foi possível consultar os indicadores.');}
$max=max(1,...array_column($dias,'execucoes'));
$total=max(1,array_sum($status));
?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Indicadores — Monitor Renova</title>
<style>
*{box-sizing:border-box}body{font:15px system-ui,-apple-system,'Segoe UI',sans-serif;background:#f3f6fb;color:#1b3048;margin:0}
header{background:#122a46;color:white;padding:22px max(20px,calc((100vw - 1060px)/2))}header a{color:#d2e4fb}header h1{margin:8px 0 0;font-size:24px}
main{max-width:1100px;margin:24px auto;padding:0 20px}.grid{display:grid;grid-template-columns:1fr 1fr;gap:20px}.panel{background:white;border:1px solid #e2e8f0;border-radius:14px;padding:22px;margin-bottom:20px}h2{font-size:19px;margin:0 0 12px}.muted{color:#63748b}.bar-row{display:grid;grid-template-columns:100px 1fr 45px;gap:12px;align-items:center;margin:17px 0}.bar-bg{height:20px;background:#edf2f7;border-radius:7px;overflow:hidden}.bar{height:100%;border-radius:7px;background:#346ca6}.bar.ok{background:#25845c}.bar.bad{background:#bd5454}.bar.wait{background:#d8a344}.bar.cancel{background:#8491a4}
.week{display:flex;align-items:end;gap:12px;min-height:190px;padding:16px 4px 5px;border-bottom:1px solid #cfd9e4}.day{flex:1;min-width:0;text-align:center}.day-bar{height:145px;display:flex;flex-direction:column;justify-content:flex-end;align-items:center}.day-bar span{width:min(42px,90%);min-height:3px;border-radius:6px 6px 0 0;background:#346ca6}.day-label{font-size:12px;color:#60758a;margin-top:7px}.stat{font-size:28px;font-weight:700}.stack{display:flex;flex-wrap:wrap;gap:12px}.mini{background:#eef4fa;border-radius:9px;padding:12px 18px;flex:1}.mini strong{display:block;font-size:24px}table{border-collapse:collapse;width:100%}th,td{padding:11px;text-align:left;border-bottom:1px solid #e8eef5}th{font-size:13px;color:#60758a}.table-wrap{overflow-x:auto}a{color:#185fb0;text-decoration:none}a:hover{text-decoration:underline}
.notice{padding:13px;background:#fff5df;border:1px solid #f7dfae;border-radius:9px;margin-bottom:20px;color:#72531a}
@media(max-width:740px){.grid{grid-template-columns:1fr}.bar-row{grid-template-columns:85px 1fr 35px}}
</style></head><body>
<header><a href="/">← Voltar ao painel</a><h1>Indicadores e gráficos</h1><div style="opacity:.8">Monitor Renova · dados existentes no MySQL</div></header>
<main>
<div class="notice">A base contém uma execução de <strong>SIMULAÇÃO ACADÊMICA</strong>. Os valores exibidos não são cobranças reais nem mensagens enviadas pelo WhatsApp.</div>
<div class="stack">
<div class="mini"><span class="muted">Automações cadastradas</span><strong><?=h($kpi['automacoes'])?></strong></div>
<div class="mini"><span class="muted">Execuções registradas</span><strong><?=h($kpi['execucoes'])?></strong></div>
<div class="mini"><span class="muted">Sucessos</span><strong><?=h($kpi['sucessos'])?></strong></div>
<div class="mini"><span class="muted">Falhas</span><strong><?=h($kpi['falhas'])?></strong></div>
</div>
<div class="grid" style="margin-top:20px">
<section class="panel"><h2>Execuções por status</h2><p class="muted">Distribuição de todos os registros no banco.</p>
<?php foreach(['sucesso'=>['Sucesso','ok'],'falha'=>['Falha','bad'],'iniciada'=>['Iniciada','wait'],'cancelada'=>['Cancelada','cancel']] as $s=>$v):?>
<div class="bar-row"><span><?=h($v[0])?></span><div class="bar-bg"><div class="bar <?=h($v[1])?>" style="width:<?=round(100*$status[$s]/$total,2)?>%"></div></div><strong><?=h($status[$s])?></strong></div>
<?php endforeach;?>
</section>
<section class="panel"><h2>Execuções nos últimos 7 dias</h2><p class="muted">Quantidade de execuções iniciadas por dia.</p>
<div class="week">
<?php foreach($dias as $dia):?><div class="day"><div class="day-bar"><span style="height:<?=round(100*$dia['execucoes']/$max,1)?>%" title="<?=h($dia['execucoes'])?> execuções"></span></div><div class="day-label"><?=h(substr($dia['dia'],8,2).'/'.substr($dia['dia'],5,2))?></div><small><?=h($dia['execucoes'])?></small></div><?php endforeach;?>
</div>
</section></div>
<section class="panel"><h2>Volume diário de registros</h2><div class="table-wrap"><table><thead><tr><th>Data</th><th>Execuções</th><th>Processados</th><th>Elegíveis</th><th>Pendentes</th><th>Enviados</th></tr></thead><tbody>
<?php foreach(array_reverse($dias) as $dia):?><tr><td><?=h($dia['dia'])?></td><td><?=h($dia['execucoes'])?></td><td><?=h($dia['processados'])?></td><td><?=h($dia['elegiveis'])?></td><td><?=h($dia['pendentes'])?></td><td><?=h($dia['enviados'])?></td></tr><?php endforeach;?>
</tbody></table></div></section>
<section class="panel"><h2>Fluxos cadastrados</h2><div class="table-wrap"><table><thead><tr><th>Fluxo</th><th>Sistema</th><th>Situação cadastrada</th></tr></thead><tbody>
<?php foreach($automacoes as $a):?><tr><td><?=h($a['nome'])?></td><td><?=h($a['sistema_origem'])?></td><td><?=((int)$a['ativo']===1)?'Ativo':'Inativo'?></td></tr><?php endforeach;?>
</tbody></table></div></section>
<p class="muted">O sistema ainda não recebe auditorias automáticas do n8n. As métricas serão atualizadas após a integração autenticada.</p></main></body></html>
