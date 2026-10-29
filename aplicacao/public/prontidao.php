<?php
declare(strict_types=1);
require_once '/var/www/src/Auth.php';
Auth::requireLogin();
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store');
require_once '/var/www/src/AuditoriaProntidao.php';
require_once '/var/www/src/ProntidaoController.php';
try{
    $controller=new ProntidaoController(
        new AuditoriaProntidao(Database::connection())
    );
    $d=$controller->index($_GET);
    $months=$d['months'];$mes=$d['mes'];
    $latest=$d['latest'];$history=$d['history'];$labels=$d['labels'];
}catch(Throwable $e){
    error_log('Prontidão MVC: '.$e->getMessage());
    http_response_code(503);exit('Auditoria de prontidão indisponível.');
}
require '/var/www/src/views/prontidao.php';
