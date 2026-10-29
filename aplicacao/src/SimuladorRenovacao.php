<?php
declare(strict_types=1);
require_once __DIR__.'/ValidacaoApolice.php';
/**
 * Triagem conservadora (somente leitura). A aprovação local NUNCA libera envio:
 * dependem de comprovação na fonte, histórico externo e autorização do contato.
 */
final class SimuladorRenovacao {
    public const MOTIVOS = [
      'aguardar_historico'=>'Consulta atual conferida: histórico e autorização ainda pendentes',
      'sem_dados'=>'Segurado, vendedor ou data inválidos',
      'ja_renovado'=>'Renovação já marcada',
      'nao_renova'=>'Não deseja renovar',
      'fora_janela'=>'Fora da janela de vencimentos',
      'duplicado'=>'Duplicado de segurado, vendedor e vencimento',
      'cobrado_conferir'=>'Marcado como cobrado: verificar histórico',
      'sem_produto'=>'Produto não informado',
      'produto_excluido'=>'Produto excluído (vida, consórcio ou acidentes pessoais)',
      'cancelado_recusado'=>'Apólice cancelada ou recusada',
      'status_nao_comprovado'=>'Situação atual sem consulta válida na fonte'
    ];
    public static function janela(DateTimeImmutable $referencia): array {
      $dia=$referencia->setTime(0,0);
      $semana=(int)$dia->format('N');
      if($semana>5)return [];
      $r=[$dia->format('Y-m-d')];
      if($semana===5){
       $r[]=$dia->modify('+1 day')->format('Y-m-d');
       $r[]=$dia->modify('+2 days')->format('Y-m-d');
      }
      return $r;
    }
    public static function avaliar(array $seguros,DateTimeImmutable $referencia):array {
      $janela=self::janela($referencia);
      $contagens=array_fill_keys(array_keys(self::MOTIVOS),0);
      $vistos=[];$linhas=[];
      foreach($seguros as $s){
        $segurado=trim((string)($s['segurado']??''));
        $vendedor=trim((string)($s['vendedor']??''));
        $vencimento=trim((string)($s['vencimento']??''));
        $produto=trim((string)($s['produto']??''));
        $status=self::normalizar($s['status_atual']??'');
        $situacaoValidada=(string)($s['validacao_situacao']??'');
        $dataValidacao=(string)($s['validacao_data']??'');
        $dataVerificada=DateTimeImmutable::createFromFormat('!Y-m-d',$dataValidacao,$referencia->getTimezone());
        $fingerprint=(string)($s['validacao_fingerprint']??'');
        $mesmaOrigem=strlen($fingerprint)===64&&hash_equals($fingerprint,ValidacaoApolice::origemFingerprint($s));
        $validacaoRecente=$mesmaOrigem&&$dataVerificada!==false&&$dataVerificada->format('Y-m-d')===$dataValidacao&&$dataVerificada<=$referencia->setTime(0,0)&&$dataVerificada>=$referencia->setTime(0,0)->modify('-7 days');
        $verificacao=$validacaoRecente?(ValidacaoApolice::SITUACOES[$situacaoValidada]??'Não determinada'):(($s['validacao_id']??null)!==null?'Consulta vencida ou dados alterados':'Sem consulta atual registrada');
        $date=DateTimeImmutable::createFromFormat('!Y-m-d',$vencimento);
        $valid=$date!==false&&$date->format('Y-m-d')===$vencimento;
        if($segurado===''||$vendedor===''||!$valid)$motivo='sem_dados';
        elseif((int)($s['renovado']??0)===1)$motivo='ja_renovado';
        elseif((int)($s['nao_vai_renovar']??0)===1)$motivo='nao_renova';
        elseif(!in_array($vencimento,$janela,true))$motivo='fora_janela';
        else{
          $key=self::normalizar($segurado).'|'.self::normalizar($vendedor).'|'.$vencimento;
          if(isset($vistos[$key]))$motivo='duplicado';
          else{
            $vistos[$key]=true;
            if((int)($s['cobrado']??0)===1)$motivo='cobrado_conferir';
            elseif($produto==='')$motivo='sem_produto';
            elseif(preg_match('/CONSORCIO|VIDA|ACIDENTES PESSOAIS|(^|\W)AP($|\W)/',self::normalizar($produto)))$motivo='produto_excluido';
            elseif(in_array($status,['C','CANCELADO','CANCELADA','R','RECUSADO','RECUSADA'],true)||($validacaoRecente&&in_array($situacaoValidada,['cancelada','recusada'],true)))$motivo='cancelado_recusado';
            elseif(!$validacaoRecente||$situacaoValidada!=='ativa')$motivo='status_nao_comprovado';
            else $motivo='aguardar_historico';
          }
        }
        $contagens[$motivo]++;
        $linhas[]=['id'=>(int)($s['id']??0),'linha_origem'=>(int)($s['linha_origem']??0),'segurado'=>$segurado,'vendedor'=>$vendedor,'vencimento'=>$vencimento,'produto'=>$produto,'status_atual'=>trim((string)($s['status_atual']??'')),'verificacao'=>$verificacao,'motivo_codigo'=>$motivo,'motivo'=>self::MOTIVOS[$motivo]];
      }
      return ['janela'=>$janela,'contagens'=>$contagens,'linhas'=>$linhas,'total'=>count($linhas),'envios'=>0];
    }
    private static function normalizar(mixed $s):string {
      $v=preg_replace('/\s+/u',' ',trim((string)$s))??(string)$s;
      $out=@iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$v);
      return strtoupper($out===false?$v:$out);
    }
}
