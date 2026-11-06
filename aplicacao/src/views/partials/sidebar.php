<?php
/**
 * Navegação simples: início, vendedores (subpastas) e consultas.
 * Recursos acadêmicos continuam no código, mas não aparecem no menu.
 */
require_once __DIR__.'/../../Auth.php';
$activePage=isset($activePage)&&is_string($activePage)?$activePage:'dashboard';
$vendedoresMenu=[];
try {
    // Utiliza a mesma fonte da competência ativa, sem nomes fixos.
    $vendedoresMenu=Database::connection()->query(
        "SELECT DISTINCT vendedor FROM seguro_dashboard WHERE vendedor IS NOT NULL AND TRIM(vendedor)<>'' ORDER BY vendedor"
    )->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $e) {
    error_log('Menu de vendedores: '.$e->getMessage());
}
$vendedorAtual=isset($vendedorAtual)&&is_string($vendedorAtual)?$vendedorAtual:'';
$menuVendedoresAberto=($activePage==='vendedores');
?>
<aside class="app-sidebar" aria-label="Menu principal">
  <a class="brand" href="/" aria-label="MonitorRenova, voltar para início">
    <span class="brand-icon" aria-hidden="true">MR</span>
    <span><strong>MonitorRenova</strong><small>GESTÃO DE SEGUROS</small></span>
  </a>
  <nav aria-label="Navegação principal">
    <div class="nav-category">Dia a dia</div>
    <div class="nav-list">
      <a class="nav-item <?=$activePage==='dashboard'?'active':''?>" href="/" <?=$activePage==='dashboard'?'aria-current="page"':''?>>
        <span class="symbol" aria-hidden="true">⌂</span><span>Início · Vencimentos de hoje</span>
      </a>
      <details class="seller-folder" <?=$menuVendedoresAberto?'open':''?>>
        <summary class="nav-item <?=$menuVendedoresAberto?'active':''?>">
          <span class="symbol" aria-hidden="true">▣</span><span>Vendedores</span>
          <span class="folder-chevron" aria-hidden="true">⌄</span>
        </summary>
        <div class="seller-subfolders">
          <a href="/vendedores.php" class="seller-subfolder <?=$menuVendedoresAberto&&$vendedorAtual===''?'selected':''?>">▤ &nbsp; Todos os vendedores</a>
          <?php foreach($vendedoresMenu as $nome):?>
            <a href="/vendedores.php?<?=h(http_build_query(['vendedor'=>$nome]))?>"
               class="seller-subfolder <?=$menuVendedoresAberto&&$vendedorAtual===$nome?'selected':''?>">
              <span aria-hidden="true">▸</span> <?=h($nome)?>
            </a>
          <?php endforeach;?>
          <?php if(!$vendedoresMenu):?><span class="seller-subfolder">Nenhum vendedor cadastrado</span><?php endif;?>
        </div>
      </details>
      <a class="nav-item <?=$activePage==='seguros'?'active':''?>" href="/seguros.php" <?=$activePage==='seguros'?'aria-current="page"':''?>>
        <span class="symbol" aria-hidden="true">⌕</span><span>Consultar seguros</span>
      </a>
    </div>

  </nav>
  <div class="nav-spacer"></div>
  <div class="nav-footer">
    <div>Dados demonstrativos · sem disparos automáticos</div>
    <form method="post" action="/sair.php">
      <input type="hidden" name="csrf" value="<?=h(Auth::csrf())?>">
      <button type="submit">Sair com segurança</button>
    </form>
  </div>
</aside>
