<?php
declare(strict_types=1);
final class WhatsAppStatus {
 public static function lastCheck():array {
  $default=['state'=>'desconhecido','checked_at'=>null,'recent'=>false,'message'=>'Nenhuma verificação local registrada.'];
  $file=__DIR__.'/whatsapp-status.json';
  if(!is_file($file)||!is_readable($file))return $default;
  $data=json_decode((string)file_get_contents($file),true);
  if(!is_array($data)||($data['instance']??null)!=='monitor-renova')return $default;
  $state=$data['state']??'desconhecido';
  if(!is_string($state)||!in_array($state,['open','close','connecting','desconhecido'],true))$state='desconhecido';
  $time=$data['checked_at']??null;
  $epoch=is_string($time)?strtotime($time):false;
  if($epoch===false||$epoch>time()+60)return $default;
  $age=max(0,time()-$epoch);
  return [
   'state'=>$state,
   'checked_at'=>date('d/m/Y H:i:s',$epoch),
   'recent'=>$age<=300,
   'message'=>$age<=300?'Verificação local recente':'Último teste registrado. Estado atual não confirmado.',
   'age_minutes'=>(int)floor($age/60)
  ];
 }
}
