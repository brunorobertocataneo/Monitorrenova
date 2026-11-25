<?php
/**
 * Navegação simples: início, vendedores (subpastas) e consultas.
 * Recursos acadêmicos continuam no código, mas não aparecem no menu.
 */
require_once __DIR__.'/../../Auth.php';
$activePage=isset($activePage)&&is_string($activePage)?$activePage:'dashboard';
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
      <a class="nav-item <?=$activePage==='vendedores'?'active':''?>" href="/vendedores.php" <?=$activePage==='vendedores'?'aria-current="page"':''?>>
        <svg class="symbol" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="4" y="3" width="16" height="18" rx="2"/><path d="M4 9h16M4 15h16M10 6h4M10 12h4M10 18h4"/></svg><span>Vendedores</span>
      </a>
      <a class="nav-item <?=$activePage==='seguros'?'active':''?>" href="/seguros.php" <?=$activePage==='seguros'?'aria-current="page"':''?>>
        <span class="symbol" aria-hidden="true">⌕</span><span>Consultar seguros</span>
      </a>
    </div>

    <div class="nav-category">Projeto acadêmico</div>
    <div class="nav-list">
      <a class="nav-item" href="/tarefas.php"><span class="symbol" aria-hidden="true">✓</span><span>Tarefas (CRUD)</span></a>
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
