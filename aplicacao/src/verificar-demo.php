<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require_once __DIR__.'/Database.php';
$db=Database::connection();
foreach(['usuario','seguro_mensal','sheet_sync_registro_mensal','seguro_dashboard'] as $table) {
 $qty=(int)$db->query('SELECT COUNT(*) FROM '.$table)->fetchColumn();
 echo $table.'='.$qty.PHP_EOL;
}
