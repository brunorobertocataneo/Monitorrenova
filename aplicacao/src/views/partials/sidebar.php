<?php
require_once __DIR__.'/../../Auth.php';
/**
 * Navegação reaproveitável do MonitorRenova.
 *
 * Só é incluída por telas que já exigiram autenticação.
 * Todos os destinos são rotas locais fixas; nenhum link vem do banco.
 * A classe "active" informa a posição atual aos leitores de tela.
 *
 * @var string|null $activePage
 */
$activePage = isset($activePage) && is_string($activePage)
    ? $activePage : 'dashboard';
$navGroups = [
    'Operação' => [
        ['id'=>'dashboard','path'=>'/','icon'=>'◈','label'=>'Visão geral'],
        ['id'=>'seguros','path'=>'/seguros.php','icon'=>'▤','label'=>'Seguros'],
        ['id'=>'vencimentos','path'=>'/vencimentos.php','icon'=>'◷','label'=>'Vencimentos'],
        ['id'=>'competencias','path'=>'/competencias.php','icon'=>'▦','label'=>'Competências'],
        ['id'=>'meses','path'=>'/meses.php','icon'=>'▧','label'=>'Histórico mensal']
    ],
    'Monitoramento' => [
        ['id'=>'n8n','path'=>'/historico-n8n.php','icon'=>'◉','label'=>'Execuções do n8n'],
        ['id'=>'integracoes','path'=>'/integracoes.php','icon'=>'⌁','label'=>'Integrações'],
        ['id'=>'indicadores','path'=>'/indicadores.php','icon'=>'▥','label'=>'Indicadores']
    ],
    'Ferramentas' => [
        ['id'=>'conferencia','path'=>'/conferencia.php','icon'=>'✓','label'=>'Conferência'],
        ['id'=>'prontidao','path'=>'/prontidao.php','icon'=>'◈','label'=>'Prontidão'],
        ['id'=>'acompanhamento','path'=>'/acompanhamento.php','icon'=>'▣','label'=>'Acompanhamento'],
        ['id'=>'validacoes','path'=>'/validacoes.php','icon'=>'◇','label'=>'Validações'],
        ['id'=>'simulador','path'=>'/simulador.php','icon'=>'⊞','label'=>'Simulador'],
        ['id'=>'importar','path'=>'/importar.php','icon'=>'⇪','label'=>'Importações']
    ]
];
?>
<aside class="app-sidebar" aria-label="Menu lateral do MonitorRenova">
  <a class="brand" href="/" aria-label="MonitorRenova, ir à visão geral">
    <span class="brand-icon" aria-hidden="true">MR</span>
    <span><strong>MonitorRenova</strong><small>DEMONSTRAÇÃO • dados fictícios</small></span>
  </a>
  <nav aria-label="Navegação principal">
    <?php foreach($navGroups as $grupo=>$links): ?>
      <div class="nav-category"><?=h($grupo)?></div>
      <div class="nav-list">
        <?php foreach($links as $link):?>
          <a class="nav-item <?=$activePage===$link['id']?'active':''?>"
             href="<?=h($link['path'])?>"
             <?=$activePage===$link['id']?'aria-current="page"':''?>>
             <span class="symbol" aria-hidden="true"><?=h($link['icon'])?></span>
             <span><?=h($link['label'])?></span>
          </a>
        <?php endforeach;?>
      </div>
    <?php endforeach;?>
  </nav>
  <div class="nav-spacer"></div>
  <div class="nav-footer">
    <div>Ambiente demonstrativo • sem envios reais</div>
    <div style="margin-top:5px;opacity:.72">Painel PHP + MySQL • n8n</div>
    <div style="margin-top:8px"><a href="http://127.0.0.1:5678/" target="_blank" rel="noopener noreferrer" style="color:inherit;text-decoration:underline">Abrir editor n8n local ↗</a></div>
    <form method="post" action="/sair.php">
      <input type="hidden" name="csrf" value="<?=h(Auth::csrf())?>">
      <button type="submit">Sair com segurança</button>
    </form>
  </div>
</aside>
