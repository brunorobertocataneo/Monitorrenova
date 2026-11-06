<?php
declare(strict_types=1);
require_once __DIR__.'/Database.php';
require_once __DIR__.'/Auth.php';
require_once __DIR__.'/DashboardController.php';
require_once __DIR__.'/VencimentoRepository.php';
require_once __DIR__.'/SeguroRepository.php';
$modo=$argv[1]??'dashboard';
$db=Database::connection();
Auth::boot();
$_SERVER['SCRIPT_NAME']=$modo==='dashboard'?'/index.php':'/vendedores.php';
if($modo==='dashboard'){
    $dashboard=new DashboardController(new MonitorRepository($db),new SeguroRepository($db),new N8nHistoricoRepository($db));
    $dados=$dashboard->index([]);
    $dataHoje=$dados['dataHoje'];
    $estadoSheet=$dados['estadoSheet'];
    $resumoDia=$dados['resumoDia'];
    $vencimentosHoje=(new VencimentoRepository($db))->vencimentosDeHoje($dataHoje);
    $dbOk=true;
    ob_start();require __DIR__.'/views/dashboard.php';$html=ob_get_clean();
    foreach(['Vencimentos de hoje','Seguros de hoje para acompanhar por vendedor','Vendedores','Consultar seguros'] as $termo){
        if(!str_contains($html,$termo))throw new RuntimeException('HTML inicio sem: '.$termo);
    }
    if(str_contains($html,'Outras ferramentas'))throw new RuntimeException('Menu avancado ainda visivel');
    echo "PASS inicio: grupos=".count($vencimentosHoje).", tamanho_html=".strlen($html).PHP_EOL;
}elseif($modo==='vendedores'){
    $r=new SeguroRepository($db);
    $resultado=$r->consultar(['vendedor'=>'','produto'=>'','de'=>null,'ate'=>null,'busca'=>''],1,40);
    $vendedores=array_values(array_filter($resultado['vendedores'],static fn($n)=>trim((string)$n)!==''));
    if(!$vendedores)throw new RuntimeException('Sem vendedores');
    $vendedorAtual='';
    $busca='';$pagina=1;$erro=null;
    ob_start();require __DIR__.'/views/vendedores.php';$html=ob_get_clean();
    foreach($vendedores as $nome){
        if(!str_contains($html,htmlspecialchars($nome,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8')))throw new RuntimeException('Pasta ausente');
    }
    echo "PASS pastas: ".count($vendedores)." vendedores".PHP_EOL;
}elseif($modo==='planilha'){
    $r=new SeguroRepository($db);
    $inicial=$r->consultar(['vendedor'=>'','produto'=>'','de'=>null,'ate'=>null,'busca'=>''],1,40);
    $vendedores=array_values(array_filter($inicial['vendedores'],static fn($n)=>trim((string)$n)!==''));
    $vendedorAtual=(string)$vendedores[0];
    $resultado=$r->consultar(['vendedor'=>$vendedorAtual,'produto'=>'','de'=>null,'ate'=>null,'busca'=>''],1,40);
    $busca='';$pagina=1;$erro=null;
    foreach($resultado['registros'] as $linha){
        if($linha['vendedor']!==$vendedorAtual)throw new RuntimeException('Seguro de vendedor errado');
    }
    ob_start();require __DIR__.'/views/vendedores.php';$html=ob_get_clean();
    if(!str_contains($html,'Planilha de seguros')||!str_contains($html,$vendedorAtual))throw new RuntimeException('Planilha nao renderizada');
    echo "PASS planilha: ".$vendedorAtual." com ".$resultado['total']." registros".PHP_EOL;
}
