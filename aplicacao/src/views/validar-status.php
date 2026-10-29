<!doctype html><html lang="pt-BR"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Validar situação da apólice — Monitor Renova</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#f3f6fa;color:#183149;font:15px system-ui,-apple-system,Segoe UI,sans-serif}
header{background:#122a46;color:#fff;padding:23px max(20px,calc((100vw - 1020px)/2))}
header a{color:#d3eaff}header h1{font-size:23px;margin:10px 0 0}
main{max-width:1040px;margin:24px auto;padding:0 20px}
.card{border:1px solid #dde6ef;border-radius:12px;background:#fff;padding:22px;margin-bottom:18px}
.alert{padding:15px;background:#fff4d8;border:1px solid #f0d9a5;border-radius:8px;margin-bottom:18px}
.ok{padding:13px;background:#e5f4e9;border-radius:8px;margin-bottom:14px}.error{padding:13px;background:#ffeaea;border-radius:8px;margin-bottom:14px}
.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.muted{color:#61758a}
h2{font-size:19px;margin:0 0 16px}h3{font-size:16px;margin:0 0 12px}
label{font-weight:600;display:block;margin:13px 0 6px}
select,textarea,input[type=date]{width:100%;font:inherit;padding:11px;border:1px solid #bfcfdb;border-radius:8px;background:#fff}
button{margin-top:18px;background:#185c8c;color:white;border:0;border-radius:8px;font:inherit;font-weight:600;padding:12px 17px;cursor:pointer}
.checkbox{display:flex;gap:10px;align-items:flex-start;margin-top:15px}.checkbox label{margin:0;font-weight:500}
table{width:100%;border-collapse:collapse}th,td{padding:10px;border-bottom:1px solid #e6edf2;text-align:left;vertical-align:top}th{color:#5b6d82}
a{color:#1460a1;text-decoration:none}header a{color:#d3eaff}.wrap{overflow-x:auto}
@media(max-width:650px){.grid{grid-template-columns:1fr}}
</style>
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
<?php $activePage='validacoes';require __DIR__.'/partials/sidebar.php'; ?>
<div class="app-content">
<div class="page-topbar"><span class="topbar-kicker">MonitorRenova / Conferência manual</span><a class="mini-link" href="/">← Visão geral</a></div>
<main class="page-content">
<div class="page-titlebar"><div><div class="eyebrow">Seguros históricos</div>
<h1>Verificar situação atual da apólice</h1>
<p>Registro de consulta independente na fonte</p></div><a class="btn-link" href="/revisar.php?id=<?=h($id)?>">← Voltar à revisão</a></div>
<div class="form-module">

<div class="alert"><strong>Este é um registro manual de conferência.</strong> Verifique no portal da seguradora, no sistema da corretora ou em documento recente antes de preencher. O status do PDF histórico ou a indicação "renovado" na planilha não substituem essa consulta. <strong>Nenhuma validação nesta página libera envio automático de WhatsApp ou cobrança.</strong></div>
<?php if(isset($_GET['salvo'])):?><div class="ok">Consulta registrada no histórico. Nenhuma cobrança foi autorizada.</div><?php endif;?>
<?php if($error):?><div class="error"><?=h($error)?></div><?php endif;?>
<section class="card"><h2>Seguro de referência</h2><div class="grid">
<div><span class="muted">Segurado</span><p><strong><?=h($seguro['segurado'])?></strong></p></div>
<div><span class="muted">Vendedor</span><p><?=h($seguro['vendedor'])?></p></div>
<div><span class="muted">Competência / linha</span><p><?=h($seguro['competencia'])?> / <?=h($seguro['linha_origem'])?></p></div>
<div><span class="muted">Vencimento</span><p><?=h($seguro['vencimento'])?></p></div>
<div><span class="muted">Produto</span><p><?=h(trim((string)$seguro['produto'])!==''?$seguro['produto']:'Não informado')?></p></div>
<div><span class="muted">Status importado / PDF anterior</span><p><?=h($seguro['status_atual']?:'Não informado')?> / <?=h($seguro['status_pdf']?:'Não consta')?></p></div>
</div></section>
<section class="card"><h2>Última validação independente</h2>
<?php if(!$last):?><p class="muted">Ainda não existe consulta de situação atual registrada por um responsável.</p>
<?php else:?>
<p><strong><?=h($displaySituacao($last['situacao']))?></strong> · consultada em <?=h($last['data_consulta'])?> · <?=h($displayFonte($last['fonte']))?></p>
<p class="muted">Registrado por <?=h($last['login'])?> em <?=h($last['criado_em'])?>.</p>
<?php if($sourceChanged):?><p class="error">Os dados de identificação foram alterados desde esta validação. É necessária nova conferência na fonte.</p>
<?php elseif(!$isRecent):?><p class="alert">Consulta com mais de 7 dias: não considerar como confirmação recente para decisões operacionais.</p>
<?php else:?><p class="muted">Consulta registrada nos últimos 7 dias. Ainda exige validação dos demais critérios antes de qualquer cobrança.</p><?php endif;?>
<?php endif;?>
</section>
<section class="card"><h2>Registrar nova consulta na fonte</h2>
<form method="post">
<input type="hidden" name="csrf" value="<?=h(Auth::csrf())?>">
<input type="hidden" name="versao_origem" value="<?=h($fingerprint)?>">
<input type="hidden" name="versao_validacao" value="<?=h($latestToken)?>">
<div class="grid"><div>
<label for="situacao">Situação efetivamente consultada</label>
<select id="situacao" name="situacao" required><option value="">Selecione</option>
<?php foreach(ValidacaoApolice::SITUACOES as $key=>$label):?><option value="<?=h($key)?>" <?=isset($_POST['situacao'])&&$_POST['situacao']===$key?'selected':''?>><?=h($label)?></option><?php endforeach;?></select>
</div><div>
<label for="fonte">Fonte consultada</label><select id="fonte" name="fonte" required><option value="">Selecione</option>
<?php foreach(ValidacaoApolice::FONTES as $key=>$label):?><option value="<?=h($key)?>" <?=isset($_POST['fonte'])&&$_POST['fonte']===$key?'selected':''?>><?=h($label)?></option><?php endforeach;?></select>
</div></div>
<label for="data_consulta">Data da consulta (últimos 30 dias)</label>
<input type="date" id="data_consulta" name="data_consulta" min="<?=h($today->modify('-30 days')->format('Y-m-d'))?>" max="<?=h($today->format('Y-m-d'))?>" value="<?=h(is_string($_POST['data_consulta']??null)?$_POST['data_consulta']:$today->format('Y-m-d'))?>" required>
<label for="observacao">Observação operacional opcional (até 400 caracteres)</label>
<textarea id="observacao" name="observacao" rows="3" maxlength="400" placeholder="Descreva a conferência de forma objetiva, sem senhas, telefones, CPF ou documentos pessoais."><?=h(is_string($_POST['observacao']??null)?$_POST['observacao']:'')?></textarea>
<div class="checkbox"><input type="checkbox" name="confirmo" value="1" id="confirmo" required><label for="confirmo">Confirmo que consultei uma fonte atual da apólice e estou registrando fielmente o resultado, inclusive quando a situação não pôde ser determinada.</label></div>
<button type="submit">Registrar consulta e guardar histórico</button>
</form></section>
<section class="card"><h2>Histórico de consultas</h2><div class="wrap"><table><thead><tr><th>Registrado em</th><th>Responsável</th><th>Situação</th><th>Fonte</th><th>Data da consulta</th><th>Observação</th></tr></thead><tbody>
<?php foreach($history as $record):?><tr><td><?=h($record['criado_em'])?></td><td><?=h($record['login'])?></td><td><?=h($displaySituacao($record['situacao']))?></td><td><?=h($displayFonte($record['fonte']))?></td><td><?=h($record['data_consulta'])?></td><td><?=h($record['observacao'])?></td></tr><?php endforeach;?>
<?php if(!$history):?><tr><td colspan="6" class="muted">Nenhuma consulta registrada.</td></tr><?php endif;?></tbody></table></div></section>

</div></main></div></body></html>