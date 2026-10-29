<?php
/**
 * View do módulo competencias. Apenas apresentação e escape HTML.
 * Os Controllers e o Repository fazem a validação e o acesso ao banco.
 */
function h(mixed $valor):string {
    return htmlspecialchars((string)$valor,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
}
?><!doctype html>
<html lang="pt-BR"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Competências mensais — MonitorRenova</title>
<style>
*{box-sizing:border-box}body{font:15px system-ui,-apple-system,"Segoe UI",sans-serif;color:#1b3048;background:#f2f5fa;margin:0}
header{background:#142f4d;color:#fff;padding:24px max(20px,calc((100vw - 1060px)/2))}
header a{color:#cbe1fb}h1{font-size:25px;margin:8px 0 4px}
main{max-width:1090px;margin:26px auto;padding:0 20px}
.panel{background:#fff;padding:23px;border:1px solid #dce5ef;border-radius:12px;margin-bottom:16px}
h2{margin:0 0 12px;font-size:19px}.muted{color:#60748a}
.stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:13px}
.metric{display:block;font-size:24px;font-weight:750;margin-top:7px}
table{border-collapse:collapse;width:100%}td,th{text-align:left;border-bottom:1px solid #e6edf4;padding:12px 9px}
th{font-size:13px;color:#64748b}.scroll{overflow-x:auto}
.status{display:inline-block;padding:5px 9px;border-radius:18px;background:#f2f4f7;color:#44546a}
.status.pendente{background:#fff1d5;color:#805a13}.status.autorizada{background:#e8f1ff;color:#20598d}.status.concluida{background:#e6f5e9;color:#237144}
.warning{padding:14px;background:#fff7e7;border:1px solid #ead5ab;border-radius:9px;margin-bottom:15px}
.good{padding:14px;background:#eaf7ef;border:1px solid #cbe8d3;border-radius:9px;margin-bottom:15px}
button{font:inherit;padding:9px 13px;border:0;border-radius:8px;background:#1e6098;color:#fff;cursor:pointer}
button:disabled{opacity:.55;cursor:not-allowed}a{color:#155b9c}
@media(max-width:700px){.stats{grid-template-columns:1fr}}
</style>
<link rel="stylesheet" href="/assets/app.css?v=1">
<style>
/* Ajustes mínimos: conteúdo original preservado dentro da nova estrutura */
.app-shell .module-layout .card,.app-shell .module-layout .panel{box-shadow:0 3px 14px #19365009}
.app-shell .module-layout{min-width:0}
.app-shell .module-layout table{min-width:660px}
.app-shell .module-layout .stats,.app-shell .module-layout .grid,.app-shell .module-layout .metrics{gap:13px}
.app-shell .module-layout h2{font-size:18px}
.app-shell .module-layout a:focus-visible{outline:3px solid #61aac4;outline-offset:2px}
@media(max-width:700px){.app-shell .module-layout .grid{grid-template-columns:1fr}.app-shell .module-layout .stats{grid-template-columns:1fr}.app-shell .module-layout .metrics{grid-template-columns:repeat(2,minmax(0,1fr))}}
</style>
</head>
<body class="app-shell">
<?php $activePage='competencias'; require __DIR__.'/partials/sidebar.php'; ?>
<div class="app-content">
<div class="page-topbar"><span class="topbar-kicker">MonitorRenova / Gestão mensal</span><a class="mini-link" href="/">← Visão geral</a></div>
<main class="page-content">
<div class="page-titlebar"><div><div class="eyebrow">Gestão mensal</div><h1>Gerenciador de competências</h1><p>Controle de meses aprovados, transições e histórico preservado.</p></div></div>
<div class="module-layout">
<?php if(isset($erro)):?><div class="warning"><?=h($erro)?></div><?php endif;?>
<?php if($ok):?><div class="good">Nova competência autorizada. O n8n concluirá a troca na próxima sincronização se os dados permanecerem idênticos aos aprovados.</div><?php endif;?>
<div class="stats">
<section class="panel"><span class="muted">Competência ativa</span><strong class="metric"><?=h($dados['ativa'])?></strong></section>
<section class="panel"><span class="muted">Seguros ativos na cópia</span><strong class="metric"><?=h($dados['registrosAtivos'])?></strong></section>
<section class="panel"><span class="muted">Última sincronização</span><strong class="metric" style="font-size:18px"><?=h($dados['sincronizadoEm'])?></strong>
<?php if(!$dados['sincronizacaoRecente']):?><small class="muted">Atualização desatualizada; conferir Docker/n8n.</small><?php endif;?></section>
</div>
<section class="panel">
<h2>Como funciona a troca de mês</h2>
<p>Se você substituir os registros na <strong>mesma planilha Google Sheets</strong>, mantendo a aba <code>_BASE</code> e as colunas esperadas, o MonitorRenova detectará o próximo mês automaticamente.</p>
<p>Para proteger os seguros, o sistema exige <strong>duas leituras idênticas</strong> e sua autorização para ativar a nova competência. Até essa autorização, o dashboard permanece na competência anterior. Nenhum arquivo mensal é apagado.</p>
<div class="warning"><strong>Atenção:</strong> uma planilha com meses misturados ou uma substituição incompleta será bloqueada no PHP. Se criar outro arquivo Google Sheets com outro link, precisaremos configurar o novo ID no n8n. <strong>O resumo do WhatsApp das 9h utiliza exclusivamente a competência aprovada no MySQL</strong>, assim como o dashboard. A substituição da planilha não altera o resumo até a aprovação da nova competência. Se o mês atual ainda não estiver aprovado, ou a sincronização estiver desatualizada, o envio será bloqueado em vez de apresentar informações antigas.</div>
</section>
<section class="panel">
<h2>Trocas detectadas</h2>
<?php if(!$dados['transicoes']):?><p class="muted">Nenhuma nova competência detectada. A competência <?=h($dados['ativa'])?> permanece ativa.</p><?php endif;?>
<?php foreach($dados['transicoes'] as $t):?>
<div style="border-top:1px solid #e9edf3;padding:15px 0">
<strong><?=h($t['competencia'])?></strong> — <span class="status <?=h($t['estado'])?>"><?=h($t['estado'])?></span>
<p class="muted" style="margin:9px 0">Registros encontrados: <?=h($t['total_detectado'])?> · Leituras consecutivas idênticas: <?=h($t['leituras_iguais'])?> · Última observação: <?=h($t['ultima_leitura'])?></p>
<?php if($t['estado']==='pendente'):?>
  <form method="post" style="margin:11px 0">
    <input type="hidden" name="csrf" value="<?=h(Auth::csrf())?>">
    <input type="hidden" name="competencia" value="<?=h($t['competencia'])?>">
    <button type="submit" <?=((int)$t['leituras_iguais']<2)?'disabled':''?>>Autorizar ativação de <?=h($t['competencia'])?></button>
  </form>
  <small class="muted">Confirme os dados na planilha antes de aprovar. Se o lote mudar após aprovação, uma nova conferência será exigida.</small>
<?php elseif($t['estado']==='autorizada'):?>
  <p>Aguardando o n8n receber novamente o lote autorizado para ativar <?=h($t['competencia'])?>.</p>
<?php else:?>
  <p class="muted">Ativação concluída em <?=h($t['concluida_em'])?>, com o arquivo anterior preservado.</p>
<?php endif;?>
</div>
<?php endforeach;?>
</section>
<section class="panel">
<h2>Arquivo mensal preservado</h2>
<div class="scroll"><table>
<thead><tr><th>Competência</th><th>Seguros arquivados</th><th>Disponibilidade</th></tr></thead>
<tbody><?php foreach($dados['arquivo'] as $row):?>
<tr><td><?=h($row['competencia'])?></td><td><?=h($row['total'])?></td>
<td><?= $row['competencia']===$dados['ativa']?'Competência ativa':'Histórico protegido' ?></td></tr>
<?php endforeach;?></tbody>
</table></div>
<p class="muted">Os arquivos permanecem no MySQL local. O histórico de importações manuais é independente desta cópia sincronizada.</p>
</section>
</div>
</main>
</div>
</body></html>
