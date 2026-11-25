<?php
declare(strict_types=1);
require_once '/var/www/src/Auth.php';
Auth::requireLogin();
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');
require_once '/var/www/src/DashboardController.php';

$dbOk = false;
$dataHoje = (new DateTimeImmutable('today', new DateTimeZone('America/Sao_Paulo')))->format('Y-m-d');
$vencimentosHoje = [];
$resumoDia = [];
$estadoSheet = null;

try {
    $db = Database::connection();
    $controller = new DashboardController(new SeguroRepository($db), new VencimentoRepository($db));
    $dados = $controller->index();
    $dataHoje = $dados['dataHoje'];
    $vencimentosHoje = $dados['vencimentosHoje'];
    $resumoDia = $dados['resumoDia'];
    $estadoSheet = $dados['estadoSheet'];
    $dbOk = true;
} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(503);
}
require '/var/www/src/views/dashboard.php';
