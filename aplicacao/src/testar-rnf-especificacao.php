<?php
declare(strict_types=1);
/**
 * Evidências RNF03, RNF06 e checagem indiciária RNF07.
 * Execute SOMENTE em banco de demonstração descartável (CI_RNF_ISOLATED=1).
 * A transação externa é revertida, sem alterar a massa de testes.
 * NÃO equivale a aceite de todos os casos de revisão/privacidade.
 */
if (PHP_SAPI !== 'cli' || getenv('CI_RNF_ISOLATED') !== '1') {
    fwrite(STDERR, "Execute apenas em base isolada com CI_RNF_ISOLATED=1.\n");
    exit(2);
}
require_once __DIR__.'/Database.php';
require_once __DIR__.'/RevisaoSeguroService.php';
$db = Database::connection();
$issues=[];
function verifyRNF(bool $ok,string $name):void {
    global $issues;
    echo ($ok ? '[OK] ' : '[FALHA] ').$name.PHP_EOL;
    if (!$ok) $issues[]=$name;
}
$preexisting = (int)$db->query("SELECT COUNT(*) FROM usuario")->fetchColumn();
$db->beginTransaction();
try {
    $login='ci-rnf-'.bin2hex(random_bytes(6));
    $secret=bin2hex(random_bytes(20));
    $hash=password_hash($secret,PASSWORD_DEFAULT);
    $stmt=$db->prepare("INSERT INTO usuario(login,senha_hash) VALUES(?,?)");
    $stmt->execute([$login,$hash]);
    $uid=(int)$db->lastInsertId();
    $all=$db->query("SELECT senha_hash FROM usuario")->fetchAll(PDO::FETCH_COLUMN);
    $allHashes=true;
    foreach($all as $stored){
        if (!is_string($stored) || (password_get_info($stored)['algo']??null)===null) {
            $allHashes=false; break;
        }
        if (password_get_info($stored)['algo']===0) {$allHashes=false; break;}
    }
    verifyRNF($allHashes && password_verify($secret,$hash),'RNF03 senhas na amostra armazenadas como hash');
    echo "RNF03 contas preexistentes analisadas: $preexisting (sem exibir segredos).\n";
    $stmt=$db->query("SELECT s.id FROM seguro_mensal s LEFT JOIN revisao_seguro r ON r.seguro_mensal_id=s.id WHERE r.id IS NULL LIMIT 1");
    $sid=(int)$stmt->fetchColumn();
    if($sid<1)throw new RuntimeException('Nenhum seguro sem revisão na base demo');
    $service=new RevisaoSeguroService($db);
    $before=(int)$db->query("SELECT COUNT(*) FROM historico_revisao")->fetchColumn();
    $saved=$service->registrar($sid,$uid,'em_analise','Revisão sintética executada em teste de RNF06','novo');
    $rows=$service->historico($sid);
    $after=(int)$db->query("SELECT COUNT(*) FROM historico_revisao")->fetchColumn();
    verifyRNF($saved && $after===$before+1 && count($rows)===1 &&
        $rows[0]['login']===$login &&
        $rows[0]['situacao_nova']==='em_analise' &&
        !empty($rows[0]['criado_em']),
        'RNF06 revisão com autoria, situação, data e trilha preservada (cenário)');
    $r=$db->query("SELECT segurado,vendedor FROM seguro_mensal")->fetchAll(PDO::FETCH_ASSOC);
    $suspicious=0;
    foreach($r as $line){
        $v=implode(' ',array_values($line));
        if(preg_match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\\.[A-Z]{2,}/i',$v) ||
            preg_match('/(?:\\D|^)(?:\\d[. -]?){11}(?:\\D|$)/',$v))$suspicious++;
    }
    verifyRNF($suspicious===0 && count($r)>0,
        'RNF07 triagem automatizada limitada de e-mail/CPF em segurados e vendedores fictícios');
    echo 'RNF07 linhas examinadas: '.count($r).'; isto NÃO confirma ausência de todo dado pessoal.\n';
} finally {
    if($db->inTransaction())$db->rollBack();
}
echo 'FALHAS='.count($issues).PHP_EOL;
exit($issues===[]?0:1);
