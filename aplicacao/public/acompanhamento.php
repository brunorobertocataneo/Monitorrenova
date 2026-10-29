<?php
declare(strict_types=1);
require_once '/var/www/src/Auth.php';
Auth::requireLogin();
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store');
require_once '/var/www/src/AcompanhamentoRepository.php';
require_once '/var/www/src/AcompanhamentoController.php';
try{
    $controller=new AcompanhamentoController(
        new AcompanhamentoRepository(Database::connection())
    );
    $d=$controller->index($_GET);
    $months=$d['months'];$mes=$d['mes'];
    $statuses=$d['statuses'];$filter=$d['filter'];
    $counts=$d['counts'];$rows=$d['rows'];$total=$d['total'];
    $page=$d['page'];$pages=$d['pages'];$qs=$d['qs'];
}catch(Throwable $e){
    error_log('Acompanhamento MVC: '.$e->getMessage());
    http_response_code(503);exit('Acompanhamento indisponível.');
}
require '/var/www/src/views/acompanhamento.php';
