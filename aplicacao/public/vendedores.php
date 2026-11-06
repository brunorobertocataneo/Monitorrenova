<?php
declare(strict_types=1);
/**
 * Pasta de vendedores — GET consulta; POST altera somente status via serviço auditado.
 */
require_once '/var/www/src/Auth.php';
Auth::requireLogin();
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store');
require_once '/var/www/src/Database.php';
require_once '/var/www/src/SeguroRepository.php';
require_once '/var/www/src/SeguroStatusService.php';

$vendedorAtual='';
$busca='';
$pagina=1;
$erro=null;
$sucesso=isset($_GET['ok'])&&$_GET['ok']==='salvo'?'Situação salva no MySQL com registro no histórico.':null;
$resultado=['registros'=>[],'total'=>0,'totalPaginas'=>1,'pagina'=>1,'vendedores'=>[]];
$method=$_SERVER['REQUEST_METHOD']??'GET';
if(!in_array($method,['GET','POST'],true)){
    header('Allow: GET, POST');
    http_response_code(405);
    exit('Método não permitido.');
}
try {
    $db=Database::connection();
    if($method==='POST'){
        if(!Auth::verifyCsrf($_POST['csrf']??null)){
            http_response_code(403);
            throw new InvalidArgumentException('Formulário expirado. Atualize a página e tente novamente.');
        }
        $service=new SeguroStatusService($db);
        $service->atualizar(
            $_POST['linha']??null,
            $_POST['versao']??null,
            $_POST['situacao']??null,
            $_POST['vendedor']??null,
            (int)($_SESSION['usuario_id']??0)
        );
        $retornoVendedor=is_string($_POST['vendedor']??null)?$_POST['vendedor']:'';
        header('Location: /vendedores.php?'.http_build_query(['vendedor'=>$retornoVendedor,'ok'=>'salvo']),true,303);
        exit;
    }
} catch(InvalidArgumentException $e){
    $erro=$e->getMessage();
    http_response_code(http_response_code()===403?403:422);
} catch(RuntimeException $e){
    $erro=$e->getMessage();
    http_response_code(409);
} catch(Throwable $e){
    error_log('Falha ao gravar status do seguro: '.$e->getMessage());
    $erro='Não foi possível gravar a situação. Nenhuma alteração foi confirmada.';
    http_response_code(503);
}
try {
    $repo=new SeguroRepository(Database::connection());
    $bruto=$method==='POST'?($_POST['vendedor']??''):($_GET['vendedor']??'');
    $vendedorAtual=is_string($bruto)?mb_substr(trim($bruto),0,180):'';
    $brutoBusca=$_GET['busca']??'';
    $busca=is_string($brutoBusca)?mb_substr(trim($brutoBusca),0,100):'';
    $p=$_GET['pagina']??'1';
    if((is_string($p)||is_int($p))&&preg_match('/^[1-9][0-9]{0,4}$/D',(string)$p))$pagina=min(10000,(int)$p);
    $filtros=['vendedor'=>$vendedorAtual,'produto'=>'','de'=>null,'ate'=>null,'busca'=>$busca];
    $resultado=$repo->consultar($filtros,$pagina,40);
    $vendedores=array_values(array_filter($resultado['vendedores'],static fn($v)=>trim((string)$v)!==''));
    if($vendedorAtual!==''&&!in_array($vendedorAtual,$vendedores,true)){
        http_response_code(404);
        $erro='Vendedor não encontrado na competência ativa.';
        $resultado['registros']=[];
        $resultado['total']=0;
    }
} catch(Throwable $e) {
    error_log('Pastas de vendedores: '.$e->getMessage());
    http_response_code(503);
    $erro='Não foi possível consultar os vendedores neste momento.';
    $vendedores=[];
}
require '/var/www/src/views/vendedores.php';
