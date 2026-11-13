<?php
declare(strict_types=1);
/**
 * RNF07: varredura dos dados textuais em TODAS as tabelas de um banco demo.
 * Não imprime valores nem linhas, apenas contagens por tabela/campo.
 * Detecta padrões estruturais (e-mail, CPF e telefones) mas não prova que
 * um nome fictício não corresponda a uma pessoa real.
 */
if (PHP_SAPI !== 'cli' || getenv('CI_RNF_ISOLATED') !== '1') exit(2);
require_once __DIR__ . '/Database.php';
$db=Database::connection();
$database=(string)$db->query('SELECT DATABASE()')->fetchColumn();
if ($database !== 'monitor_renova_demo') {
    fwrite(STDERR,"RNF07 exige banco descartável monitor_renova_demo.\n");
    exit(2);
}
$tables=$db->query("SELECT TABLE_NAME,COLUMN_NAME,DATA_TYPE FROM information_schema.COLUMNS
 WHERE TABLE_SCHEMA=DATABASE() AND DATA_TYPE IN ('varchar','char','text','tinytext','mediumtext','longtext')
 ORDER BY TABLE_NAME,COLUMN_NAME")->fetchAll(PDO::FETCH_ASSOC);
$found=[]; $checked=0; $fields=0; $tableNames=[];
foreach($tables as $col) {
    $table=$col['TABLE_NAME'];$field=$col['COLUMN_NAME'];
    // Hash e identificadores técnicos são cadeias aleatórias, não contato de segurado.
    if(preg_match('/hash|token|fingerprint|senha|secret|checksum|idempotency/i',$field))continue;
    if(!preg_match('/^[A-Za-z0-9_]+$/D',$table.$field))throw new RuntimeException('Nome SQL inválido');
    $stmt=$db->query("SELECT `$field` AS valor FROM `$table` WHERE `$field` IS NOT NULL");
    $fields++;$tableNames[$table]=true;
    $hit=0;
    while(($value=$stmt->fetchColumn())!==false){
        $checked++;
        $str=(string)$value;
        $email=(bool)preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\\.[a-zA-Z]{2,}/',$str);
        $cpf=(bool)preg_match('/(?<!\\d)\\d{3}[. ]?\\d{3}[. ]?\\d{3}[- ]?\\d{2}(?!\\d)/',$str);
        $phone=(bool)preg_match('/(?<!\\d)(?:\\+55[ -]?)?\\(?\\d{2}\\)?[ -]?9?\\d{4}[ -]?\\d{4}(?!\\d)/',$str);
        if($email||$cpf||$phone)$hit++;
    }
    if($hit)$found[]="$table.$field: $hit possíveis contatos/documentos";
}
foreach($found as $message)echo "[ALERTA] RNF07 $message\n";
echo 'RNF07 campos_verificados='.$fields.' tabelas='.count($tableNames).' valores_verificados='.$checked.' alertas='.count($found).PHP_EOL;
echo "NOTA: triagem estrutural; nomes, anexos, logs e dumps exigem inspeção humana complementar.\n";
exit($found===[]?0:1);
