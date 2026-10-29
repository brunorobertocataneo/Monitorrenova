<?php
declare(strict_types=1);
require_once '/var/www/src/Auth.php'; Auth::requireLogin();
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
require_once '/var/www/src/Database.php';
function h(mixed $v):string{return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function n(mixed $v):int{return (int)($v??0);}
try {
 $db=Database::connection();
 $months=$db->query('SELECT competencia FROM seguro_mensal GROUP BY competencia ORDER BY competencia DESC')->fetchAll(PDO::FETCH_COLUMN);
 $mes=is_string($_GET['mes']??null)&&in_array($_GET['mes'],$months,true)?$_GET['mes']:($months[0]??null);
 $allowed=['todos','sem_produto','sem_status','historico_sem_atual','sinais_conflitantes','sem_confirmacao'];
 $filtro=is_string($_GET['filtro']??null)&&in_array($_GET['filtro'],$allowed,true)?$_GET['filtro']:'todos';
 $where=[
   'todos'=>'1=1',
   'sem_produto'=>"(produto IS NULL OR TRIM(produto)='')",
   'sem_status'=>"(status_atual IS NULL OR TRIM(status_atual)='')",
   'historico_sem_atual'=>"(status_pdf IS NOT NULL AND TRIM(status_pdf)<>'' AND (status_atual IS NULL OR TRIM(status_atual)=''))",
   'sinais_conflitantes'=>'(renovado=1 AND nao_vai_renovar=1)',
   'sem_confirmacao'=>"(renovado=0 AND nao_vai_renovar=0 AND cobrado=0 AND (status_atual IS NULL OR TRIM(status_atual)=''))"
 ];
 $stats=['total'=>0,'sem_produto'=>0,'sem_status'=>0,'historico_sem_atual'=>0,'sinais_conflitantes'=>0,'sem_confirmacao'=>0];
 $rows=[];$total=0;$last=null;$page=max(1,min(10000,(int)($_GET['pagina']??1)));$pages=1;
 if($mes){
  $s=$db->prepare("SELECT COUNT(*) total,
   SUM(CASE WHEN produto IS NULL OR TRIM(produto)='' THEN 1 ELSE 0 END) sem_produto,
   SUM(CASE WHEN status_atual IS NULL OR TRIM(status_atual)='' THEN 1 ELSE 0 END) sem_status,
   SUM(CASE WHEN status_pdf IS NOT NULL AND TRIM(status_pdf)<>'' AND (status_atual IS NULL OR TRIM(status_atual)='') THEN 1 ELSE 0 END) historico_sem_atual,
   SUM(CASE WHEN renovado=1 AND nao_vai_renovar=1 THEN 1 ELSE 0 END) sinais_conflitantes,
   SUM(CASE WHEN renovado=0 AND nao_vai_renovar=0 AND cobrado=0 AND (status_atual IS NULL OR TRIM(status_atual)='') THEN 1 ELSE 0 END) sem_confirmacao
   FROM seguro_mensal WHERE competencia=?");
  $s->execute([$mes]);$stats=$s->fetch();
  $query=' FROM seguro_mensal WHERE competencia=:mes AND '.$where[$filtro];
  $s=$db->prepare('SELECT COUNT(*)'.$query);$s->execute(['mes'=>$mes]);$total=(int)$s->fetchColumn();
  $pages=max(1,(int)ceil($total/30));$page=min($page,$pages);
  $s=$db->prepare('SELECT id,linha_origem,segurado,vendedor,vencimento,produto,status_atual,status_pdf,renovado,cobrado,nao_vai_renovar'.$query.' ORDER BY vencimento ASC,linha_origem ASC LIMIT 30 OFFSET :offset');
  $s->bindValue(':mes',$mes,PDO::PARAM_STR);$s->bindValue(':offset',($page-1)*30,PDO::PARAM_INT);
  $s->execute();$rows=$s->fetchAll();
  $s=$db->prepare('SELECT criado_em,registros FROM lote_importacao WHERE competencia=? ORDER BY id DESC LIMIT 1');
  $s->execute([$mes]);$last=$s->fetch();
 }
}catch(Throwable $e){error_log('Conferencia: '.$e->getMessage());http_response_code(503);exit('Conferência indisponível.');}
$labels=['todos'=>'Todos','sem_produto'=>'Produto não informado','sem_status'=>'Status atual não informado','historico_sem_atual'=>'Somente status histórico','sinais_conflitantes'=>'Marcação contraditória','sem_confirmacao'=>'Sem confirmação na planilha'];
$csvUrl='/conferencia.php?'.http_build_query(['mes'=>$mes,'filtro'=>$filtro]);
?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Conferência de dados — Monitor Renova</title>
<style>
*{box-sizing:border-box}body{margin:0;color:#193149;background:#f2f6fa;font:15px system-ui,-apple-system,Segoe UI,sans-serif}header{background:#132d48;color:#fff;padding:24px max(20px,calc((100vw - 1120px)/2))}header a{color:#d5e9fc}header h1{margin:8px 0 0;font-size:25px}main{max-width:1160px;margin:25px auto;padding:0 20px}.panel{background:#fff;border:1px solid #e1e8f0;border-radius:12px;padding:20px;margin-bottom:18px}.note{padding:14px;border:1px solid #eedab1;background:#fff6df;border-radius:8px;margin-bottom:20px}.cards{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin-bottom:20px}.metric{font-weight:750;font-size:27px;margin-top:7px}.muted{color:#64778e}h2{font-size:19px;margin:0 0 12px}.filter{display:flex;flex-wrap:wrap;align-items:end;gap:12px}label{display:block;font-size:13px;margin-bottom:6px}select,button{padding:10px;border-radius:7px;border:1px solid #bdcad9;font:inherit;background:white}button{border-color:#245c8d;background:#245c8d;color:white;cursor:pointer}.tablewrap{overflow:auto}table{border-collapse:collapse;width:100%;min-width:960px}th,td{padding:11px;text-align:left;border-bottom:1px solid #e8eef5;font-size:13px;vertical-align:top}th{color:#64778e}a{color:#145fa9;text-decoration:none}a:hover{text-decoration:underline}.pages{display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-top:15px}@media(max-width:750px){.cards{grid-template-columns:repeat(2,1fr)}}@media(max-width:500px){.cards{grid-template-columns:1fr}}
</style></head><body><header><a href="/">← Voltar ao painel</a><h1>Conferência de dados</h1><p style="margin:6px 0 0;opacity:.8">Identificação de informações que precisam de revisão humana</p></header><main>
<div class="note"><strong>Atenção:</strong> ausência de status, produto ou confirmação não comprova que uma apólice esteja ativa, pendente ou apta para cobrança. Os indicadores podem se sobrepor. Esta página é somente leitura, não altera a planilha nem envia WhatsApp.</div>
<section class="panel"><form method="get" class="filter"><div><label for="mes">Mês</label><select name="mes" id="mes"><?php foreach($months as $m):?><option value="<?=h($m)?>" <?=$m===$mes?'selected':''?>><?=h($m)?></option><?php endforeach;?></select></div>
<div><label for="filtro">Situação para conferir</label><select name="filtro" id="filtro"><?php foreach($labels as $k=>$v):?><option value="<?=h($k)?>" <?=$k===$filtro?'selected':''?>><?=h($v)?></option><?php endforeach;?></select></div><button type="submit">Consultar</button></form>
<?php if($last):?><p class="muted">Última importação mensal registrada: <?=h($last['criado_em'])?> · <?=h($last['registros'])?> registros.</p><?php else:?><p class="muted">Os registros migrados de outubro ainda não têm um lote mensal de importação registrado.</p><?php endif;?></section>
<div class="cards">
<?php foreach(['total'=>'Registros do mês','sem_produto'=>'Sem produto','sem_status'=>'Sem status atual','historico_sem_atual'=>'Apenas status histórico','sinais_conflitantes'=>'Marcações conflitantes','sem_confirmacao'=>'Sem confirmação na planilha'] as $k=>$label):?>
<div class="panel"><div class="muted"><?=h($label)?></div><div class="metric"><?=h(n($stats[$k]??0))?></div></div><?php endforeach;?>
</div>
<section class="panel"><h2><?=h($labels[$filtro])?> — <?=h($total)?> registros</h2><p><a href="/exportar-conferencia.php?<?=h(http_build_query(['mes'=>$mes,'filtro'=>$filtro]))?>">Exportar esta conferência em CSV (Excel) →</a></p><div class="tablewrap"><table><thead><tr><th>Linha</th><th>Segurado</th><th>Vendedor</th><th>Vencimento</th><th>Produto</th><th>Renovado</th><th>Cobrado</th><th>Não vai renovar</th><th>Status atual</th><th>Status histórico (PDF)</th><th>Revisão</th></tr></thead><tbody>
<?php foreach($rows as $r):?><tr><td><?=h($r['linha_origem'])?></td><td><?=h($r['segurado'])?></td><td><?=h($r['vendedor'])?></td><td><?=h($r['vencimento'])?></td><td><?=h($r['produto']??'Não informado')?></td><td><?=n($r['renovado'])?'Sim':'Não indicado'?></td><td><?=n($r['cobrado'])?'Sim':'Não indicado'?></td><td><?=n($r['nao_vai_renovar'])?'Sim':'Não indicado'?></td><td><?=h($r['status_atual']??'Não informado')?></td><td><?=h($r['status_pdf']??'Não consta')?></td><td><a href="/revisar.php?id=<?=h($r['id'])?>">Revisar →</a> · <a href="/validar-status.php?id=<?=h($r['id'])?>">Consultar fonte →</a></td></tr><?php endforeach;?>
<?php if(!$rows):?><tr><td colspan="11">Nenhum registro neste filtro.</td></tr><?php endif;?></tbody></table></div><div class="pages"><span class="muted">Página <?=h($page)?> de <?=h($pages)?> · até 30 registros por página</span><div>
<?php if($page>1):?><a href="<?=h($csvUrl.'&pagina='.($page-1))?>">← Anterior</a><?php endif;?>&nbsp;
<?php if($page<$pages):?><a href="<?=h($csvUrl.'&pagina='.($page+1))?>">Próxima →</a><?php endif;?></div></div></section>
</main></body></html>
