<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
function reply(int $status,string $message,array $extra=[]):never {
    http_response_code($status);
    echo json_encode(['ok'=>$status>=200&&$status<300,'message'=>$message]+$extra,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
if(($_SERVER['REQUEST_METHOD']??'')!=='POST') reply(405,'Metodo nao permitido');
$expected=getenv('INGEST_TOKEN');
$provided=$_SERVER['HTTP_X_MONITOR_TOKEN']??'';
if(!is_string($expected)||strlen($expected)<32||!is_string($provided)||!hash_equals($expected,$provided)){
    reply(401,'Nao autorizado');
}
$type=strtolower(trim(explode(';',(string)($_SERVER['CONTENT_TYPE']??''))[0]));
if($type!=='application/json')reply(415,'Somente application/json');
if((int)($_SERVER['CONTENT_LENGTH']??0)>4096)reply(413,'Corpo excede 4 KB');
$raw=file_get_contents('php://input',false,null,0,4097);
if(!is_string($raw)||strlen($raw)>4096)reply(413,'Corpo excede 4 KB');
try{$data=json_decode($raw,true,12,JSON_THROW_ON_ERROR);}
catch(Throwable){reply(400,'JSON invalido');}
if(!is_array($data)||array_is_list($data))reply(400,'Objeto JSON esperado');
$allowed=['modo','workflow_id','execution_id','status','total_processados','total_elegiveis','total_pendentes','total_enviados','codigo_erro'];
foreach(array_keys($data) as $key){
    if(!in_array($key,$allowed,true))reply(422,'Campo nao reconhecido');
}
try{
    require_once '/var/www/src/AuditIngestService.php';
    $valid=AuditIngestService::validate($data);
}catch(InvalidArgumentException $e){
    reply(422,$e->getMessage());
}catch(Throwable $e){
    error_log('Ingest validation: '.$e->getMessage());
    reply(500,'Erro na validacao');
}
try{
    $service=new AuditIngestService(Database::connection());
    $out=$service->record($valid);
    reply(200,$out['novo']?'Auditoria registrada':'Auditoria ja registrada, sem duplicidade',
        ['execution_record_id'=>$out['id'],'new_record'=>$out['novo']]);
}catch(DomainException){
    reply(409,'Id de execucao repetido com dados divergentes');
}catch(RuntimeException $e){
    if($e->getMessage()==='Automacao de auditoria nao cadastrada')reply(503,'Auditoria nao cadastrada');
    error_log('Ingest runtime: '.$e->getMessage());
    reply(500,'Erro interno');
}catch(Throwable $e){
    error_log('Ingest failure: '.$e->getMessage());
    reply(500,'Erro interno');
}
