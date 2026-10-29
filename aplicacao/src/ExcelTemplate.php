<?php
declare(strict_types=1);
final class ExcelTemplate {
 public static function create(string $path): void {
  if(!class_exists(ZipArchive::class))throw new RuntimeException('Excel indisponível');
  $zip=new ZipArchive();
  if($zip->open($path,ZipArchive::CREATE|ZipArchive::OVERWRITE)!==true)throw new RuntimeException('Não foi possível criar o modelo');
  $headers=['linha_origem','segurado','vendedor','vencimento','produto','renovado','cobrado','nao_vai_renovar','apolice','status_atual','status_pdf','premio_pdf','fonte_pdf'];
  $sample=['2','CLIENTE EXEMPLO','VENDEDOR EXEMPLO','2026-11-05','AUTOMOVEIS','0','0','0','','','','',''];
  $xml='<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetViews><sheetView workbookViewId="0"/></sheetViews><sheetFormatPr defaultRowHeight="15"/><cols><col min="1" max="1" width="15" customWidth="1"/><col min="2" max="3" width="26" customWidth="1"/><col min="4" max="4" width="18" customWidth="1"/><col min="5" max="13" width="19" customWidth="1"/></cols><sheetData>';
  foreach([$headers,$sample] as $ri=>$row){
   $num=$ri+1;$xml.='<row r="'.$num.'">';
   foreach($row as $ci=>$value){
    if($value==='')continue;
    $col=chr(65+$ci);$safe=htmlspecialchars($value,ENT_XML1|ENT_QUOTES,'UTF-8');
    $xml.='<c r="'.$col.$num.'" s="0" t="inlineStr"><is><t>'.$safe.'</t></is></c>';
   }
   $xml.='</row>';
  }
  $xml.='</sheetData></worksheet>';
  $entries=[
   '[Content_Types].xml'=>'<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>',
   '_rels/.rels'=>'<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>',
   'xl/workbook.xml'=>'<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Importacao" sheetId="1" r:id="rId1"/></sheets></workbook>',
   'xl/_rels/workbook.xml.rels'=>'<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>',
   'xl/styles.xml'=>'<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="1"><font><sz val="11"/><name val="Calibri"/></font></fonts><fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills><borders count="1"><border/></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="1"><xf numFmtId="49" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/></cellXfs></styleSheet>',
   'xl/worksheets/sheet1.xml'=>$xml
  ];
  try{
   foreach($entries as $file=>$data){if(!$zip->addFromString($file,$data))throw new RuntimeException('Falha no arquivo Excel');}
  }finally{$zip->close();}
 }
}
