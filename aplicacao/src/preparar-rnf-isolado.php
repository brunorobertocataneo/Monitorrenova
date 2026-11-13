<?php
declare(strict_types=1);
/** Somente CI em banco descartável. */
if (PHP_SAPI !== 'cli' || getenv('CI_RNF_ISOLATED') !== '1') exit(2);
require_once __DIR__.'/Database.php';
$db=Database::connection();
$mode=$argv[1]??'';
if($mode==='admin'){
    $pass=getenv('CI_RNF_PASSWORD');
    if(!is_string($pass)||strlen($pass)<16)throw new RuntimeException('Credencial de demonstração ausente');
    $n=(int)$db->query('SELECT COUNT(*) FROM usuario')->fetchColumn();
    if($n!==0)throw new RuntimeException('Teste exige banco sem contas');
    $q=$db->prepare('INSERT INTO usuario(login,senha_hash) VALUES (?,?)');
    $q->execute(['admin',password_hash($pass,PASSWORD_DEFAULT)]);
    echo "RNF09 ADMIN_DEMO_PREPARADO\n";
    exit(0);
}
if($mode==='carga'){
    $present=(int)$db->query("SELECT COUNT(*) FROM seguro_mensal")->fetchColumn();
    if($present!==65)throw new RuntimeException("A massa deve conter 65 seguros antes da carga; recebeu $present");
    $db->beginTransaction();
    try {
        $q=$db->prepare("INSERT INTO seguro_mensal(competencia,linha_origem,segurado,vendedor,vencimento,produto,renovado,cobrado,nao_vai_renovar) VALUES ('2027-12',?,?,?,?, 'Seguro ficticio',0,0,0)");
        for($i=2;$i<=501;$i++)$q->execute([$i,'SEGURO-TESTE-'.str_pad((string)$i,4,'0',STR_PAD_LEFT),'Vendedor Sintetico '.($i%5),'2027-12-15']);
        $db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
    $total=(int)$db->query('SELECT COUNT(*) FROM seguro_mensal')->fetchColumn();
    if($total!==565)throw new RuntimeException("Esperado 565 registros após inserção, recebeu $total");
    echo "RNF01 CARGA_FICTICIA_OK total=$total, adicionais=500\n";
    exit(0);
}
fwrite(STDERR,"Uso: admin | carga\n");exit(2);
