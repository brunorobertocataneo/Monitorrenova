<!doctype html><html lang="pt-BR"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Revisar seguro — Monitor Renova</title>
<style>body{font:15px system-ui,-apple-system,Segoe UI,sans-serif;background:#f3f6fa;color:#20334a;margin:0}header{background:#122a46;color:white;padding:22px max(20px,calc((100vw - 940px)/2))}header a{color:#e0efff}main{max-width:980px;margin:24px auto;padding:0 18px}.panel{border-radius:12px;border:1px solid #dce5f0;background:white;padding:22px;margin:18px 0}.info{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.info p{margin:6px 0}.muted{color:#63758a}.alert{background:#fff4df;border:1px solid #eed8b1;border-radius:8px;padding:12px}.error{background:#ffe7e7;padding:12px;border-radius:8px}.ok{background:#e3f2e7;padding:12px;border-radius:8px}label{display:block;margin:17px 0 6px;font-weight:600}select,textarea,button{font:inherit;border:1px solid #c4d0dd;border-radius:7px;padding:10px}textarea{width:100%;box-sizing:border-box}button{margin-top:15px;background:#165889;color:white;border:0;cursor:pointer}table{border-collapse:collapse;width:100%}th,td{text-align:left;border-bottom:1px solid #e6edf4;padding:10px;vertical-align:top;overflow-wrap:anywhere}.wrap{overflow-x:auto}h1{margin:7px 0 0;font-size:25px}@media(max-width:600px){.info{grid-template-columns:1fr}}</style>
<link rel="stylesheet" href="/assets/app.css?v=1">
<style>
.app-shell .form-module .panel,.app-shell .form-module .card{box-shadow:0 3px 14px #19365009}
.app-shell .form-module{max-width:1080px;margin:0 auto;min-width:0}
.app-shell .form-module .wrap{overflow-x:auto}
.app-shell .form-module table{min-width:740px}
.app-shell .form-module input:focus-visible,.app-shell .form-module textarea:focus-visible{outline:3px solid #78bbc4;outline-offset:2px}
@media(max-width:650px){.app-shell .form-module .grid,.app-shell .form-module .info{grid-template-columns:1fr}}
</style></head>
<body class="app-shell">
<?php $activePage='acompanhamento';require __DIR__.'/partials/sidebar.php'; ?>
<div class="app-content">
<div class="page-topbar"><span class="topbar-kicker">MonitorRenova / Conferência manual</span><a class="mini-link" href="/">← Visão geral</a></div>
<main class="page-content">
<div class="page-titlebar"><div><div class="eyebrow">Seguros históricos</div>
<h1>Revisar seguro #<?=h($seguro['linha_origem'])?></h1>
<p>Conferência interna de uma apólice do arquivo histórico</p></div><a class="btn-link" href="/acompanhamento.php?mes=<?=h($seguro['competencia'])?>">← Acompanhamento</a></div>
<div class="form-module">

<div class="alert"><strong>Este formulário registra somente o andamento da conferência interna.</strong> Não altera status da apólice, não confirma elegibilidade para cobrança e não modifica o Google Sheets. Para registrar a situação atual verificada na fonte, use <a href="/validar-status.php?id=<?=h($id)?>">Verificar apólice na fonte →</a>.</div>
<?php if($error):?><p class="error"><?=h($error)?></p><?php endif;?><?php if(isset($_GET['salvo'])):?><p class="ok">Revisão registrada e histórico atualizado.</p><?php endif;?>
<section class="panel"><h2>Dados de referência (somente leitura)</h2><div class="info">
<p><span class="muted">Segurado</span><br><strong><?=h($seguro['segurado'])?></strong></p>
<p><span class="muted">Vendedor</span><br><strong><?=h($seguro['vendedor'])?></strong></p>
<p><span class="muted">Competência / Vencimento</span><br><?=h($seguro['competencia'])?> / <?=h($seguro['vencimento'])?></p>
<p><span class="muted">Produto</span><br><?=h($seguro['produto']??'Não informado')?></p>
<p><span class="muted">Status atual da fonte</span><br><?=h($seguro['status_atual']??'Não verificado')?></p>
<p><span class="muted">Status histórico do PDF</span><br><?=h($seguro['status_pdf']??'Não consta')?></p>
</div></section>
<section class="panel"><h2>Registrar revisão</h2><form method="post">
<input type="hidden" name="csrf" value="<?=h(Auth::csrf())?>"><input type="hidden" name="versao" value="<?=h($versao)?>">
<label for="situacao">Andamento da conferência</label><select id="situacao" name="situacao">
<?php foreach($allowed as $key=>$label):?><option value="<?=h($key)?>" <?=$key===($rev['situacao']??'pendente')?'selected':''?>><?=h($label)?></option><?php endforeach;?></select>
<label for="observacao">Observação (até 500 caracteres, não registre dados sensíveis)</label>
<textarea id="observacao" name="observacao" rows="4" maxlength="500" placeholder="Ex.: falta verificar o produto com a equipe."><?=h($rev['observacao']??'')?></textarea>
<p><button type="submit">Salvar revisão e registrar histórico</button></p></form></section>
<section class="panel"><h2>Histórico de revisão</h2><div class="wrap"><table><thead><tr><th>Data</th><th>Responsável</th><th>Antes</th><th>Depois</th><th>Observação</th></tr></thead><tbody>
<?php foreach($history as $entry):?><tr><td><?=h($entry['criado_em'])?></td><td><?=h($entry['login']??'Conta indisponível')?></td><td><?=h($entry['situacao_anterior']??'Sem revisão')?></td><td><?=h($entry['situacao_nova'])?></td><td><?=h($entry['observacao_nova'])?></td></tr><?php endforeach;?>
<?php if(!$history):?><tr><td colspan="5" class="muted">Nenhuma revisão registrada.</td></tr><?php endif;?></tbody></table></div></section>

</div></main></div></body></html>