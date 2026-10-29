<?php
declare(strict_types=1);

/**
 * Ponto de entrada HTTP (roteamento simplificado).
 * Valida a sessão, chama o Controller e carrega a View.
 * Todas as consultas SQL ficam no SeguroRepository (Model).
 */
require_once '/var/www/src/Auth.php';
Auth::requireLogin();
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store');

require_once '/var/www/src/Database.php';
require_once '/var/www/src/SeguroController.php';

try {
    $controller = new SeguroController(
        new SeguroRepository(Database::connection())
    );
    $dados = $controller->index($_GET);

    // Apenas os campos esperados do Controller entram no escopo da View.
    $vendedor = $dados['vendedor'];
    $produto = $dados['produto'];
    $de = $dados['de'];
    $ate = $dados['ate'];
    $busca = $dados['busca'];
    $pagina = $dados['pagina'];
    $limite = $dados['limite'];
    $vendors = $dados['vendors'];
    $products = $dados['products'];
    $total = $dados['total'];
    $totalPages = $dados['totalPages'];
    $rows = $dados['rows'];
    $ultimaImportacao = $dados['ultimaImportacao'];
    $stats = $dados['stats'];
    $paramsUrl = $dados['paramsUrl'];
} catch (Throwable $erro) {
    // Detalhes internos apenas no log, nunca no HTML do usuário.
    error_log($erro->getMessage());
    http_response_code(503);
    exit('Consulta indisponível no momento.');
}

require '/var/www/src/views/seguros.php';
