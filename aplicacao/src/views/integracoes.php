<?php
/**
 * View do módulo integracoes. Apenas apresentação e escape HTML.
 * Os Controllers e o Repository fazem a validação e o acesso ao banco.
 */
function h(mixed $valor):string {
    return htmlspecialchars((string)$valor,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
}
?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Integrações — Monitor Renova</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#f3f6fb;color:#1c314a;font:15px system-ui,-apple-system,Segoe UI,sans-serif}
header{background:#122a46;color:white;padding:24px max(20px,calc((100vw - 1050px)/2))}header a{color:#d9edff}header h1{font-size:25px;margin:9px 0 4px}
main{max-width:1100px;margin:25px auto;padding:0 20px}
.card{background:white;border:1px solid #dde6f0;border-radius:12px;padding:23px;margin-bottom:18px}
.grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}
.muted{color:#60758a}.number{font-size:28px;font-weight:750;margin-top:10px}
.note{border:1px solid #efd69e;background:#fff5da;border-radius:9px;padding:15px;margin-bottom:18px}
a{color:#1660a8}h2{margin:0 0 12px;font-size:19px}h3{margin:0 0 7px;font-size:16px}
small{line-height:1.5}code{background:#f0f3f9;padding:2px 5px;border-radius:4px}
@media(max-width:750px){.grid{grid-template-columns:1fr}}
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
<?php $activePage='integracoes'; require __DIR__.'/partials/sidebar.php'; ?>
<div class="app-content">
<div class="page-topbar"><span class="topbar-kicker">MonitorRenova / Diagnóstico operacional</span><a class="mini-link" href="/">← Visão geral</a></div>
<main class="page-content">
<div class="page-titlebar"><div><div class="eyebrow">Diagnóstico operacional</div><h1>Integrações e segurança</h1><p>Diagnóstico de conexões, credenciais protegidas e automações locais.</p></div></div>
<div class="module-layout">
<div class="note"><strong>Segurança dos envios:</strong> o resumo diário das 9h está ativo <strong>somente para o WhatsApp do próprio responsável</strong>. Envios de cobrança a clientes ou vendedores continuam desativados. O estado de conexão exibido abaixo é o do último diagnóstico local, não uma confirmação de entrega de mensagens. A situação das apólices deve ser verificada na origem.</div>
<div class="grid">
<section class="card"><h3>Evolution API / WhatsApp</h3><div class="number" style="font-size:21px"><?=h($title)?></div><p class="muted"><?=h($wa['checked_at']??'Sem data')?></p><small><?=h($wa['message'])?><?php if(isset($wa['age_minutes'])):?> (há <?=h($wa['age_minutes'])?> min)<?php endif;?></small></section>
<section class="card"><h3>n8n — consulta WhatsApp</h3><div class="number" style="font-size:21px">Consulta autenticada testada</div><p>Fluxo manual <strong>DIAGNÓSTICO WhatsApp — SOMENTE CONSULTA</strong>.</p><small>O n8n executou com a credencial Header Auth e confirmou a conexão. Nenhuma chave foi armazenada neste painel.</small></section>
<section class="card"><h3>Resumo automático das 9h</h3><div class="number" style="font-size:21px">Ativo para o responsável</div><p>Envia a lista nominal de vencimentos para a conta própria conectada, em dias úteis, usando a mesma competência aprovada no MySQL que alimenta o dashboard PHP.</p><small>Se a competência não estiver aprovada ou a sincronização estiver desatualizada, o resumo é bloqueado.</small><small>Não envia a vendedores ou clientes. Esta página não dispara mensagens.</small></section>
</div>
<section class="card">
<h2>Integração de auditorias: n8n → PHP</h2>
<div class="grid">
<div><h3>Conexão interna</h3><p><strong>Testada</strong></p><small>O n8n alcança o PHP pela rede Docker; o acesso sem chave recebe HTTP 401.</small></div>
<div><h3>Chave de auditoria do PHP</h3><p><strong><?=$ingestReady?'Configurada':'Pendente'?></strong></p><small>O segredo de integração não aparece nesta página.</small></div>
<div><h3>Auditoria antiga</h3><p><strong>Sem envios</strong></p><small>O fluxo histórico de auditoria permanece sem disparos. O monitoramento atual das execuções está disponível na página de histórico do n8n.</small></div>
</div>
<?php if($ultimaAuditoriaN8n):?>
<p>Última auditoria recebida do n8n: <strong>#<?=h($ultimaAuditoriaN8n['id'])?></strong>, <?=h($ultimaAuditoriaN8n['status'])?>, <?=h($ultimaAuditoriaN8n['iniciada_em'])?>. Registros processados: <?=h($ultimaAuditoriaN8n['total_processados'])?>; candidatos preliminares: <?=h($ultimaAuditoriaN8n['total_elegiveis'])?>; pendentes: <?=h($ultimaAuditoriaN8n['total_pendentes'])?>; enviados: <?=h($ultimaAuditoriaN8n['total_enviados'])?>.</p>
<?php else:?>
<p class="muted">Nenhuma auditoria completa foi recebida do n8n por esta integração. Os resultados da auditoria local estão disponíveis na <a href="/prontidao.php">Prontidão das renovações</a>.</p>
<?php endif;?>
<?php if($ultimoEstadoAuditoria):?>
<p><strong>Última tentativa de leitura do histórico no n8n:</strong> execução #<?=h($ultimoEstadoAuditoria['execution_id']??'?')?>, em <?=h($ultimoEstadoAuditoria['attempted_at']??'sem horário')?>. <?=($ultimoEstadoAuditoria['error_category']??null)==='RATE_LIMIT'?'O Google Sheets respondeu com limite de requisições; a auditoria completa não terminou.':'O n8n registrou um erro; confira a execução antes de utilizar os dados.'?> Este é um registro da última tentativa, não monitoramento contínuo.</p>
<?php endif;?>
<p class="muted">O histórico técnico atual é coletado localmente com autenticação por token; a chave não aparece nesta página. A confirmação de entrega deve ser feita no WhatsApp. Não habilite envios de cobrança para terceiros sem autorização e validação das apólices.</p>
</section>
<section class="card">
<h2>Histórico real de execuções do n8n</h2>
<?php if($historicoResumo):?>
<div class="grid">
<div><h3>Execuções monitoradas</h3><div class="number"><?=h($historicoResumo['total'])?></div></div>
<div><h3>Envios aceitos pela API</h3><div class="number"><?=h($historicoResumo['aceitosApi'])?></div><small>Não comprova entrega no telefone.</small></div>
<div><h3>Falhas registradas</h3><div class="number"><?=h($historicoResumo['falhas'])?></div></div>
</div>
<p><strong>Sincronizações confirmadas por commit no MySQL:</strong> <?=h($historicoResumo['mysqlConfirmadas'])?>.</p>
<p class="muted">Última coleta: <?=h($historicoResumo['coletadoEm']??'Aguardando')?>.
<?= $historicoResumo['coletaRecente']?'Dados recentes.':'Dados possivelmente desatualizados.' ?></p>
<?php else:?><p class="muted">Histórico indisponível; verificar o serviço de coleta.</p><?php endif;?>
<p><a href="/historico-n8n.php">Abrir histórico detalhado de execuções →</a></p>
</section>
<section class="card"><h2>Auditoria dos dados — outubro de 2026</h2>
<?php if(!$dbOK):?><p>Banco indisponível para a consulta.</p><?php else:?>
<div class="grid">
<div><div class="muted">Seguros no arquivo mensal</div><div class="number"><?=h($stats['total']??0)?></div></div>
<div><div class="muted">Sem situação atual informada</div><div class="number"><?=h($stats['sem_status']??0)?></div></div>
<div><div class="muted">Sem produto informado</div><div class="number"><?=h($stats['sem_produto']??0)?></div></div>
</div>
<p class="muted">Esses valores vêm do MySQL; não confirmam que a apólice esteja ativa, nem que seja permitido realizar cobrança. <a href="/validacoes.php?mes=2026-10">Abrir fila de confirmação das apólices →</a></p>
<?php endif;?>
</section>
<section class="card"><h2>Teste de WhatsApp com destinatário controlado</h2><p>O fluxo <strong>TESTE WHATSAPP — RASCUNHO BLOQUEADO (SEM ENVIOS)</strong> está preparado no n8n. Ele cria uma mensagem fictícia, sem número de telefone, e verifica o bloqueio de envio. Seu nó HTTP POST está desativado e desconectado.</p><p>Uma mensagem neutra de teste foi enviada à própria conta conectada, e o recebimento foi confirmado. O teste não autoriza disparos adicionais. Nenhum cliente foi contatado por esse teste.</p></section>
<section class="card"><h2>Verificação manual de conexão no n8n</h2>
<p>1. Abra <a href="http://localhost:5678" target="_blank" rel="noopener noreferrer">n8n</a> no computador que hospeda o Docker.</p>
<p>2. Entre no fluxo <strong>DIAGNÓSTICO WhatsApp — SOMENTE CONSULTA</strong>.</p>
<p>3. No nó <strong>CONSULTAR STATUS — GET SEM ENVIOS</strong>, a credencial <strong>Header Auth account</strong> já está configurada. Não compartilhe sua chave.</p>
<p>4. Execute manualmente o diagnóstico. O resultado esperado, enquanto o WhatsApp estiver conectado, é <code>open</code>.</p>
<p>5. Mantenha o fluxo de cobranças inativo. <a href="/prontidao.php">Confira a auditoria local das renovações</a>; o resultado completo do n8n ainda requer a leitura do histórico no Google Sheets.</p>
</section>
</div>
</main>
</div>
</body></html>
