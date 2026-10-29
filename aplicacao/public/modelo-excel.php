<?php
declare(strict_types=1);
require_once '/var/www/src/Auth.php';
Auth::requireLogin();
require_once '/var/www/src/ExcelTemplate.php';
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
$temp=tempnam(sys_get_temp_dir(),'modelo-renova-');
if($temp===false){http_response_code(503);exit('Modelo indisponível');}
try{
 ExcelTemplate::create($temp);
 header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
 header('Content-Disposition: attachment; filename="modelo-monitor-renova.xlsx"');
 header('Content-Length: '.filesize($temp));
 readfile($temp);
}finally{unlink($temp);}
