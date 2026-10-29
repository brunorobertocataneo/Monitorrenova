<?php
declare(strict_types=1);

/**
 * API privada n8n -> PHP para atualizar a cópia da aba "_BASE".
 * Sem acesso público (Docker publica o site apenas em 127.0.0.1).
 * Aceita apenas POST autenticado e JSON; nunca envia WhatsApp.
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

function responderSync(int $http, array $resultado): never
{
    http_response_code($http);
    echo json_encode($resultado,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
if (($_SERVER['REQUEST_METHOD']??'')!=='POST') {
    responderSync(405,['ok'=>false,'error'=>'METHOD']);
}

$tokenEsperado=getenv('INGEST_TOKEN');
$tokenEnviado=$_SERVER['HTTP_X_MONITOR_TOKEN']??'';
if (!is_string($tokenEsperado) || strlen($tokenEsperado)<32 ||
    !is_string($tokenEnviado) || !hash_equals($tokenEsperado,$tokenEnviado)) {
    responderSync(401,['ok'=>false,'error'=>'AUTH']);
}
if (strtolower(trim(explode(';',(string)($_SERVER['CONTENT_TYPE']??''))[0]))!=='application/json') {
    responderSync(415,['ok'=>false,'error'=>'TYPE']);
}
$bytes=262144;
if ((int)($_SERVER['CONTENT_LENGTH']??0)>$bytes) {
    responderSync(413,['ok'=>false,'error'=>'SIZE']);
}
$texto=file_get_contents('php://input',false,null,0,$bytes+1);
if (!is_string($texto)||strlen($texto)>$bytes) {
    responderSync(413,['ok'=>false,'error'=>'SIZE']);
}
try {
    $carga=json_decode($texto,true,20,JSON_THROW_ON_ERROR);
    if (!is_array($carga)||array_is_list($carga)) throw new InvalidArgumentException('JSON precisa ser objeto');

    // Somente o n8n autenticado deve escolher quando gravar.
    $mode=$carga['mode']??null;
    if (!in_array($mode,['preview','apply'],true)) throw new InvalidArgumentException('Modo inesperado');
    unset($carga['mode']);

    require_once '/var/www/src/Database.php';
    require_once '/var/www/src/SheetSyncService.php';
    $servico=new SheetSyncService(Database::connection());
    responderSync(200,$servico->executar($carga,$mode==='apply'));
} catch (InvalidArgumentException $e) {
    responderSync(422,['ok'=>false,'error'=>'VALIDATION']);
} catch (DomainException $e) {
    // Sem mensagens ou nomes em resposta: informações só no log local.
    error_log('SheetSync conflito: '.$e->getMessage());
    responderSync(409,['ok'=>false,'error'=>'CONFLICT']);
} catch (Throwable $e) {
    error_log('SheetSync excecao: '.$e->getMessage());
    responderSync(500,['ok'=>false,'error'=>'INTERNAL']);
}
