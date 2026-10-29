<?php
declare(strict_types=1);

require_once '/var/www/src/Auth.php';
Auth::requireLogin();
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store');
header('Content-Security-Policy: default-src \'self\'; style-src \'self\' \'unsafe-inline\'; form-action \'self\'; base-uri \'none\'');

require_once '/var/www/src/TarefaService.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if (!in_array($method, ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    http_response_code(405);
    exit('Método não permitido.');
}

$erro = null;
$sucesso = [
    'criada' => 'Tarefa cadastrada com sucesso.',
    'atualizada' => 'Tarefa atualizada com sucesso.',
    'excluida' => 'Tarefa arquivada (exclusão lógica).',
];
$mensagem = isset($_GET['ok']) && is_string($_GET['ok'])
    ? ($sucesso[$_GET['ok']] ?? null) : null;
$formEnviado = null;
$editarId = null;

try {
    $repository = new TarefaRepository(Database::connection());
    $service = new TarefaService($repository);

    if ($method === 'POST') {
        if (!Auth::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(403);
            throw new InvalidArgumentException('Token de segurança inválido. Recarregue a página.');
        }

        $acao = $_POST['acao'] ?? null;
        if (!is_string($acao) || !in_array($acao, ['criar', 'atualizar', 'excluir'], true)) {
            throw new InvalidArgumentException('Ação inválida.');
        }
        $usuarioId = (int) ($_SESSION['usuario_id'] ?? 0);
        $formEnviado = $_POST;
        if ($acao === 'criar') {
            $service->criar($_POST, $usuarioId);
            header('Location: /tarefas.php?ok=criada', true, 303);
            exit;
        }
        if ($acao === 'atualizar') {
            $editarId = TarefaService::identificador($_POST['id'] ?? null);
            $service->atualizar($_POST['id'], $_POST['versao'] ?? null, $_POST, $usuarioId);
            header('Location: /tarefas.php?ok=atualizada', true, 303);
            exit;
        }
        $service->excluir($_POST['id'] ?? null, $_POST['versao'] ?? null, $usuarioId);
        header('Location: /tarefas.php?ok=excluida', true, 303);
        exit;
    }
} catch (InvalidArgumentException|RuntimeException $e) {
    $erro = $e->getMessage();
} catch (Throwable $e) {
    error_log('Tarefas CRUD: ' . $e->getMessage());
    http_response_code(503);
    exit('Módulo de tarefas indisponível.');
}

try {
    if ($method === 'GET' && isset($_GET['editar'])) {
        $editarId = TarefaService::identificador($_GET['editar']);
    }
    $editando = $editarId !== null ? $repository->buscar($editarId) : null;
    if ($editarId !== null && $editando === null) {
        $erro = $erro ?? 'Esta tarefa não existe ou já foi arquivada.';
    }

    $filtroSituacao = is_string($_GET['situacao'] ?? null)
        ? (string) $_GET['situacao'] : '';
    if ($filtroSituacao !== '' && !isset(TarefaService::SITUACOES[$filtroSituacao])) {
        $filtroSituacao = '';
    }
    $filtroBusca = is_string($_GET['busca'] ?? null)
        ? mb_substr(trim($_GET['busca']), 0, 100) : '';

    $paginaValor = $_GET['pagina'] ?? '1';
    $pagina = (is_string($paginaValor) || is_int($paginaValor))
        && preg_match('/^[0-9]{1,5}$/D', (string) $paginaValor)
        ? max(1, min(10000, (int) $paginaValor)) : 1;

    $resultado = $repository->listar($filtroSituacao, $filtroBusca, $pagina);
    $rows = $resultado['registros'];
    $total = $resultado['total'];
    $pagina = $resultado['pagina'];
    $paginas = $resultado['totalPaginas'];
    $seguros = $repository->segurosParaAssociar();
    $excluidas = $repository->contarExcluidas();
} catch (InvalidArgumentException $e) {
    $erro = $e->getMessage();
    $editando = null;
    $filtroSituacao = '';
    $filtroBusca = '';
    $pagina = 1;
    $resultado = $repository->listar();
    $rows = $resultado['registros'];
    $total = $resultado['total'];
    $paginas = $resultado['totalPaginas'];
    $seguros = $repository->segurosParaAssociar();
    $excluidas = $repository->contarExcluidas();
} catch (Throwable $e) {
    error_log('Consulta de tarefas: ' . $e->getMessage());
    http_response_code(503);
    exit('Não foi possível consultar as tarefas.');
}

require '/var/www/src/views/tarefas.php';
