<?php
declare(strict_types=1);
final class SpreadsheetReader {
 public static function read(string $filename,string $extension): array {
  if($extension==='csv'){
   $stream=fopen($filename,'rb');if(!$stream)throw new RuntimeException('Falha na leitura do CSV');
   $rows=[];while(($r=fgetcsv($stream,0,';','"',''))!==false){
    if($r===[null])continue;
    $rows[]=$r;if(count($rows)>2001){fclose($stream);throw new RuntimeException('Limite de 2000 seguros por arquivo');}
   }
   fclose($stream);return $rows;
  }
  if($extension!=='xlsx'||!class_exists('ZipArchive'))throw new RuntimeException('Formato XLSX indisponível');
  $zip=new ZipArchive();
  if($zip->open($filename)!==true)throw new RuntimeException('Excel inválido');
  try{
   $xmlSize=0;
   for($i=0;$i<$zip->numFiles;$i++){
    $stat=$zip->statIndex($i);$name=(string)$stat['name'];
    if(str_contains($name,'../')||str_starts_with($name,'/'))throw new RuntimeException('Arquivo Excel inseguro');
    $xmlSize+=(int)$stat['size'];
    if($xmlSize>16000000)throw new RuntimeException('Excel muito grande para processamento seguro');
   }
   $strings=[];
   $shared=$zip->getFromName('xl/sharedStrings.xml');
   if($shared!==false){
    $doc=self::xml($shared);
    foreach($doc->si as $si){
     $parts=$si->xpath('.//*[local-name()="t"]')?:[];
     $strings[]=implode('',array_map(fn($p)=>(string)$p,$parts));
     if(count($strings)>100000)throw new RuntimeException('Tabela de textos excessiva');
    }
   }
   $sheet=$zip->getFromName('xl/worksheets/sheet1.xml');
   if($sheet===false)throw new RuntimeException('Primeira aba não encontrada. Exporte a aba desejada para um novo XLSX.');
   $xml=self::xml($sheet);
   $sheetData=$xml->sheetData;
   if(!$sheetData)throw new RuntimeException('Planilha sem linhas');
   $rows=[];
   foreach($sheetData->row as $row){
    $cells=[];
    foreach($row->c as $cell){
     if(isset($cell->f))throw new RuntimeException('Planilhas com fórmulas não são aceitas; substitua por valores.');
     $ref=(string)$cell['r'];if(!preg_match('/^([A-Z]{1,2})\d+$/D',$ref,$m))throw new RuntimeException('Referência de célula inválida');
     $index=0;foreach(str_split($m[1]) as $letter)$index=$index*26+(ord($letter)-64);$index--;
     if($index>40)continue;
     $type=(string)$cell['t'];
     if($type==='s'){$v=$strings[(int)$cell->v]??null;if($v===null)throw new RuntimeException('Índice de texto inválido');}
     elseif($type==='inlineStr'){$parts=$cell->xpath('.//*[local-name()="t"]')?:[];$v=implode('',array_map(fn($p)=>(string)$p,$parts));}
     elseif($type==='e')throw new RuntimeException('Uma célula contém erro do Excel');
     else{$v=(string)($cell->v??'');}
     $cells[$index]=$v;
    }
    if(!$cells)continue;
    $max=max(array_keys($cells));$result=array_fill(0,$max+1,'');
    foreach($cells as $i=>$v)$result[$i]=$v;
    $rows[]=$result;
    if(count($rows)>2001)throw new RuntimeException('Limite de 2000 seguros por arquivo');
   }
   return $rows;
  }finally{$zip->close();}
 }
 private static function xml(string $data): SimpleXMLElement {
  $old=libxml_use_internal_errors(true);
  try{
   $x=simplexml_load_string($data,SimpleXMLElement::class,LIBXML_NONET|LIBXML_NOERROR|LIBXML_NOWARNING);
   if(!$x)throw new RuntimeException('XML interno do Excel inválido');
   return $x;
  }finally{libxml_clear_errors();libxml_use_internal_errors($old);}
 }
}
