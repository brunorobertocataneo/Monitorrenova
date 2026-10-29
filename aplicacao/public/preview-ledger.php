<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
function respond(int $status, array $body): never {
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
if(($_SERVER['REQUEST_METHOD']??'')!=='POST') respond(405,['ok'=>false,'error'=>'METHOD']);
$expected=getenv('INGEST_TOKEN');
$given=$_SERVER['HTTP_X_MONITOR_TOKEN']??'';
if(!is_string($expected)||strlen($expected)<32||!is_string($given)||!hash_equals($expected,$given)) respond(401,['ok'=>false,'error'=>'AUTH']);
if((int)($_SERVER['CONTENT_LENGTH']??0)>2048)respond(413,['ok'=>false,'error'=>'SIZE']);
if(strtolower(trim(explode(';',(string)($_SERVER['CONTENT_TYPE']??''))[0]))!=='application/json')respond(415,['ok'=>false,'error'=>'TYPE']);
try{
  $request=json_decode((string)file_get_contents('php://input',false,null,0,2049),true,8,JSON_THROW_ON_ERROR);
  if(!is_array($request)||array_is_list($request))throw new InvalidArgumentException('BODY');
  foreach(array_keys($request) as $key)if(!in_array($key,['action','idempotency_key','execution','provider_id'],true))throw new InvalidArgumentException('FIELD');
  $action=$request['action']??null;
  $key=$request['idempotency_key']??null;
  if(!in_array($action,['reserve','accepted','uncertain','status'],true)||!is_string($key)||!preg_match('/^[a-f0-9]{64}$/D',$key))throw new InvalidArgumentException('PARAMS');
  $pdo=require_once '/var/www/src/Database.php';
  $pdo=Database::connection();
  $pdo->exec("CREATE TABLE IF NOT EXISTS piloto_envio_guard (idempotency_key CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY, estado VARCHAR(16) NOT NULL, execution_ref VARCHAR(80), provider_id VARCHAR(200), criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP, atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB");
  if($action==='reserve'){
    $ref=(string)($request['execution']??'');
    if(strlen($ref)>80)throw new InvalidArgumentException('EXECUTION');
    $stmt=$pdo->prepare("INSERT IGNORE INTO piloto_envio_guard(idempotency_key,estado,execution_ref) VALUES(?,'RESERVADO',?)");
    $stmt->execute([$key,$ref]);
    $new=$stmt->rowCount()===1;
    $state=$pdo->prepare('SELECT estado FROM piloto_envio_guard WHERE idempotency_key=?');
    $state->execute([$key]);
    respond(200,['ok'=>true,'reserved'=>$new,'duplicate'=>!$new,'state'=>$state->fetchColumn()]);
  }
  if($action==='status'){
    $stmt=$pdo->prepare('SELECT estado FROM piloto_envio_guard WHERE idempotency_key=?');$stmt->execute([$key]);
    respond(200,['ok'=>true,'state'=>$stmt->fetchColumn()?:'NAO_EXISTE']);
  }
  $provider=(string)($request['provider_id']??'');
  if(strlen($provider)>200)throw new InvalidArgumentException('PROVIDER');
  $newState=$action==='accepted'?'ACEITO_API':'INCERTO';
  $stmt=$pdo->prepare("UPDATE piloto_envio_guard SET estado=?, provider_id=? WHERE idempotency_key=? AND estado='RESERVADO'");
  $stmt->execute([$newState,$provider,$key]);
  respond(200,['ok'=>true,'updated'=>$stmt->rowCount()===1,'state'=>$newState]);
}catch(InvalidArgumentException){respond(422,['ok'=>false,'error'=>'VALIDATION']);}
catch(Throwable $e){error_log('preview-ledger: '.$e->getMessage());respond(500,['ok'=>false,'error'=>'INTERNAL']);}
