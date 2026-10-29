<?php
declare(strict_types=1);

/**
 * Endpoint interno de leitura usado pelo resumo diário do próprio dono.
 * Exige header X-Monitor-Token, a credencial já utilizada pelo n8n.
 * Nunca expõe a lista no navegador de um visitante não autenticado.
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

function responderAprovados(int $http,array $body):never
{
    http_response_code($http);
    echo json_encode($body,JSON_UNESCAPED_UNICODE|
        JSON_INVALID_UTF8_SUBSTITUTE|JSON_THROW_ON_ERROR);
    exit;
}
if(($_SERVER['REQUEST_METHOD']??'')!=='GET'){
    responderAprovados(405,['ok'=>false,'error'=>'METHOD']);
}
$token=getenv('INGEST_TOKEN');
$enviado=$_SERVER['HTTP_X_MONITOR_TOKEN']??null;
if(!is_string($token)||strlen($token)<32||
   !is_string($enviado)||!hash_equals($token,$enviado)){
    responderAprovados(401,['ok'=>false,'error'=>'AUTH']);
}
try{
    require_once '/var/www/src/Database.php';
    require_once '/var/www/src/ResumoAprovadoRepository.php';
    $repository=new ResumoAprovadoRepository(Database::connection());
    responderAprovados(200,$repository->obter());
}catch(DomainException $e){
    // Falhar fechado: nenhum envio se mês/atualização for inválido.
    error_log('Resumo aprovado bloqueado: '.$e->getMessage());
    responderAprovados(409,['ok'=>false,'error'=>'SOURCE_NOT_READY']);
}catch(Throwable $e){
    error_log('Resumo aprovado indisponível: '.$e->getMessage());
    responderAprovados(503,['ok'=>false,'error'=>'INTERNAL']);
}
