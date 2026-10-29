<?php
declare(strict_types=1);
require_once '/var/www/src/Auth.php'; Auth::requireLogin();
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
require_once '/var/www/src/Database.php';
$status=filter_input(INPUT_GET,'status',FILTER_UNSAFE_RAW);
if(!is_string($status) || !in_array($status,['iniciada','sucesso','falha','cancelada'],true))$status=null;
try{
 $db=Database::connection();
 $sql='SELECT e.id,a.nome AS automacao,e.status,e.iniciada_em,e.terminada_em,e.total_processados,e.total_elegiveis,e.total_pendentes,e.total_enviados FROM execucao e JOIN automacao a ON a.id=e.automacao_id';
 if($status!==null){$query=$db->prepare($sql.' WHERE e.status=? ORDER BY e.id DESC LIMIT 5000');$query->execute([$status]);}
 else{$query=$db->query($sql.' ORDER BY e.id DESC LIMIT 5000');}
 $rows=$query->fetchAll();
}catch(Throwable $e){error_log($e->getMessage());http_response_code(503);exit('Exportação indisponível');}
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="monitor-renova-execucoes.csv"');
$out=fopen('php://output','wb');
fwrite($out,"\xEF\xBB\xBF");
fputcsv($out,['ID','Automação','Status','Início','Término','Processados','Elegíveis','Pendentes','Enviados'],';');
foreach($rows as $r){
 $values=array_values($r);
 foreach($values as &$v){if(is_string($v)&&preg_match('/^[\x00-\x20]*[=+@-]/u',$v))$v="'".$v;}
 unset($v);
 fputcsv($out,$values,';');
}
fclose($out);
