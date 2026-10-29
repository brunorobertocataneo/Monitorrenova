<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(403);exit(1);}
require_once __DIR__.'/Database.php';
require_once __DIR__.'/SeguroController.php';
require_once __DIR__.'/VencimentoController.php';
require_once __DIR__.'/VencimentoRepository.php';
require_once __DIR__.'/HistoricoMensalRepository.php';
require_once __DIR__.'/HistoricoMensalController.php';
$db=Database::connection();
$tests=[];
function check(string $name,bool $pass,mixed $observed):void{global $tests;$tests[$name]=['ok'=>$pass,'observado'=>$observed];}
try{
 $c=new SeguroController(new SeguroRepository($db));
 $all=$c->index([]);
 check('seguros_todos', $all['total']===50, $all['total']);
 $search=$c->index(['busca'=>'Itachi Uchiha']);
 check('busca_por_segurado',$search['total']===1,$search['total']);
 $vendor=$c->index(['vendedor'=>'Ada Lovelace']);
 check('filtro_vendedor',$vendor['total']>0&&$vendor['total']<50,$vendor['total']);
 $product=$c->index(['produto'=>'Seguro Auto']);
 check('filtro_produto',$product['total']>0&&$product['total']<50,$product['total']);
 $badDate=$c->index(['de'=>'2026-02-30','pagina'=>['2'],'busca'=>['arr']]);
 check('entrada_invalida_controlada',$badDate['de']===null&&$badDate['pagina']===1,$badDate['pagina']);
 $page2=$c->index(['pagina'=>'2']);
 check('paginacao',$page2['pagina']===2&&count($page2['rows'])===20,count($page2['rows']));
 $zero=$c->index(['busca'=>'CLIENTE-IMPOSSIVEL-DEMO']);
 check('busca_sem_resultado',$zero['total']===0,$zero['total']);
}catch(Throwable $e){check('seguro_controller',false,$e->getMessage());}
try{
 $v=new VencimentoController(new VencimentoRepository($db));
 $data=$v->index(['de'=>'2026-11-01','ate'=>'2026-11-30']);
 $sum=array_sum(array_column($data['bySeller'],'total'));
 check('grafico_vendedores_soma',$sum===(int)$data['total'],$sum);
 $sum=array_sum(array_column($data['byProduct'],'total'));
 check('grafico_produtos_soma',$sum===(int)$data['total'],$sum);
 $sum=array_sum(array_column($data['byDate'],'total'));
 check('grafico_datas_soma',$sum===(int)$data['total'],$sum);
 $no=$v->index(['de'=>'2021-01-01','ate'=>'2021-01-31']);
 check('periodo_sem_dados',(int)$no['total']===0,$no['total']);
}catch(Throwable $e){check('vencimento_controller',false,$e->getMessage());}
try{
 $h=new HistoricoMensalController(new HistoricoMensalRepository($db));
 $oct=$h->index(['mes'=>'2026-10']);
 $nov=$h->index(['mes'=>'2026-11']);
 check('historico_outubro',(int)$oct['stats']['total']===15,$oct['stats']['total']);
 check('historico_novembro',(int)$nov['stats']['total']===50,$nov['stats']['total']);
}catch(Throwable $e){check('historico_controller',false,$e->getMessage());}
$pass=count(array_filter($tests,static fn($t)=>$t['ok']));
echo json_encode(['aprovados'=>$pass,'total'=>count($tests),'detalhes'=>$tests],JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT).PHP_EOL;
exit($pass===count($tests)?0:1);
