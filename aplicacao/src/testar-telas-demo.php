<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli') exit(1);
require_once __DIR__.'/Database.php';
require_once __DIR__.'/DashboardController.php';
require_once __DIR__.'/VencimentoController.php';
require_once __DIR__.'/VencimentoRepository.php';
require_once __DIR__.'/HistoricoMensalRepository.php';
require_once __DIR__.'/HistoricoMensalController.php';
$db=Database::connection();
$tests=[];
try{
 $ctrl=new DashboardController(new MonitorRepository($db),new SeguroRepository($db),new N8nHistoricoRepository($db));
 $data=$ctrl->index([]);
 $tests['painel']='OK';
 $tests['automacoes']=(int)$data['kpi']['automacoes'];
}catch(Throwable $e){$tests['painel']='ERRO: '.$e->getMessage();}
try{
 $ctrl=new VencimentoController(new VencimentoRepository($db));
 $data=$ctrl->index(['de'=>'2026-11-01','ate'=>'2026-11-30']);
 $tests['vencimentos_total']=(int)$data['total'];
}catch(Throwable $e){$tests['vencimentos']='ERRO: '.$e->getMessage();}
try{
 $ctrl=new HistoricoMensalController(new HistoricoMensalRepository($db));
 $data=$ctrl->index(['mes'=>'2026-11','fonte'=>'planilha']);
 $tests['historico_total']=(int)$data['stats']['total'];
}catch(Throwable $e){$tests['historico']='ERRO: '.$e->getMessage();}
echo json_encode($tests,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT).PHP_EOL;
