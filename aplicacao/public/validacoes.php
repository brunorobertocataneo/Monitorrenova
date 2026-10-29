<?php
declare(strict_types=1);
require_once '/var/www/src/Auth.php';
Auth::requireLogin();
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store');
require_once '/var/www/src/ValidacaoApolice.php';
require_once '/var/www/src/ValidacoesRepository.php';
require_once '/var/www/src/ValidacoesController.php';
try{
    $controller=new ValidacoesController(
        new ValidacoesRepository(Database::connection())
    );
    $d=$controller->index($_GET);
    $labels=$d['labels'];$months=$d['months'];$month=$d['month'];
    $filter=$d['filter'];$stats=$d['stats'];
    $rows=$d['rows'];$page=$d['page'];$pages=$d['pages'];
    $total=$d['total'];$urlParams=$d['urlParams'];
}catch(Throwable $e){
    error_log('Validações MVC: '.$e->getMessage());
    http_response_code(503);exit('Acompanhamento das validações indisponível.');
}
require '/var/www/src/views/validacoes.php';
