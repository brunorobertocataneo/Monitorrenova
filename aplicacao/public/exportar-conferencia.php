<?php
declare(strict_types=1);
require_once '/var/www/src/Auth.php';
Auth::requireLogin();
require_once '/var/www/src/ConferenciaRepository.php';
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, private');
$mes=$_GET['mes']??null;$filtro=$_GET['filtro']??'todos';
if(!is_string($mes)||!preg_match('/^20\d{2}-(0[1-9]|1[0-2])$/D',$mes)||!is_string($filtro)||!array_key_exists($filtro,ConferenciaRepository::FILTROS)){
 http_response_code(400);exit('Mês ou filtro inválido.');
}
try {
 $repo=new ConferenciaRepository(Database::connection());
 if(!$repo->validarMes($mes)){http_response_code(404);exit('Mês sem dados.');}
 $count=$repo->contar($mes,$filtro);
 if($count>10000){http_response_code(413);exit('Quantidade elevada. Divida a análise.');}
 $rows=$repo->consultar($mes,$filtro,10000);
}catch(Throwable $e){error_log('Exportação de conferência indisponível: '.$e->getCode());http_response_code(503);exit('Exportação indisponível.');}
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="conferencia-'.$mes.'-'.$filtro.'.csv"');
$out=fopen('php://output','wb');
fwrite($out,"\xEF\xBB\xBF");
fputcsv($out,['Linha','Segurado','Vendedor','Vencimento','Produto','Renovado','Cobrado','Não vai renovar','Status atual','Status histórico PDF'],';','"','');
foreach($rows as $r) {
 $values=[
  (string)$r['linha_origem'],(string)$r['segurado'],(string)$r['vendedor'],
  (string)$r['vencimento'],(string)($r['produto']??''),
  (int)$r['renovado']===1?'SIM':'NÃO INFORMADO',
  (int)$r['cobrado']===1?'SIM':'NÃO INFORMADO',
  (int)$r['nao_vai_renovar']===1?'SIM':'NÃO INFORMADO',
  (string)($r['status_atual']??''),(string)($r['status_pdf']??'')
 ];
 foreach($values as &$value) {
  if(preg_match('/^\s*[=+@\-\t\r]/u',$value))$value="'".$value;
 }
 unset($value);
 fputcsv($out,$values,';','"','');
}
fclose($out);
