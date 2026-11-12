<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { exit(1); }
require_once __DIR__ . '/Database.php';

$db = Database::connection();
$users = (int)$db->query('SELECT COUNT(*) FROM usuario')->fetchColumn();
if ($users !== 0) {
    fwrite(STDERR, "ABORTADO: o banco tem usuarios; nao modificar contas existentes.\n");
    exit(2);
}
$login = 'admin';
$password = bin2hex(random_bytes(18));
$cookie = '';
$newUser = 0;
$results = [];
function resultHttp(string $name, bool $condition, string $detail): void {
    global $results;
    $results[$name] = $condition;
    echo ($condition ? '[OK] ' : '[FALHA] ') . $name . ': ' . $detail . PHP_EOL;
}
function requestRNF(string $path, string $method='GET', ?array $data=null): array {
    global $cookie;
    $head = ['Connection: close'];
    if ($cookie !== '') { $head[] = 'Cookie: ' . $cookie; }
    if ($method === 'POST') { $head[] = 'Content-Type: application/x-www-form-urlencoded'; }
    $options = ['http'=>[
        'method'=>$method,'header'=>implode("\r\n",$head),"ignore_errors"=>true,
        'follow_location'=>0,'timeout'=>8,
    ]];
    if ($data !== null) { $options['http']['content'] = http_build_query($data); }
    $context = stream_context_create($options);
    $start = hrtime(true);
    $body = @file_get_contents('http://127.0.0.1' . $path,false,$context);
    $ms = (hrtime(true)-$start)/1e6;
    $headers = $http_response_header ?? [];
    $status = 0;
    foreach ($headers as $header) {
        if (preg_match('/^HTTP\/\S+\s+(\d{3})/', $header,$matches)) { $status=(int)$matches[1]; }
        if (preg_match('/^Set-Cookie:\s*(MONITOR_RENOVA_SESSION=[^;\s]+)/i',$header,$matches)) { $cookie=$matches[1]; }
    }
    return ['status'=>$status,'body'=>is_string($body)?$body:'','ms'=>$ms,'headers'=>$headers];
}
function countsHTTP(PDO $db): string {
    $parts = [];
    foreach (['tarefa_acompanhamento','historico_tarefa_acompanhamento'] as $t) {
        $parts[]=$t . '=' . $db->query("SELECT COUNT(*) FROM " . $t)->fetchColumn();
    }
    return implode(';',$parts);
}
try {
    $q=$db->prepare('INSERT INTO usuario (login,senha_hash) VALUES (?,?)');
    $q->execute([$login,password_hash($password,PASSWORD_DEFAULT)]);
    $newUser = (int)$db->lastInsertId();
    $loginPage=requestRNF('/login.php');
    if (!preg_match('/name="csrf" value="([^"]+)"/',$loginPage['body'],$matches)) {
        throw new RuntimeException('Token CSRF da tela de login nao foi encontrado');
    }
    $loginPost=requestRNF('/login.php','POST',['csrf'=>html_entity_decode($matches[1]),'senha'=>$password]);
    resultHttp('LOGIN_HTTP', $loginPost['status']===303,'HTTP '.$loginPost['status']);
    $page=requestRNF('/tarefas.php');
    resultHttp('TAREFAS_AUTENTICADA', $page['status']===200,'HTTP '.$page['status']);
    $before = countsHTTP($db);
    foreach (['criar','atualizar','excluir'] as $action) {
        foreach (['omitido','invalido'] as $case) {
            $fields=['acao'=>$action,'id'=>'987654321','versao'=>'1','titulo'=>'Teste CSRF sem salvamento','descricao'=>'Teste','prioridade'=>'media','situacao'=>'pendente','vencimento'=>'','seguro_id'=>''];
            if ($case === 'invalido') { $fields['csrf']='token-invalido'; }
            $response=requestRNF('/tarefas.php','POST',$fields);
            resultHttp('RNF06_'.$action.'_'.$case,$response['status']===403, 'HTTP '.$response['status']);
        }
    }
    $after=countsHTTP($db);
    resultHttp('RNF06_BANCO_NAO_ALTERADO',$before===$after,'antes '.$before.' / depois '.$after);
    $times=[];$statuses=[];
    for($i=0;$i<30;$i++) {
        $response=requestRNF('/seguros.php');
        $times[]=$response['ms'];
        $statuses[]=$response['status'];
    }
    sort($times,SORT_NUMERIC);
    $fast=count(array_filter($times,static fn(float $t):bool=>$t<=3000));
    $all200=count(array_filter($statuses,static fn(int $s):bool=>$s===200));
    resultHttp('RNF03_30_CONSULTAS_AUTENTICADAS',$all200===30 && $fast>=29,
        "HTTP200=$all200/30; <=3000ms=$fast/30; p50=".round(($times[14]+$times[15])/2,2)."ms; p95=".round($times[28],2)."ms; max=".round($times[29],2)."ms");
    $taskHtml=requestRNF('/tarefas.php');
    if (preg_match('/name="csrf" value="([^"]+)"/',$taskHtml['body'],$matches)) {
        requestRNF('/sair.php','POST',['csrf'=>html_entity_decode($matches[1])]);
    }
} finally {
    if ($newUser>0) {
        $q=$db->prepare('DELETE FROM usuario WHERE id=? AND login=?');
        $q->execute([$newUser,'admin']);
        echo "TEST_ADMIN_REMOVIDO=" . ($q->rowCount()===1?'SIM':'NAO') . PHP_EOL;
    }
}
$passed=count(array_filter($results));
echo "HTTP RNF: $passed/" . count($results) . " aprovados." . PHP_EOL;
exit($passed===count($results)?0:1);
