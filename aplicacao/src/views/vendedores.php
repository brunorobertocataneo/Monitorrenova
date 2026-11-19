<?php
function h(mixed $v):string {return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function situacaoPlanilha(mixed $codigo):array {
    $valor=strtoupper(trim((string)$codigo));
    $rotulos=[
        'PENDENTE'=>['Pendente','is-pending'],
        'RENOVADO'=>['Renovado','is-renewed'],
        'NAO_RENOVA'=>['Não vai renovar','is-no-renewal'],
        'NAO_VAI_RENOVAR'=>['Não vai renovar','is-no-renewal'],
        'COBRADO'=>['Cobrado','is-charged'],
        'ATIVO'=>['Ativo','is-renewed'],
        'CANCELADO'=>['Cancelado','is-no-renewal']
    ];
    if($valor==='')return ['Sem confirmação','is-neutral'];
    return $rotulos[$valor]??[str_replace('_',' ',(string)$codigo),'is-neutral'];
}
$activePage='vendedores';
$pagina=(int)($resultado['pagina']??1);
$totalPaginas=(int)($resultado['totalPaginas']??1);
$rows=$resultado['registros']??[];
$params=['vendedor'=>$vendedorAtual,'busca'=>$busca];
?>
<!doctype html><html lang="pt-BR"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#07523c">
<title>Vendedores — MonitorRenova</title>
<link rel="stylesheet" href="/assets/app.css?v=7">
</head><body class="app-shell">
<?php require __DIR__.'/partials/sidebar.php'; ?>
<div class="app-content">
<div class="page-topbar"><span class="topbar-kicker">MonitorRenova / Vendedores</span><a class="mini-link" href="/">⌂ Início</a></div>
<main class="page-content">
  <div class="page-titlebar">
    <div><div class="eyebrow">Pastas de vendedores</div>
    <h1><?=h($vendedorAtual!==''?$vendedorAtual:'Vendedores')?></h1>
    <p><?= $vendedorAtual!==''?'Seguros deste vendedor organizados como na planilha.':'Selecione uma pasta para consultar os seguros do vendedor.'?></p></div>
    <?php if($vendedorAtual!==''):?><a class="btn-link" href="/vendedores.php">← Todos os vendedores</a><?php endif;?>
  </div>
  <?php if($erro):?><div class="note-banner" role="alert"><?=h($erro)?></div><?php endif;?>
  <?php if($sucesso):?><div class="syncline" role="status"><?=h($sucesso)?></div><?php endif;?>
  <?php if($vendedorAtual===''):?>
    <section class="surface vendor-explorer" aria-labelledby="vendor-explorer-title">
      <div class="surface-head">
        <div><h2 id="vendor-explorer-title">Pastas dos vendedores</h2><p class="surface-sub">Pastas virtuais, atualizadas automaticamente conforme a competência ativa do MySQL.</p></div>
        <span class="tag"><?=h(count($vendedores))?> vendedores</span>
      </div>
      <div class="vendor-explorer-toolbar">
        <div class="vendor-explorer-search">
          <label for="buscar-vendedor">Localizar vendedor</label>
          <div class="vendor-explorer-search-field">
            <svg aria-hidden="true" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="10.7" cy="10.7" r="6.7"/><path d="m16 16 5 5"/></svg>
            <input id="buscar-vendedor" type="search" placeholder="Pesquise pelo nome do vendedor" autocomplete="off" maxlength="180" aria-controls="vendor-folder-list">
          </div>
        </div>
        <span class="vendor-explorer-summary" id="vendedores-visiveis" role="status" aria-live="polite"><?=h(count($vendedores))?> pastas encontradas</span>
      </div>
      <div class="vendor-folder-grid" id="vendor-folder-list">
        <?php foreach($vendedores as $nome):?>
          <?php $dadosPasta=$estatisticasVendedores[$nome]??['total'=>0,'pendentes'=>0]; ?>
          <a class="vendor-folder-card" data-vendor-name="<?=h(mb_strtolower((string)$nome,'UTF-8'))?>" href="/vendedores.php?<?=h(http_build_query(['vendedor'=>$nome]))?>" aria-label="Abrir pasta de <?=h($nome)?>: <?=h($dadosPasta['total'])?> seguros, <?=h($dadosPasta['pendentes'])?> pendentes">
            <span class="vendor-folder-icon" aria-hidden="true">
              <svg viewBox="0 0 48 48" width="44" height="44" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M5 12a4 4 0 0 1 4-4h10l4 5h16a4 4 0 0 1 4 4v20a4 4 0 0 1-4 4H9a4 4 0 0 1-4-4V12Z" fill="#e3ab43"/>
                <path d="M5 20a4 4 0 0 1 4-4h30a4 4 0 0 1 4 4v16a5 5 0 0 1-5 5H10a5 5 0 0 1-5-5V20Z" fill="#f3cb70"/>
                <path d="M9 21h30" stroke="#e3aa4d" stroke-width="1.4" stroke-linecap="round"/>
              </svg>
            </span>
            <span class="vendor-folder-info">
              <strong class="vendor-folder-name"><?=h($nome)?></strong>
              <span class="vendor-folder-metrics"><?=h($dadosPasta['total'])?> seguro(s) <span aria-hidden="true">·</span> <?=h($dadosPasta['pendentes'])?> pendente(s)</span>
            </span>
            <svg class="vendor-folder-arrow" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="m9 5 7 7-7 7"/></svg>
          </a>
        <?php endforeach;?>
      </div>
      <?php if(!$vendedores):?><p class="vendor-folder-empty">Ainda não há vendedores cadastrados nesta competência.</p><?php endif;?>
      <?php if($vendedores):?><p class="vendor-folder-empty" id="vendor-no-results" hidden>Nenhuma pasta corresponde à pesquisa. Experimente outro nome.</p><?php endif;?>
    </section>
    <script>
      (() => {
        const field=document.getElementById('buscar-vendedor');
        const folders=[...document.querySelectorAll('#vendor-folder-list [data-vendor-name]')];
        const counter=document.getElementById('vendedores-visiveis');
        const empty=document.getElementById('vendor-no-results');
        if(!field||!counter)return;
        const normalize=text=>text.normalize('NFD').replace(/[\\u0300-\\u036f]/g,'').toLocaleLowerCase('pt-BR');
        field.addEventListener('input',()=>{
          const term=normalize(field.value.trim());
          let count=0;
          for(const folder of folders){
            const match=normalize(folder.dataset.vendorName||'').includes(term);
            folder.hidden=!match;
            if(match)count++;
          }
          counter.textContent=count+' pasta'+(count===1?'':'s')+' encontrada'+(count===1?'':'s');
          if(empty)empty.hidden=count!==0;
        });
      })();
    </script>
  <?php else:?>
    <section class="surface">
      <div class="surface-head">
        <div><h2>Planilha de seguros</h2><p class="surface-sub">Competência ativa · <?=h($resultado['total']??0)?> registro(s).</p></div>
        <a class="mini-link" href="/seguros.php?<?=h(http_build_query(['vendedor'=>$vendedorAtual]))?>">Consulta detalhada ↗</a>
      </div>
      <form method="get" class="vendor-search">
        <input type="hidden" name="vendedor" value="<?=h($vendedorAtual)?>">
        <div><label for="busca">Pesquisar segurado</label><input id="busca" name="busca" value="<?=h($busca)?>" maxlength="100" placeholder="Digite o nome do cliente"></div>
        <button type="submit">Pesquisar</button>
        <a href="/vendedores.php?<?=h(http_build_query(['vendedor'=>$vendedorAtual]))?>">Limpar</a>
      </form>
      <p class="hint" style="margin:8px 0 14px">Escolha uma situação por seguro e clique em <strong>Salvar alteração</strong> para registrar no MySQL.</p>
      <div class="table-scroll sheet-scroll" tabindex="0" aria-label="Tabela de seguros; role para os lados para visualizar as colunas">
        <table class="vendor-table sheet-table">
          <thead><tr><th scope="col">Segurado</th><th scope="col">Vendedor</th><th scope="col">Vencimento</th><th scope="col">Produto</th><th scope="col">Situação do seguro</th></tr></thead>
          <tbody>
          <?php foreach($rows as $r):?>
          <?php [$situacao,$classeSituacao]=situacaoPlanilha($r['status_atual']??null); ?>
          <tr>
            <td class="sheet-name"><strong><?=h($r['segurado'])?></strong></td>
            <td><?=h($r['vendedor'])?></td>
            <td class="sheet-date"><?=h((new DateTimeImmutable((string)$r['vencimento']))->format('d/m/Y'))?></td>
            <td><?=h($r['produto']?:'Não informado')?></td>
            <td>
              <?php $situacaoEscolhida=((int)$r['renovado']===1||$r['status_atual']==='RENOVADO')?'renovado':(((int)$r['nao_vai_renovar']===1||$r['status_atual']==='NAO_RENOVA')?'nao_renovar':'pendente'); ?>
              <form method="post" class="sheet-status-form" aria-label="Alterar situação de <?=h($r['segurado'])?>">
                <input type="hidden" name="csrf" value="<?=h(Auth::csrf())?>">
                <input type="hidden" name="linha" value="<?=h($r['linha_origem'])?>">
                <input type="hidden" name="vendedor" value="<?=h($vendedorAtual)?>">
                <input type="hidden" name="versao" value="<?=h($r['versao_manual']??0)?>">
                <select name="situacao" aria-label="Situação de <?=h($r['segurado'])?>">
                  <option value="pendente" <?=$situacaoEscolhida==='pendente'?'selected':''?>>Pendente</option>
                  <option value="renovado" <?=$situacaoEscolhida==='renovado'?'selected':''?>>Renovado</option>
                  <option value="nao_renovar" <?=$situacaoEscolhida==='nao_renovar'?'selected':''?>>Não vai renovar</option>
                </select>
                <button type="submit" aria-label="Salvar situação de <?=h($r['segurado'])?>">Salvar alteração</button>
              </form>
            </td>
          </tr>
          <?php endforeach;?>
          <?php if(!$rows):?><tr><td colspan="5">Nenhum seguro encontrado nessa pasta com os filtros informados.</td></tr><?php endif;?>
          </tbody>
        </table>
      </div>
      <div class="vendor-pages">
        <span>Página <?=h($pagina)?> de <?=h($totalPaginas)?> · 40 seguros por página</span>
        <span>
          <?php if($pagina>1):?><a href="?<?=h(http_build_query($params+['pagina'=>$pagina-1]))?>">← Anterior</a><?php endif;?>
          <?php if($pagina<$totalPaginas):?><a href="?<?=h(http_build_query($params+['pagina'=>$pagina+1]))?>">Próxima →</a><?php endif;?>
        </span>
      </div>
    </section>
  <?php endif;?>
  <p class="hint">Agora você pode alterar a situação e salvar no MySQL. A situação é salva no MySQL com histórico, mas a planilha externa não é atualizada automaticamente.</p>
</main></div></body></html>
