<?php
declare(strict_types=1);
require_once '/var/www/src/Auth.php'; Auth::requireLogin();
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="modelo-monitor-renova.csv"');
header('Cache-Control: no-store');
$out=fopen('php://output','wb');fwrite($out,"\xEF\xBB\xBF");
fputcsv($out,['linha_origem','segurado','vendedor','vencimento','produto','renovado','cobrado','nao_vai_renovar','apolice','status_atual','status_pdf','premio_pdf','fonte_pdf'],';','"','');
fputcsv($out,[2,'CLIENTE EXEMPLO','VENDEDOR EXEMPLO','2026-11-05','AUTOMOVEIS',0,0,0,'','','','',''],';','"','');
fclose($out);
