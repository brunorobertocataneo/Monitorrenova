<?php
declare(strict_types=1);
require_once '/var/www/src/Auth.php'; Auth::requireLogin();
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
Auth::boot();
require_once '/var/www/src/Database.php';
require_once '/var/www/src/SpreadsheetReader.php';
function h(mixed $s):string{return htmlspecialchars((string)$s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function fail(string $message):never{throw new RuntimeException($message);}
if(empty($_SESSION['csrf']))$_SESSION['csrf']=bin2hex(random_bytes(24));
$notice=null;$error=null;
$required=['linha_origem','segurado','vendedor','vencimento','produto','renovado','cobrado','nao_vai_renovar'];
$fields=array_merge($required,['apolice','status_atual','status_pdf','premio_pdf','fonte_pdf']);
if($_SERVER['REQUEST_METHOD']==='POST'){
 try{
  if(!hash_equals($_SESSION['csrf'],(string)($_POST['csrf']??'')))fail('Sessão inválida. Recarregue a página.');
  $action=$_POST['acao']??'';
  if($action==='cancelar'){unset($_SESSION['lote_pendente']);$notice='Prévia descartada; nenhum seguro foi gravado.';}
  elseif($action==='prever'){
   unset($_SESSION['lote_pendente']);
   $mes=(string)($_POST['competencia']??'');
   if(!preg_match('/^20\d{2}-(0[1-9]|1[0-2])$/D',$mes))fail('Selecione uma competência válida.');
   if(!isset($_FILES['arquivo'])||$_FILES['arquivo']['error']!==UPLOAD_ERR_OK||$_FILES['arquivo']['size']>2097152||$_FILES['arquivo']['size']<20)fail('Selecione um CSV ou XLSX válido com até 2 MB.');
   $uploadedName=(string)($_FILES['arquivo']['name']??'');
   $extension=strtolower(pathinfo($uploadedName,PATHINFO_EXTENSION));
   if(!in_array($extension,['csv','xlsx'],true))fail('Aceitamos somente CSV e XLSX nesta versão.');
   $records=SpreadsheetReader::read($_FILES['arquivo']['tmp_name'],$extension);
   $header=array_shift($records);
   if(!$header)fail('Cabeçalho ausente.');
   $header[0]=preg_replace('/^\xEF\xBB\xBF/','',$header[0]);
   $header=array_map(fn($x)=>trim((string)$x),$header);
   if(count(array_unique($header))!==count($header)||array_diff($required,$header)){fail('Colunas obrigatórias ausentes, duplicadas ou separador incorreto. Utilize o modelo CSV.');}
   $data=[];$seen=[];
   foreach($records as $cells){
    if($cells===[null])continue;
    if(count($cells)!==count($header)){fail('Linha com número incorreto de colunas.');}
    $r=array_combine($header,$cells);
    $line=filter_var($r['linha_origem'],FILTER_VALIDATE_INT,['options'=>['min_range'=>2]]);
    $name=trim($r['segurado']);$seller=trim($r['vendedor']);
    $date=trim($r['vencimento']);$dt=DateTimeImmutable::createFromFormat('!Y-m-d',$date);
    if(!$line||isset($seen[$line])||$name===''||$seller===''||strlen($name)>255||strlen($seller)>180||!$dt||$dt->format('Y-m-d')!==$date||substr($date,0,7)!==$mes){fail('Linha com identificador, cliente, vendedor ou vencimento inválido.');}
    foreach(['renovado','cobrado','nao_vai_renovar'] as $flag){$r[$flag]=trim($r[$flag]);if(!in_array($r[$flag],['0','1'],true)){fail('Flags devem ser 0 ou 1.');}}
    $seen[$line]=true;
    foreach($fields as $f)$r[$f]=trim((string)($r[$f]??''));
    $r['linha_origem']=(int)$line;
    $r['apolice']=$r['apolice']===''?null:$r['apolice'];
    $r['produto']=$r['produto']===''?null:$r['produto'];
    $r['status_atual']=$r['status_atual']===''?null:$r['status_atual'];
    $r['status_pdf']=$r['status_pdf']===''?null:$r['status_pdf'];
    $r['fonte_pdf']=$r['fonte_pdf']===''?null:$r['fonte_pdf'];
    if($r['premio_pdf']!==''){
     $money=str_replace(',','.',$r['premio_pdf']);
     if(!preg_match('/^\d{1,10}(\.\d{1,2})?$/D',$money))fail('Prêmio deve ser número com ponto decimal, sem R$.');
     $r['premio_pdf']=$money;
    }else $r['premio_pdf']=null;
    foreach(['apolice'=>100,'produto'=>120,'status_atual'=>30,'status_pdf'=>30,'fonte_pdf'=>255] as $k=>$max)if(strlen((string)$r[$k])>$max)fail("Campo $k excede limite.");
    $data[]=$r;if(count($data)>2000){fail('Máximo de 2000 seguros por arquivo.');}
   }

   if(!$data)fail('Nenhum seguro encontrado.');
   $db=Database::connection();
   $q=$db->prepare('SELECT linha_origem,segurado,vendedor,vencimento FROM seguro_mensal WHERE competencia=?');
   $q->execute([$mes]);$existing=[];
   foreach($q->fetchAll() as $row)$existing[(int)$row['linha_origem']]=$row;
   if($existing && count($data)<ceil(count($existing)*0.85))fail('Quantidade muito abaixo da base existente. Confira o arquivo antes de importar.');
   foreach($data as $r){$old=$existing[$r['linha_origem']]??null;
    if($old&&($old['segurado']!==$r['segurado']||$old['vendedor']!==$r['vendedor']||$old['vencimento']!==$r['vencimento']))fail('Conflito de posição com seguro já registrado. Importação cancelada para evitar troca de apólices.');
   }
   $_SESSION['lote_pendente']=['mes'=>$mes,'registros'=>$data,'hash'=>hash('sha256',json_encode($data,JSON_UNESCAPED_UNICODE)),'origem'=>basename($uploadedName)];
   $notice='Arquivo conferido. Revise os dados abaixo e clique em Confirmar importação.';
  }
  elseif($action==='confirmar'){
   $lote=$_SESSION['lote_pendente']??null;
   if(!$lote||!is_array($lote))fail('Não existe prévia pendente. Envie o CSV primeiro.');
   $db=Database::connection();$db->beginTransaction();
   try{
    $q=$db->prepare('SELECT linha_origem,segurado,vendedor,vencimento FROM seguro_mensal WHERE competencia=? FOR UPDATE');
    $q->execute([$lote['mes']]);$existing=[];foreach($q->fetchAll() as $row)$existing[(int)$row['linha_origem']]=$row;
    if($existing&&count($lote['registros'])<ceil(count($existing)*0.85))fail('Total incompatível com a base existente.');
    foreach($lote['registros'] as $r){$old=$existing[$r['linha_origem']]??null;
     if($old&&($old['segurado']!==$r['segurado']||$old['vendedor']!==$r['vendedor']||$old['vencimento']!==$r['vencimento']))fail('Conflito detectado antes de gravar; transação revertida.');
    }
    $q=$db->prepare('SELECT id FROM lote_importacao WHERE competencia=? AND hash_arquivo=?');$q->execute([$lote['mes'],$lote['hash']]);
    if($q->fetchColumn()){ $db->rollBack();unset($_SESSION['lote_pendente']);$notice='Este mesmo lote já havia sido importado. Nenhuma duplicata criada.'; }
    else{
     $q=$db->prepare('INSERT INTO lote_importacao (competencia,origem,hash_arquivo,registros) VALUES (?,?,?,?)');
     $q->execute([$lote['mes'],'CSV pelo painel: '.substr($lote['origem'],0,110),$lote['hash'],count($lote['registros'])]);
     $batchId=(int)$db->lastInsertId();
     $q=$db->prepare('INSERT INTO seguro_mensal (competencia,linha_origem,apolice,segurado,vendedor,vencimento,produto,renovado,cobrado,nao_vai_renovar,status_atual,status_pdf,premio_pdf,fonte_pdf,lote_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE produto=VALUES(produto),renovado=VALUES(renovado),cobrado=VALUES(cobrado),nao_vai_renovar=VALUES(nao_vai_renovar),status_atual=VALUES(status_atual),status_pdf=VALUES(status_pdf),premio_pdf=VALUES(premio_pdf),fonte_pdf=VALUES(fonte_pdf),lote_id=VALUES(lote_id)');
     foreach($lote['registros'] as $r)$q->execute([$lote['mes'],$r['linha_origem'],$r['apolice'],$r['segurado'],$r['vendedor'],$r['vencimento'],$r['produto'],(int)$r['renovado'],(int)$r['cobrado'],(int)$r['nao_vai_renovar'],$r['status_atual'],$r['status_pdf'],$r['premio_pdf'],$r['fonte_pdf'],$batchId]);
     $db->commit();$notice=count($lote['registros']).' seguros importados para '.$lote['mes'].'. Nenhuma mensagem foi enviada.';unset($_SESSION['lote_pendente']);
    }
   }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
  }else fail('Ação inválida.');
 }catch(Throwable $e){error_log('Importação: '.$e->getMessage());$error=$e->getMessage();}
}
$pending=$_SESSION['lote_pendente']??null;
?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Importação mensal — Monitor Renova</title><style>
*{box-sizing:border-box}body{font:15px system-ui,-apple-system,Segoe UI,sans-serif;margin:0;color:#172c43;background:#f3f6fb}header{background:#122a46;color:white;padding:22px max(20px,calc((100vw - 980px)/2))}header a{color:#d6e8fc}header h1{margin:8px 0 0}main{max-width:1020px;margin:25px auto;padding:0 20px}.panel{background:white;padding:24px;border:1px solid #dfe8f1;border-radius:12px;margin-bottom:18px}.muted{color:#60758a}label{display:block;margin:14px 0 6px}input,button{padding:10px;font:inherit;border-radius:7px;border:1px solid #bdccdc}button{background:#1b5689;color:white;cursor:pointer}button.secondary{background:white;color:#1b5689}.notice{padding:14px;background:#e5f4e9;border:1px solid #b9dbc3;border-radius:9px;margin-bottom:18px}.error{padding:14px;background:#ffeaea;color:#8d2525;border:1px solid #f1bfbf;border-radius:9px;margin-bottom:18px}.alert{padding:14px;background:#fff6e0;border-radius:9px;margin-bottom:18px}.flex{display:flex;gap:12px;flex-wrap:wrap}.tablewrap{overflow:auto}table{border-collapse:collapse;width:100%}th,td{border-bottom:1px solid #e7edf4;padding:11px;text-align:left;white-space:nowrap}th{color:#61758a;font-size:13px}a{color:#125ea5}
</style></head><body><header><a href="/">← Voltar ao painel</a><h1>Importação mensal de seguros</h1><div>CSV / XLSX → Conferência → Confirmação → MySQL</div></header><main>
<div class="alert"><strong>Ambiente local, sem WhatsApp.</strong> Aceita CSV (separador ponto e vírgula) e Excel XLSX (primeira aba) no padrão do sistema, com data AAAA-MM-DD e flags 0/1. Não envie PDF nesta versão. No Excel, a coluna vencimento precisa estar como texto AAAA-MM-DD; datas numéricas e fórmulas não são aceitas. Os meses anteriores são mantidos; este processo não exclui registros automaticamente.</div>
<?php if($notice):?><div class="notice"><?=h($notice)?></div><?php endif;?>
<?php if($error):?><div class="error"><?=h($error)?></div><?php endif;?>
<?php if(!$pending):?><section class="panel"><h2>1. Escolher relatório CSV ou Excel</h2><p class="muted">Prepare um CSV ou XLSX com as colunas indicadas no modelo. Não use dados reais para testar um mês novo.</p>
<p><a href="/modelo-importacao.php">Baixar modelo CSV</a> · <a href="/modelo-excel.php">Baixar modelo Excel (.xlsx)</a></p><form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?=h($_SESSION['csrf'])?>"><input type="hidden" name="acao" value="prever">
<label for="competencia">Mês de competência</label><input type="month" name="competencia" id="competencia" value="2026-11" required>
<label for="arquivo">Arquivo CSV ou XLSX (máximo 2 MB)</label><input type="file" id="arquivo" name="arquivo" accept=".csv,.xlsx,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
<p><button type="submit">Validar e visualizar prévia</button></p></form></section>
<?php else:?><section class="panel"><h2>2. Conferir antes de gravar</h2><p>Competência: <strong><?=h($pending['mes'])?></strong> · Arquivo: <?=h($pending['origem'])?> · Registros: <strong><?=count($pending['registros'])?></strong></p>
<div class="tablewrap"><table><thead><tr><th>Linha</th><th>Segurado</th><th>Vendedor</th><th>Vencimento</th><th>Produto</th></tr></thead><tbody>
<?php foreach(array_slice($pending['registros'],0,15) as $r):?><tr><td><?=h($r['linha_origem'])?></td><td><?=h($r['segurado'])?></td><td><?=h($r['vendedor'])?></td><td><?=h($r['vencimento'])?></td><td><?=h($r['produto']??'—')?></td></tr><?php endforeach;?></tbody></table></div><p class="muted">Mostrando os primeiros 15 registros. Todos foram validados antes da prévia.</p>
<div class="flex"><form method="post"><input type="hidden" name="csrf" value="<?=h($_SESSION['csrf'])?>"><input type="hidden" name="acao" value="confirmar"><button type="submit">Confirmar importação para MySQL</button></form>
<form method="post"><input type="hidden" name="csrf" value="<?=h($_SESSION['csrf'])?>"><input type="hidden" name="acao" value="cancelar"><button class="secondary" type="submit">Cancelar sem gravar</button></form></div></section><?php endif;?>
<p class="muted">Importação manual supervisionada. Histórico mensal em <a href="/meses.php">Histórico mensal</a>.</p></main></body></html>
