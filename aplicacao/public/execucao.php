<?php
declare(strict_types=1);
require_once '/var/www/src/Auth.php'; Auth::requireLogin();
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
require_once '/var/www/src/Database.php';
function h(mixed $v): string { return htmlspecialchars((string)$v, ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
$id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
if(!$id){http_response_code(400);exit('Identificador de execução inválido.');}
try{
 $db=Database::connection();
 $q=$db->prepare('SELECT e.*,a.nome AS automacao,a.sistema_origem FROM execucao e JOIN automacao a ON a.id=e.automacao_id WHERE e.id=?');
 $q->execute([$id]);$e=$q->fetch();
 if(!$e){http_response_code(404);exit('Execução não encontrada.');}
 $q=$db->prepare('SELECT nivel,tipo,descricao,criado_em FROM evento_execucao WHERE execucao_id=? ORDER BY id DESC');
 $q->execute([$id]);$events=$q->fetchAll();
}catch(Throwable $ex){error_log($ex->getMessage());http_response_code(503);exit('Banco temporariamente indisponível.');}
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Execução #<?=h($id)?> — Monitor Renova</title>
<style>body{font-family:system-ui,sans-serif;background:#f3f6fb;color:#17263a;margin:0}header{background:#122a46;color:white;padding:25px}main{max-width:940px;margin:30px auto;padding:0 18px}.panel{background:white;border:1px solid #e3e9f0;border-radius:12px;padding:22px;margin-bottom:18px}h1{margin:0;font-size:24px}dt{color:#68778a;font-size:13px}dd{margin:4px 0 16px;font-weight:600;overflow-wrap:anywhere}dl{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px}table{width:100%;border-collapse:collapse}th,td{text-align:left;padding:11px;border-bottom:1px solid #eee;overflow-wrap:anywhere}a{color:#1460ae}header a{color:#cce1ff}.muted{color:#67768a}@media(max-width:650px){dl{grid-template-columns:repeat(2,minmax(0,1fr))}}</style>
</head><body><header><a href="/">← Voltar ao painel</a><h1>Execução #<?=h($id)?></h1></header><main>
<section class="panel"><h2>Resumo</h2><dl>
<dt>Automação<dd><?=h($e['automacao'])?></dd></dt>
<dt>Status<dd><?=h($e['status'])?></dd></dt>
<dt>Referência externa<dd><?=h($e['identificador_externo']??'—')?></dd></dt>
<dt>Início<dd><?=h($e['iniciada_em'])?></dd></dt>
<dt>Término<dd><?=h($e['terminada_em']??'—')?></dd></dt>
<dt>Processados<dd><?=h($e['total_processados'])?></dd></dt>
<dt>Elegíveis<dd><?=h($e['total_elegiveis'])?></dd></dt>
<dt>Pendentes<dd><?=h($e['total_pendentes'])?></dd></dt>
<dt>Enviados<dd><?=h($e['total_enviados'])?></dd></dt>
</dl><?php if(!empty($e['mensagem_erro'])):?><p><strong>Erro:</strong> <?=h($e['mensagem_erro'])?></p><?php endif;?></section>
<section class="panel"><h2>Eventos desta execução</h2><div style="overflow-x:auto"><table><thead><tr><th>Data</th><th>Nível</th><th>Tipo</th><th>Descrição</th></tr></thead><tbody>
<?php foreach($events as $item):?><tr><td><?=h($item['criado_em'])?></td><td><?=h($item['nivel'])?></td><td><?=h($item['tipo'])?></td><td><?=h($item['descricao'])?></td></tr><?php endforeach;?>
<?php if(!$events):?><tr><td colspan="4" class="muted">Nenhum evento associado.</td></tr><?php endif;?>
</tbody></table></div></section></main></body></html>