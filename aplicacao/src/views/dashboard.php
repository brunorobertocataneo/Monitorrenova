<?php
/**
 * View do dashboard inicial (MVC).
 *
 * Todos os dados vêm do DashboardController; nenhuma consulta SQL ou
 * chamada ao n8n é executada aqui. Somente links, filtros e apresentação.
 * O conteúdo exibido é escapado contra inserção de HTML via planilha.
 */
function h(mixed $valor):string
{
    return htmlspecialchars((string)$valor,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
}
$activePage='dashboard';
$viewDate=(new DateTimeImmutable($dataHoje))->format('d/m/Y');
?>
<!doctype html>
<html lang="pt-BR"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#102238">
<title>Visão geral — MonitorRenova</title>
<link rel="stylesheet" href="/assets/app.css?v=1">
</head>
<body class="app-shell">
<?php require __DIR__.'/partials/sidebar.php';?>
<div class="app-content">
  <div class="page-topbar">
    <span class="topbar-kicker">Painel operacional / Visão geral</span>
    <span class="topbar-time"><span class="status-dot <?=$estadoSheet&&!$estadoSheet['recente']?'is-warn':''?>" aria-hidden="true"></span>Ambiente local e protegido</span>
  </div>
  <main class="page-content" id="conteudo">
    <div class="page-titlebar">
      <div>
        <div class="eyebrow">Controle diário de seguros</div>
        <h1>Visão geral</h1>
        <p>Acompanhe vencimentos, vendedores e a atualização dos dados em um só lugar.</p>
      </div>
      <a class="btn-link primary-action" href="/vencimentos.php">Ver dashboard de vencimentos <span aria-hidden="true">↗</span></a>
    </div>

    <?php if(!$dbOk): ?>
      <div class="syncline stale" role="alert">
        <strong>Banco indisponível.</strong> Confira se os contêineres PHP e MySQL estão funcionando.
      </div>
    <?php else: ?>
      <div class="syncline <?=$estadoSheet&&!$estadoSheet['recente']?'stale':''?>" role="status">
        <span class="tag"><?= $estadoSheet&&$estadoSheet['recente']?'Dados sincronizados':'Sincronização a conferir'?></span>
        <?php if($estadoSheet):?>
          <span><strong>Competência <?=h($estadoSheet['competencia'])?></strong></span>
          <span>Última leitura: <?=h($estadoSheet['horario'])?> · horário de Brasília</span>
          <span><?=h($estadoSheet['total'])?> seguros no MySQL</span>
        <?php else:?>
          <span>Sem leitura recente da planilha. Exibindo cópia local quando disponível.</span>
        <?php endif;?>
        <a href="/competencias.php" style="font-weight:750">Gerenciar competências →</a>
      </div>

      <div class="section-head">
        <div><h2>Panorama de hoje</h2><p>Seguros com vencimento em <?=h($viewDate)?></p></div>
        <a class="link" href="/seguros.php?<?=h(http_build_query(['de'=>$dataHoje,'ate'=>$dataHoje]))?>">Consultar seguros de hoje →</a>
      </div>
      <div class="metrics-row">
        <div class="stat-card">
          <span class="stat-icon" aria-hidden="true">▣</span>
          <span class="stat-label">Vencimentos de hoje</span>
          <strong class="stat-value"><?=h($resumoDia['total'])?></strong>
          <span class="stat-note">Registros com data de hoje</span>
        </div>
        <div class="stat-card important">
          <span class="stat-icon" aria-hidden="true">◷</span>
          <span class="stat-label">Sem marcações de conclusão</span>
          <strong class="stat-value"><?=h($resumoDia['paraConferencia'])?></strong>
          <span class="stat-note">Necessitam de conferência humana</span>
        </div>
        <div class="stat-card">
          <span class="stat-icon" aria-hidden="true">♧</span>
          <span class="stat-label">Vendedores envolvidos</span>
          <strong class="stat-value"><?=h($resumoDia['vendedores'])?></strong>
          <span class="stat-note">Com vencimentos hoje</span>
        </div>
        <div class="stat-card">
          <span class="stat-icon" aria-hidden="true">▤</span>
          <span class="stat-label">Base aprovada no MySQL</span>
          <strong class="stat-value"><?=h($estadoSheet['total']??0)?></strong>
          <span class="stat-note">Competência <?=h($estadoSheet['competencia']??'não informada')?></span>
        </div>
      </div>

      <div class="section-head">
        <div><h2>Acompanhamento operacional</h2><p>Consulta de seguros e principais ações</p></div>
      </div>
      <div class="content-grid">
        <section class="surface" aria-labelledby="titulo-vencimentos">
          <div class="surface-head">
            <div>
              <h2 id="titulo-vencimentos">Segurados com vencimento em <?=h($viewDate)?></h2>
              <p class="surface-sub">Agrupados por vendedor para facilitar a conferência.</p>
            </div>
            <a class="mini-link" href="/seguros.php?<?=h(http_build_query(['de'=>$dataHoje,'ate'=>$dataHoje]))?>">Lista completa ↗</a>
          </div>
          <div class="info-line">Esta é uma cópia sincronizada no MySQL e pode haver atraso em relação ao Google Sheets.</div>
          <?php if((int)$resumoDia['paraConferencia']===0):?>
            <p class="hint">Nenhum registro sem marcações de conclusão nesta data, conforme a última sincronização.</p>
          <?php else:?>
            <div class="seller-list">
            <?php foreach($resumoDia['grupos'] as $vendedor=>$nomes):?>
              <article class="seller-entry">
                <div class="seller-entry-head">
                  <a href="/seguros.php?<?=h(http_build_query(['vendedor'=>$vendedor,'de'=>$dataHoje,'ate'=>$dataHoje]))?>"><?=h($vendedor)?></a>
                  <span class="count-bubble" aria-label="<?=h(count($nomes))?> segurados mostrados"><?=h(count($nomes))?></span>
                </div>
                <ul>
                  <?php foreach(array_slice($nomes,0,3) as $segurado):?>
                    <li><?=h($segurado)?></li>
                  <?php endforeach;?>
                </ul>
                <?php if(count($nomes)>3):?>
                  <div class="more">Outros <?=h(count($nomes)-3)?> registros deste vendedor: veja a lista completa.</div>
                <?php endif;?>
              </article>
            <?php endforeach;?>
            </div>
          <?php endif;?>
          <?php if($resumoDia['omitidos']>0):?>
            <div class="note-banner">Existem <?=h($resumoDia['omitidos'])?> seguros não exibidos nesta prévia limitada a 100 nomes. Consulte a lista completa.</div>
          <?php endif;?>
          <div class="note-banner"><strong>Importante:</strong> “sem marcações” não significa situação pendente confirmada e não autoriza cobrança automática.</div>
        </section>

        <div style="display:grid;gap:18px;min-width:0">
          <section class="surface" aria-labelledby="titulo-acessos">
            <div class="surface-head">
              <div><h2 id="titulo-acessos">Acesso rápido</h2><p class="surface-sub">Módulos essenciais do sistema</p></div>
            </div>
            <div class="system-link-list">
              <?php foreach([
                ['/seguros.php','▤','Consultar seguros'],
                ['/vencimentos.php','◷','Vencimentos'],
                ['/competencias.php','▦','Competências'],
                ['/meses.php','▧','Histórico mensal'],
                ['/integracoes.php','⌁','Integrações'],
                ['/conferencia.php','✓','Conferência']
              ] as $atalho):?>
                <a class="system-link" href="<?=h($atalho[0])?>"><span class="box-icon" aria-hidden="true"><?=h($atalho[1])?></span><?=h($atalho[2])?></a>
              <?php endforeach;?>
            </div>
          </section>
          <section class="surface" aria-labelledby="titulo-monitoramento">
            <div class="surface-head">
              <div><h2 id="titulo-monitoramento">Monitoramento n8n</h2><p class="surface-sub">Últimas execuções registradas</p></div>
              <a class="mini-link" href="/historico-n8n.php">Histórico ↗</a>
            </div>
            <?php if($n8nResumo):?>
              <div class="info-line">
                <?=h($n8nResumo['total'])?> execuções monitoradas
                · <?=h($n8nResumo['mysqlConfirmadas'])?> commits confirmados
                · <?=h($n8nResumo['falhas'])?> falhas no histórico.
                <div style="margin-top:5px">Última coleta: <?=h($n8nResumo['coletadoEm']??'não registrada')?>.
                <?= $n8nResumo['coletaRecente']?'Dados coletados recentemente.':'Coleta possivelmente desatualizada.'?></div>
              </div>
              <div class="status-log">
                <?php foreach($n8nUltimas as $n):?>
                  <div class="status-log-item">
                    <span class="log-id">#<?=h($n['execution_id'])?></span>
                    <div>
                      <div class="log-title"><?=h(N8nHistoricoRepository::nomeWorkflow($n['workflow_id']))?></div>
                      <div class="log-desc"><?=h(N8nHistoricoRepository::resultado($n['resultado']))?> · <?=h($n['iniciado_em'])?></div>
                    </div>
                  </div>
                <?php endforeach;?>
                <?php if(!$n8nUltimas):?><p class="hint">Ainda não há registros de execuções.</p><?php endif;?>
              </div>
            <?php else:?>
              <p class="hint">Histórico n8n temporariamente indisponível.</p>
            <?php endif;?>
            <p class="hint">Aceite da API não comprova entrega no WhatsApp. “Em andamento” no n8n não significa falha do MySQL.</p>
          </section>
        </div>
      </div>

      <details class="legacy-details">
        <summary>Auditorias e relatórios anteriores (dados históricos) <span aria-hidden="true">⌄</span></summary>
        <div class="legacy-body">
          <p class="hint">Estes indicadores pertencem às auditorias legadas e não representam os disparos atuais do n8n.</p>
          <div class="legacy-grid">
            <?php foreach([
              'Automações de auditoria'=>'automacoes',
              'Auditorias registradas'=>'execucoes',
              'Auditorias OK'=>'sucessos',
              'Falhas de auditoria'=>'falhas'
            ] as $titulo=>$chave):?>
              <div class="legacy-metric"><span><?=h($titulo)?></span><strong><?=h($kpi[$chave])?></strong></div>
            <?php endforeach;?>
          </div>
          <div class="section-head"><h2>Automações cadastradas</h2></div>
          <div class="table-scroll"><table>
            <thead><tr><th>Automação</th><th>Origem</th><th>Identificador</th><th>Estado</th></tr></thead>
            <tbody>
              <?php foreach($automacoes as $a):?>
                <tr><td><?=h($a['nome'])?></td><td><?=h($a['sistema_origem'])?></td><td><?=h($a['referencia_externa'])?></td><td><?=((int)$a['ativo']===1)?'Ativa':'Inativa'?></td></tr>
              <?php endforeach;?>
              <?php if(!$automacoes):?><tr><td colspan="4">Nenhuma automação cadastrada.</td></tr><?php endif;?>
            </tbody>
          </table></div>
          <div class="section-head">
            <h2>Execuções de auditoria</h2>
            <a class="link" href="/exportar.php">Exportar CSV →</a>
          </div>
          <form method="get" class="form-row">
            <div><label for="automacao">Automação</label><select id="automacao" name="automacao"><option value="">Todas</option>
              <?php foreach($automacoes as $a):?><option value="<?=h($a['id'])?>" <?=$automacaoId===(int)$a['id']?'selected':''?>><?=h($a['nome'])?></option><?php endforeach;?>
            </select></div>
            <div><label for="status">Status</label><select id="status" name="status"><option value="">Todos</option>
              <?php foreach(['iniciada','sucesso','falha','cancelada'] as $v):?><option value="<?=h($v)?>" <?=$status===$v?'selected':''?>><?=h(ucfirst($v))?></option><?php endforeach;?>
            </select></div>
            <button type="submit">Filtrar</button>
            <a href="/">Limpar</a>
          </form>
          <div class="table-scroll"><table>
            <thead><tr><th>ID</th><th>Automação</th><th>Status</th><th>Início</th><th>Processados</th><th>Elegíveis</th><th>Pendentes</th><th>Enviados</th></tr></thead><tbody>
            <?php foreach($execucoes as $e):?>
              <tr><td><a href="/execucao.php?id=<?=urlencode((string)$e['id'])?>">#<?=h($e['id'])?></a></td><td><?=h($e['automacao'])?></td><td><?=h($e['status'])?></td><td><?=h($e['iniciada_em'])?></td><td><?=h($e['total_processados'])?></td><td><?=h($e['total_elegiveis'])?></td><td><?=h($e['total_pendentes'])?></td><td><?=h($e['total_enviados'])?></td></tr>
            <?php endforeach;?>
            <?php if(!$execucoes):?><tr><td colspan="8">Nenhuma auditoria antiga encontrada. Consulte o histórico real do n8n.</td></tr><?php endif;?>
            </tbody>
          </table></div>
          <div class="section-head"><h2>Eventos registrados nas auditorias</h2></div>
          <div class="table-scroll"><table><thead><tr><th>Data</th><th>Automação</th><th>Nível</th><th>Tipo</th><th>Descrição</th></tr></thead><tbody>
            <?php foreach($eventos as $e):?>
              <tr><td><?=h($e['criado_em'])?></td><td><?=h($e['automacao'])?></td><td><?=h($e['nivel'])?></td><td><?=h($e['tipo'])?></td><td><?=h($e['descricao'])?></td></tr>
            <?php endforeach;?>
            <?php if(!$eventos):?><tr><td colspan="5">Nenhum evento antigo registrado.</td></tr><?php endif;?>
          </tbody></table></div>
        </div>
      </details>
    <?php endif;?>
    <footer class="app-footer">MonitorRenova · Ambiente local · PHP e MySQL · Dados operacionais para conferência, sem cobrança automática.</footer>
  </main>
</div>
</body></html>
