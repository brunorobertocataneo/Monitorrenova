<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit(1);
require_once __DIR__.'/Database.php';
require_once __DIR__.'/TarefaService.php';
$db=Database::connection();
$r=new TarefaRepository($db); $s=new TarefaService($r);
$n=0;
function ok(bool $v,string $m): void {global $n; if(!$v) throw new RuntimeException($m); echo "OK $m\n"; $n++;}
$db->beginTransaction();
try {
 $u=(int)$db->query('SELECT id FROM usuario LIMIT 1')->fetchColumn();
 if(!$u){$q=$db->prepare('INSERT INTO usuario (login,senha_hash) VALUES (?,?)'); $q->execute(['teste_gestao_'.bin2hex(random_bytes(4)),password_hash(bin2hex(random_bytes(20)),PASSWORD_DEFAULT)]);$u=(int)$db->lastInsertId();}
 $v=$r->vendedores()[0];
 $today=new DateTimeImmutable('today',new DateTimeZone('America/Sao_Paulo'));
 $base=['titulo'=>'Teste gestao '.bin2hex(random_bytes(4)),'descricao'=>'','prioridade'=>'alta','situacao'=>'pendente','vencimento'=>$today->modify('-1 day')->format('Y-m-d'),'seguro_id'=>'','categoria'=>'comercial','responsavel'=>$v,'recorrencia'=>'semanal'];
 $antes=$r->indicadores();
 $id=$s->criar($base,$u); $t=$r->buscar($id);
 ok($t['categoria']==='comercial' && $t['responsavel']===$v && $t['recorrencia']==='semanal','campos persistidos');
 ok((int)$r->indicadores()['atrasadas']===(int)$antes['atrasadas']+1,'indicador atrasadas');
 $done=array_replace($base,['situacao'=>'concluida']);
 $s->atualizar($id,1,$done,$u);
 $lista=$r->listar('', $base['titulo'])['registros'];
 ok(count($lista)===2,'proxima tarefa criada');
 $next=array_values(array_filter($lista,fn($x)=>(int)$x['id']!==$id))[0];
 ok($next['situacao']==='pendente' && $next['vencimento']>$today->format('Y-m-d') && $next['responsavel']===$v,'proxima tarefa pendente e futura');
 ok((int)$r->indicadores()['atrasadas']===(int)$antes['atrasadas'],'concluida nao atrasada');
 $s->atualizar($id,2,$base,$u);$s->atualizar($id,3,$done,$u);
 ok(count($r->listar('', $base['titulo'])['registros'])===2,'sem duplicacao ao reabrir');
 ok(count($r->historico($id))===4,'historico preservado');
 foreach([['categoria'=>'invalida'],['responsavel'=>'nao existe'],['recorrencia'=>'invalida'],['vencimento'=>'']] as $invalid){try{$s->validar(array_replace($base,$invalid));throw new RuntimeException('validacao ausente');}catch(InvalidArgumentException $e){ok(true,'entrada invalida rejeitada');}}
} finally {$db->rollBack();}
echo "GESTAO: $n aprovados\n";
