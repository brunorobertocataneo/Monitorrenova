<?php
declare(strict_types=1);
/**
 * Entrada HTTP do histórico n8n (MVC), somente leitura.
 * Não executa workflows nem permite envio de mensagens.
 */
require_once '/var/www/src/Auth.php';
Auth::requireLogin();
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store');
require_once '/var/www/src/N8nHistoricoRepository.php';
require_once '/var/www/src/N8nHistoricoController.php';

try{
    $controller=new N8nHistoricoController(
        new N8nHistoricoRepository(Database::connection())
    );
    $data=$controller->index($_GET);
    $workflow=$data['workflow'];
    $resumo=$data['resumo'];
    $execucoes=$data['execucoes'];
}catch(Throwable $e){
    error_log('Historico n8n indisponivel: '.$e->getMessage());
    http_response_code(503);
    exit('Histórico indisponível no momento.');
}
require '/var/www/src/views/historico-n8n.php';
