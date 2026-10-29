<?php
declare(strict_types=1);
require_once '/var/www/src/Auth.php';
Auth::requireLogin();
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store');
require_once '/var/www/src/RevisaoSeguroService.php';

function h(mixed $s):string{
 return htmlspecialchars((string)$s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
}
$id=filter_var($_GET['id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
if(!$id){http_response_code(400);exit('Identificador inválido.');}
$method=$_SERVER['REQUEST_METHOD']??'GET';
if($method!=='GET'&&$method!=='POST'){
 http_response_code(405);exit('Método não permitido.');
}
$error=null;
try{
 $service=new RevisaoSeguroService(Database::connection());
 $allowed=RevisaoSeguroService::SITUACOES;
 if($method==='POST'){
  if(!Auth::verifyCsrf($_POST['csrf']??null))throw new RuntimeException('Sessão inválida.');
  $service->registrar((int)$id,(int)$_SESSION['usuario_id'],
     $_POST['situacao']??null,$_POST['observacao']??null,$_POST['versao']??null);
  header('Location: /revisar.php?id='.$id.'&salvo=1',true,303);
  exit;
 }
}catch(InvalidArgumentException|RuntimeException $e){
 $error=$e->getMessage();
}catch(Throwable $e){
 error_log('Revisão: '.$e->getMessage());
 http_response_code(503);exit('Revisão indisponível.');
}
try{
 $seguro=$service->seguro((int)$id);
 if(!$seguro){http_response_code(404);exit('Seguro não encontrado.');}
 $rev=$service->atual((int)$id);
 $versao=RevisaoSeguroService::versao($rev);
 $history=$service->historico((int)$id);
}catch(Throwable $e){
 error_log('Consulta de revisão: '.$e->getMessage());
 http_response_code(503);exit('Consulta indisponível.');
}
require '/var/www/src/views/revisar.php';
