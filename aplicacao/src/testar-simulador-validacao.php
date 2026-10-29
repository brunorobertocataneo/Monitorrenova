<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(403);exit(1);}
require_once __DIR__.'/SimuladorRenovacao.php';
$ref=new DateTimeImmutable('2026-10-20',new DateTimeZone('America/Sao_Paulo'));
$base=[
 'id'=>999001,'competencia'=>'2026-10','linha_origem'=>999001,'apolice'=>null,
 'segurado'=>'PESSOA FICTICIA','vendedor'=>'VENDEDOR FICTICIO',
 'vencimento'=>'2026-10-20','produto'=>'AUTOMOVEL','renovado'=>0,'cobrado'=>0,
 'nao_vai_renovar'=>0,'status_atual'=>'A','status_pdf'=>null,
 'validacao_id'=>null,'validacao_situacao'=>null,'validacao_data'=>null,'validacao_fingerprint'=>null
];
$fp=ValidacaoApolice::origemFingerprint($base);
$tests=[
 'importado_ativo_sem_consulta'=>[$base,'status_nao_comprovado'],
 'consulta_ativa_atual'=>[array_replace($base,['validacao_id'=>1,'validacao_situacao'=>'ativa','validacao_data'=>'2026-10-20','validacao_fingerprint'=>$fp]),'aguardar_historico'],
 'consulta_antiga'=>[array_replace($base,['validacao_id'=>2,'validacao_situacao'=>'ativa','validacao_data'=>'2026-10-11','validacao_fingerprint'=>$fp]),'status_nao_comprovado'],
 'identidade_alterada'=>[array_replace($base,['validacao_id'=>3,'validacao_situacao'=>'ativa','validacao_data'=>'2026-10-20','validacao_fingerprint'=>str_repeat('0',64)]),'status_nao_comprovado'],
 'confirmada_cancelada'=>[array_replace($base,['validacao_id'=>4,'validacao_situacao'=>'cancelada','validacao_data'=>'2026-10-20','validacao_fingerprint'=>$fp]),'cancelado_recusado'],
 'confirmacao_indeterminada'=>[array_replace($base,['validacao_id'=>5,'validacao_situacao'=>'indeterminada','validacao_data'=>'2026-10-20','validacao_fingerprint'=>$fp]),'status_nao_comprovado'],
 'ja_renovado'=>[array_replace($base,['renovado'=>1]),'ja_renovado'],
];
foreach($tests as $name=>[$row,$expected]){
 $out=SimuladorRenovacao::avaliar([$row],$ref);
 $found=$out['linhas'][0]['motivo_codigo'];
 if($found!==$expected||$out['envios']!==0)throw new RuntimeException('QA FAILED: '.$name.' actual='.$found);
 echo $name.'=OK'.PHP_EOL;
}
$dup=SimuladorRenovacao::avaliar([$base,array_replace($base,['id'=>999002,'linha_origem'=>999002])],$ref);
if($dup['linhas'][1]['motivo_codigo']!=='duplicado')throw new RuntimeException('DUPLICATE_NOT_DETECTED');
echo 'duplicado=OK'.PHP_EOL;
echo 'NENHUM_ENVIO=SIM'.PHP_EOL;
