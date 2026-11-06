<?php
/** Página inicial simplificada: vencimentos de hoje e atalhos de vendedores. */
function h(mixed $valor):string {
    return htmlspecialchars((string)$valor,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
}
$activePage='dashboard';
$viewDate=(new DateTimeImmutable($dataHoje))->format('d/m/Y');
$totalHoje=0;
foreach($vencimentosHoje as $linhas) $totalHoje+=count($linhas);
?>
<!doctype html><html lang="pt-BR"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#07523c">
<title>Início · Vencimentos de hoje — MonitorRenova</title>
<link rel="stylesheet" href="/assets/app.css?v=3">
</head><body class="app-shell">
<?php require __DIR__.'/partials/sidebar.php'; ?>
<div class="app-content">
<div class="page-topbar"><span class="topbar-kicker">MonitorRenova / Início</span><span class="topbar-time">Acompanhamento diário de seguros</span></div>
<main class="page-content" id="conteudo">
  <div class="page-titlebar">
    <div>
      <div class="eyebrow">Sua rotina em um só lugar</div>
      <h1>Vencimentos de hoje</h1>
      <p><?=h($viewDate)?> · Consulte e copie os seguros organizados por vendedor.</p>
    </div>
    <a class="btn-link primary-action" href="/vendedores.php">▣ &nbsp; Abrir vendedores</a>
  </div>

  <?php if(!$dbOk):?>
    <div class="syncline stale" role="alert"><strong>Banco indisponível.</strong> Confira os contêineres PHP e MySQL.</div>
  <?php else:?>
    <?php if(!$estadoSheet||!$estadoSheet['recente']):?>
      <div class="syncline stale" role="status">
        <strong>Sincronização a conferir.</strong>
        <?= $estadoSheet?'Última leitura: '.h($estadoSheet['horario']).' (Brasília).':'A última sincronização ainda não foi registrada.'?>
        Os dados mostrados são uma cópia local.
      </div>
    <?php endif;?>

    <div class="daily-stats">
      <div class="daily-stat"><span>Vencimentos de hoje a acompanhar</span><strong><?=h($totalHoje)?></strong></div>
      <div class="daily-stat"><span>Vendedores com seguros a acompanhar</span><strong><?=h(count($vencimentosHoje))?></strong></div>
      <div class="daily-stat"><span>Sem marcações de conclusão</span><strong><?=h($resumoDia['paraConferencia']??0)?></strong></div>
    </div>

    <section class="surface daily-list" aria-labelledby="lista-titulo">
      <div class="surface-head">
        <div><h2 id="lista-titulo">Seguros de hoje para acompanhar por vendedor</h2>
        <p class="surface-sub">Renovados e seguros marcados como não vão renovar saem desta lista automaticamente após salvar.</p></div>
        <a class="mini-link" href="/vencimentos.php">Ver mais datas e gráficos ↗</a>
      </div>
      <?php if(!$vencimentosHoje):?>
        <div class="daily-empty">
          <strong>Nenhum seguro aguardando acompanhamento hoje.</strong>
          <p>Conforme a última cópia dos dados no MySQL.</p>
          <a href="/vendedores.php">Consultar pastas de vendedores →</a>
        </div>
      <?php else:?>
        <div class="daily-sellers">
          <?php foreach($vencimentosHoje as $nomeVendedor=>$seguros):?>
            <?php $texto=["VENCIMENTOS DE HOJE — ".$viewDate,"Vendedor: ".$nomeVendedor,"Verificar a situação atual antes de entrar em contato.",""]; ?>
            <article class="daily-seller">
              <div class="daily-seller-header">
                <div><h3><?=h($nomeVendedor)?></h3><span class="count-bubble"><?=h(count($seguros))?> seguro(s)</span></div>
                <button type="button" class="daily-copy">Copiar lista</button>
              </div>
              <div class="table-scroll">
                <table class="daily-table">
                  <thead><tr><th>Segurado</th><th>Produto</th><th>Situação atual</th></tr></thead><tbody>
                  <?php foreach($seguros as $seguro):?>
                    <?php
                    $segurado=trim((string)($seguro['segurado']??''))?:'Não informado';
                    $produto=trim((string)($seguro['produto']??''))?:'Não informado';
                    $marcas=[];
                    if((int)$seguro['renovado']===1)$marcas[]='Renovado';
                    if((int)$seguro['cobrado']===1)$marcas[]='Cobrado';
                    if((int)$seguro['nao_vai_renovar']===1)$marcas[]='Não vai renovar';
                    $situacao=$marcas?implode(', ',$marcas):'Sem marcações de conclusão';
                    $texto[]='• '.$segurado.' — '.$produto.' ('.$situacao.')';
                    ?>
                    <tr><td><strong><?=h($segurado)?></strong></td><td><?=h($produto)?></td><td><?=h($situacao)?></td></tr>
                  <?php endforeach;?>
                  </tbody>
                </table>
              </div>
              <textarea readonly class="daily-copy-text" aria-label="Lista pronta para copiar de <?=h($nomeVendedor)?>"><?=h(implode("\n",$texto))?></textarea>
              <div class="daily-actions">
                <a href="/vendedores.php?<?=h(http_build_query(['vendedor'=>$nomeVendedor]))?>">Abrir pasta do vendedor →</a>
                <span class="daily-copy-feedback" role="status" aria-live="polite"></span>
              </div>
            </article>
          <?php endforeach;?>
        </div>
      <?php endif;?>
    </section>
    <div class="daily-footer-links">
      <a href="/vendedores.php">▣ &nbsp; Todas as pastas de vendedores</a>
      <a href="/seguros.php">⌕ &nbsp; Pesquisar seguros</a>
      <a href="/vencimentos.php">◷ &nbsp; Outros vencimentos</a>
    </div>
    <p class="hint">As informações vêm do MySQL e podem estar defasadas em relação à planilha. “Sem marcações” não comprova atraso e não autoriza cobrança automática.</p>
  <?php endif;?>
</main></div>
<script>
document.querySelectorAll('.daily-copy').forEach(function(botao){
  botao.addEventListener('click',async function(){
    const cartao=botao.closest('.daily-seller');
    const campo=cartao.querySelector('.daily-copy-text');
    const aviso=cartao.querySelector('.daily-copy-feedback');
    try {
      if(navigator.clipboard && window.isSecureContext){
        await navigator.clipboard.writeText(campo.value);
      } else {
        campo.classList.add('copy-visible');
        campo.focus();campo.select();
        if(!document.execCommand('copy')) throw new Error('Cópia indisponível');
        campo.classList.remove('copy-visible');
      }
      aviso.textContent='Lista copiada!';
    } catch(e) {
      campo.classList.add('copy-visible');
      aviso.textContent='Selecione o texto acima e copie manualmente.';
    }
  });
});
</script>
</body></html>
