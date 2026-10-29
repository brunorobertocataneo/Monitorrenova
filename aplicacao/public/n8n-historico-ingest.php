<?php
declare(strict_types=1);

/**
 * Ponte privada de metadados de execução n8n → PHP.
 * O token é o mesmo header já usado pelas outras integrações internas.
 * Nunca recebe payloads, nomes, mensagens, números de telefone ou tokens
 * do n8n; expõe apenas sucesso/falha da importação de metadados.
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function historicoResposta(int $status,array $dados):never
{
    http_response_code($status);
    echo json_encode($dados,JSON_UNESCAPED_UNICODE);
    exit;
}
if(($_SERVER['REQUEST_METHOD']??'')!=='POST'){
    historicoResposta(405,['ok'=>false,'erro'=>'METHOD']);
}
$secret=getenv('INGEST_TOKEN');
$given=$_SERVER['HTTP_X_MONITOR_TOKEN']??null;
if(!is_string($secret)||strlen($secret)<32||
   !is_string($given)||!hash_equals($secret,$given)){
    historicoResposta(401,['ok'=>false,'erro'=>'AUTH']);
}
$mime=strtolower(trim(explode(';',(string)($_SERVER['CONTENT_TYPE']??''))[0]));
if($mime!=='application/json'){
    historicoResposta(415,['ok'=>false,'erro'=>'TYPE']);
}
$max=40000;
if((int)($_SERVER['CONTENT_LENGTH']??0)>$max){
    historicoResposta(413,['ok'=>false,'erro'=>'SIZE']);
}
$json=file_get_contents('php://input',false,null,0,$max+1);
if(!is_string($json)||strlen($json)>$max){
    historicoResposta(413,['ok'=>false,'erro'=>'SIZE']);
}
try{
    $request=json_decode($json,true,12,JSON_THROW_ON_ERROR);
    if(!is_array($request)||array_is_list($request)){
        throw new InvalidArgumentException('JSON esperado');
    }
    require_once '/var/www/src/Database.php';
    require_once '/var/www/src/HistoricoN8nService.php';
    $service=new HistoricoN8nService(Database::connection());
    historicoResposta(200,$service->registrar($request));
}catch(InvalidArgumentException $e){
    historicoResposta(422,['ok'=>false,'erro'=>'VALIDATION']);
}catch(Throwable $e){
    error_log('n8n historico metadata: '.$e->getMessage());
    historicoResposta(500,['ok'=>false,'erro'=>'INTERNAL']);
}
