<?php
declare(strict_types=1);

/**
 * Entrada HTTP do painel inicial (MVC).
 *
 * 1. Exige sessão autenticada;
 * 2. Consulta dados usando Repositories e DashboardController;
 * 3. Entrega os dados à View PHP, sem expor conexão ou credenciais;
 * 4. Em caso de indisponibilidade do banco, mostra aviso controlado.
 *
 * Esta rota não realiza gravações, alterações em apólices ou envios WhatsApp.
 */
require_once '/var/www/src/Auth.php';
Auth::requireLogin();
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store');

require_once '/var/www/src/DashboardController.php';

$dbOk=false;
$dataHoje=(new DateTimeImmutable('now',new DateTimeZone('America/Sao_Paulo')))
    ->format('Y-m-d');
$kpi=['automacoes'=>0,'execucoes'=>0,'sucessos'=>0,'falhas'=>0];
$automacoes=$execucoes=$eventos=[];
$resumoDia=['total'=>0,'paraConferencia'=>0,'vendedores'=>0,
    'listados'=>0,'omitidos'=>0,'grupos'=>[]];
$estadoSheet=$n8nResumo=null;
$n8nUltimas=[];
$status=null;
$automacaoId=null;

try{
    // A mesma instância PDO é injetada em cada repositório.
    $db=Database::connection();
    $dashboard=new DashboardController(
        new MonitorRepository($db),
        new SeguroRepository($db),
        new N8nHistoricoRepository($db)
    );
    $dados=$dashboard->index($_GET);
    $dataHoje=$dados['dataHoje'];
    $kpi=$dados['kpi'];
    $automacoes=$dados['automacoes'];
    $status=$dados['status'];
    $automacaoId=$dados['automacaoId'];
    $execucoes=$dados['execucoes'];
    $eventos=$dados['eventos'];
    $resumoDia=$dados['resumoDia'];
    $estadoSheet=$dados['estadoSheet'];
    $n8nResumo=$dados['n8nResumo'];
    $n8nUltimas=$dados['n8nUltimas'];
    $dbOk=true;
}catch(Throwable $e){
    error_log('Dashboard MVC: '.$e->getMessage());
    http_response_code(503);
}

require '/var/www/src/views/dashboard.php';
