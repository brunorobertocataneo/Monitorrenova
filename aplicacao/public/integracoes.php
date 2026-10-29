<?php
declare(strict_types=1);
/**
 * Entrada HTTP das integrações (MVC).
 * Somente diagnóstico local, sem ações de envio ou alteração do n8n.
 */
require_once '/var/www/src/Auth.php';
Auth::requireLogin();
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store');
require_once '/var/www/src/WhatsAppStatus.php';
require_once '/var/www/src/N8nHistoricoRepository.php';
require_once '/var/www/src/IntegracoesController.php';

try{
    $controller=new IntegracoesController(Database::connection());
    $data=$controller->index();
    $wa=$data['wa'];
    $dbOK=$data['dbOK'];
    $stats=$data['stats'];
    $historicoResumo=$data['historicoResumo'];
    $ultimaAuditoriaN8n=$data['ultimaAuditoriaN8n'];
    $title=$data['title'];
    $ultimoEstadoAuditoria=$data['ultimoEstadoAuditoria'];
    $ingestReady=$data['ingestReady'];
}catch(Throwable $e){
    error_log('Integracoes indisponiveis: '.$e->getMessage());
    http_response_code(503);
    exit('Integrações indisponíveis no momento.');
}
require '/var/www/src/views/integracoes.php';
