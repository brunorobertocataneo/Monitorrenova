<?php
declare(strict_types=1);
require_once '/var/www/src/Auth.php';Auth::requireLogin();
header('X-Frame-Options: DENY');header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');
require_once '/var/www/src/Database.php';require_once '/var/www/src/SimuladorRenovacao.php';
function h(mixed $s):string{return htmlspecialchars((string)$s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
try {
 $db=Database::connection();
 $months=$db->query('SELECT DISTINCT competencia FROM seguro_mensal ORDER BY competencia DESC')->fetchAll(PDO::FETCH_COLUMN);
 $tz=new DateTimeZone('America/Sao_Paulo');$now=new DateTimeImmutable('now',$tz);
 $month=(string)($_GET['mes']??$now->format('Y-m'));
 if(!in_array($month,$months,true))$month=$months[0]??'';
 $day=(string)($_GET['dia']??($month===$now->format('Y-m')?$now->format('Y-m-d'):$month.'-01'));
 $date=DateTimeImmutable::createFromFormat('!Y-m-d',$day,$tz);
 if(!$date||$date->format('Y-m-d')!==$day||substr($day,0,7)!==$month){
  $day=$month===$now->format('Y-m')?$now->format('Y-m-d'):$month.'-01';
  $date=DateTimeImmutable::createFromFormat('!Y-m-d',$day,$tz);
 }
 $filter=(string)($_GET['motivo']??'todos');
 if($filter!=='todos'&&!isset(SimuladorRenovacao::MOTIVOS[$filter]))$filter='todos';
 $q=$db->prepare('SELECT s.id,s.competencia,s.linha_origem,s.apolice,s.segurado,s.vendedor,s.vencimento,s.produto,s.renovado,s.cobrado,s.nao_vai_renovar,s.status_atual,s.status_pdf, v.id AS validacao_id,v.situacao AS validacao_situacao,v.data_consulta AS validacao_data,v.fingerprint_origem AS validacao_fingerprint FROM seguro_mensal s LEFT JOIN validacao_apolice v ON v.id=(SELECT MAX(v2.id) FROM validacao_apolice v2 WHERE v2.seguro_mensal_id=s.id) WHERE s.competencia=? ORDER BY s.linha_origem');
 $q->execute([$month]);$items=$q->fetchAll();
 if(count($items)>10000)throw new RuntimeException('Limite de 10000 registros');
 $analysis=SimuladorRenovacao::avaliar($items,$date);
 $rows=$filter==='todos'?$analysis['linhas']:array_values(array_filter($analysis['linhas'],fn($x)=>$x['motivo_codigo']===$filter));
 $page=max(1,min(1000,(int)($_GET['pagina']??1)));$pages=max(1,(int)ceil(count($rows)/30));$page=min($page,$pages);
 $subset=array_slice($rows,($page-1)*30,30);
 $next=fn(int $n)=>'?'.http_build_query(['mes'=>$month,'dia'=>$day,'motivo'=>$filter,'pagina'=>$n]);
}catch(Throwable $e){error_log('Simulador: '.$e->getMessage());http_response_code(503);exit('Simulador indisponível');}
?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Simulador | Monitor Renova</title>
<style>*{box-sizing:border-box}body{margin:0;background:#f2f5fa;font:15px system-ui,-apple-system,Segoe UI,sans-serif;color:#1b3148}header{background:#142e49;color:#fff;padding:24px max(20px,calc((100vw - 1070px)/2))}header a{color:#d5ebff}main{max-width:1110px;margin:26px auto;padding:0 20px}h1{margin:8px 0 2px}h2{font-size:19px;margin:0 0 15px}.panel{background:#fff;border:1px solid #dee7f0;border-radius:13px;padding:22px;margin-bottom:18px}.note{background:#fff4da;border:1px solid #efd59b;border-radius:9px;padding:15px;margin-bottom:20px}.cards{display:grid;grid-template-columns:repeat(3,1fr);gap:13px}.metric{font-size:29px;font-weight:750}.muted{color:#637589}form{display:flex;gap:12px;flex-wrap:wrap;align-items:end}label{display:block;margin-bottom:5px}select,input,button{font:inherit;border:1px solid #c5d2e0;border-radius:7px;padding:9px}button{background:#1c578a;color:white;cursor:pointer}.table{overflow:auto}table{border-collapse:collapse;width:100%;min-width:950px}th,td{border-bottom:1px solid #e7eef5;text-align:left;padding:10px;font-size:13px}a{color:#1660a5}.pagination{display:flex;justify-content:space-between;margin-top:16px}@media(max-width:650px){.cards{grid-template-columns:1fr}}</style></head><body>
<header><a href="/">← Painel</a><h1>Simulador de renovações</h1><p>Teste de triagem — somente leitura</p></header><main>
<div class="note"><strong>Envio de WhatsApp permanentemente desativado nesta página.</strong> Uma pré-triagem positiva não autoriza cobrança. Agora o simulador exige conferência recente da apólice na fonte, além de histórico de cobranças e autorização de contato. <a href="/validacoes.php">Abrir validações na fonte →</a></div>
<section class="panel"><form method="get"><div><label>Mês</label><select name="mes"><?php foreach($months as $m):?><option value="<?=h($m)?>" <?=$m===$month?'selected':''?>><?=h($m)?></option><?php endforeach;?></select></div><div><label>Data de referência</label><input type="date" name="dia" value="<?=h($day)?>"></div><div><label>Resultado</label><select name="motivo"><option value="todos">Todos</option><?php foreach(SimuladorRenovacao::MOTIVOS as $key=>$desc):?><option value="<?=h($key)?>" <?=$filter===$key?'selected':''?>><?=h($desc)?></option><?php endforeach;?></select></div><button type="submit">Simular</button></form>
<p class="muted">Janela de vencimentos: <?=h(implode(', ',$analysis['janela'])?:'Nenhuma (fim de semana)')?></p></section>
<div class="cards"><div class="panel"><span class="muted">Seguros analisados</span><div class="metric"><?=h($analysis['total'])?></div></div><div class="panel"><span class="muted">Aguardam histórico</span><div class="metric"><?=h($analysis['contagens']['aguardar_historico'])?></div></div><div class="panel"><span class="muted">Mensagens enviadas</span><div class="metric">0</div></div></div>
<section class="panel"><h2>Motivos da triagem</h2><div class="cards"><?php foreach(SimuladorRenovacao::MOTIVOS as $key=>$desc):?><p><span class="muted"><?=h($desc)?></span><br><strong><?=h($analysis['contagens'][$key])?></strong></p><?php endforeach;?></div></section>
<section class="panel"><h2>Seguros classificados (<?=h(count($rows))?>)</h2><div class="table"><table><thead><tr><th>Linha</th><th>Segurado</th><th>Vendedor</th><th>Vencimento</th><th>Produto</th><th>Status importado</th><th>Conferência na fonte</th><th>Resultado</th></tr></thead><tbody>
<?php foreach($subset as $r):?><tr><td><?=h($r['linha_origem'])?></td><td><?=h($r['segurado'])?></td><td><?=h($r['vendedor'])?></td><td><?=h($r['vencimento'])?></td><td><?=h($r['produto'])?></td><td><?=h($r['status_atual']?:'Não informado')?></td><td><?=h($r['verificacao'])?></td><td><?=h($r['motivo'])?></td></tr><?php endforeach;?>
<?php if(!$subset):?><tr><td colspan="8">Nenhum registro nesse filtro.</td></tr><?php endif;?></tbody></table></div><div class="pagination"><span>Página <?=h($page)?> de <?=h($pages)?></span><div><?php if($page>1):?><a href="<?=h($next($page-1))?>">← Anterior</a><?php endif;?> <?php if($page<$pages):?><a href="<?=h($next($page+1))?>">Próxima →</a><?php endif;?></div></div></section>
</main></body></html>