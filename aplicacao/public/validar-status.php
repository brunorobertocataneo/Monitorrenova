<?php
declare(strict_types=1);
require_once '/var/www/src/Auth.php';Auth::requireLogin();
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store');
require_once '/var/www/src/ValidacaoApolice.php';
function h(mixed $value):string{return htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
$id=filter_var($_GET['id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
if(!$id){http_response_code(400);exit('Identificador de seguro inválido.');}
$today=new DateTimeImmutable('today',new DateTimeZone('America/Sao_Paulo'));
$error=null;
try{
    $service=new ValidacaoApolice(Database::connection());
    $seguro=$service->getSeguro($id);
    if(!$seguro){http_response_code(404);exit('Seguro não encontrado.');}
    if(($_SERVER['REQUEST_METHOD']??'')==='POST'){
        if(!Auth::verifyCsrf($_POST['csrf']??null))throw new InvalidArgumentException('Sessão inválida. Recarregue a página.');
        $expectedFp=$_POST['versao_origem']??null;
        $expectedLatest=$_POST['versao_validacao']??null;
        if(!is_string($expectedFp)||!is_string($expectedLatest)||mb_strlen($expectedLatest)>30)throw new InvalidArgumentException('Versão inválida. Recarregue a página.');
        $service->registrar($id,(int)$_SESSION['usuario_id'],$_POST,$expectedFp,$expectedLatest,$today);
        header('Location: /validar-status.php?id='.$id.'&salvo=1',true,303);exit;
    }
    $last=$service->ultima($id);
    $history=$service->historico($id);
    $fingerprint=ValidacaoApolice::origemFingerprint($seguro);
    $latestToken=$last?(string)$last['id']:'novo';
    $sourceChanged=$last!==null&&!hash_equals((string)$last['fingerprint_origem'],$fingerprint);
    $refDate=$last!==null?(DateTimeImmutable::createFromFormat('!Y-m-d',$last['data_consulta'],new DateTimeZone('America/Sao_Paulo'))?:null):null;
    $isRecent=$refDate!==null&&$refDate>=$today->modify('-7 days')&&$refDate<=$today;
}catch(InvalidArgumentException|RuntimeException $e){
    $error=$e->getMessage();
    try{
       $last=$service->ultima($id);
       $history=$service->historico($id);
       $seguro=$service->getSeguro($id);
       $fingerprint=ValidacaoApolice::origemFingerprint($seguro);
       $latestToken=$last?(string)$last['id']:'novo';
       $sourceChanged=$last!==null&&!hash_equals((string)$last['fingerprint_origem'],$fingerprint);
       $refDate=$last!==null?(DateTimeImmutable::createFromFormat('!Y-m-d',$last['data_consulta'],new DateTimeZone('America/Sao_Paulo'))?:null):null;
       $isRecent=$refDate!==null&&$refDate>=$today->modify('-7 days')&&$refDate<=$today;
    }catch(Throwable $ignored){error_log('Error displaying validation form');http_response_code(503);exit('Consulta indisponível.');}
}catch(Throwable $e){error_log('Validation form: '.$e->getMessage());http_response_code(503);exit('Consulta indisponível.');}
$displaySituacao=static fn(?string $s):string=>ValidacaoApolice::SITUACOES[$s??'']??'Não verificada';
$displayFonte=static fn(?string $s):string=>ValidacaoApolice::FONTES[$s??'']??'Não informado';
require '/var/www/src/views/validar-status.php';
