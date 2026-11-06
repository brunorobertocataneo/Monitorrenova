<?php
declare(strict_types=1);
/**
 * Entry point MVC para os vencimentos.
 * Autenticação -> Controller -> Repository (MySQL) -> View.
 * Somente consultas: nenhum envio ou alteração de apólice nesta rota.
 */
require_once '/var/www/src/Auth.php';
Auth::requireLogin();
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store');
require_once '/var/www/src/Database.php';
require_once '/var/www/src/VencimentoController.php';
try {
    $controller=new VencimentoController(new VencimentoRepository(Database::connection()));
    $dados=$controller->index($_GET);
    $de=$dados['de'];
    $dataHoje=$dados['dataHoje'];
    $vencimentosHoje=$dados['vencimentosHoje'];
    $ate=$dados['ate'];
    $vendedor=$dados['vendedor'];
    $sellers=$dados['sellers'];
    $atalhos=$dados['atalhos'];
    $stats=$dados['stats'];
    $bySeller=$dados['bySeller'];
    $byProduct=$dados['byProduct'];
    $byDate=$dados['byDate'];
    $import=$dados['import'];
    $estadoSheet=$dados['estadoSheet'];
    $total=$dados['total'];
    $maxSeller=$dados['maxSeller'];
    $maxProduct=$dados['maxProduct'];
    $maxDate=$dados['maxDate'];
} catch (Throwable $e) {
    error_log('Dashboard de vencimentos: '.$e->getMessage());
    http_response_code(503);
    exit('Dashboard indisponível.');
}
require '/var/www/src/views/vencimentos.php';
