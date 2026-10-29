<?php
declare(strict_types=1);
require_once '/var/www/src/Auth.php';
Auth::requireLogin();
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store');
require_once '/var/www/src/HistoricoMensalRepository.php';
require_once '/var/www/src/HistoricoMensalController.php';
try{
    $controller=new HistoricoMensalController(
        new HistoricoMensalRepository(Database::connection())
    );
    $d=$controller->index($_GET);
    $fonte=$d['fonte'];$months=$d['months'];$month=$d['month'];
    $vendors=$d['vendors'];$vendor=$d['vendor'];
    $stats=$d['stats'];$byVendor=$d['byVendor'];
    $byProduct=$d['byProduct'];$rows=$d['rows'];
    $lots=$d['lots'];$max=$d['max'];
}catch(Throwable $e){
    error_log('Histórico mensal MVC: '.$e->getMessage());
    http_response_code(503);exit('Consulta mensal indisponível.');
}
require '/var/www/src/views/meses.php';
