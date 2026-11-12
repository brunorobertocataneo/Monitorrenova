<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli') exit(1);
require_once __DIR__.'/Database.php';
require_once __DIR__.'/TarefaService.php';
$db=Database::connection();
$mode=$argv[1]??'';
$taskId=isset($argv[2]) && ctype_digit($argv[2])?(int)$argv[2]:0;
$marker='RNF04_PERSISTENCIA_' . ($argv[3]??'');
$repo=new TarefaRepository($db);
$service=new TarefaService($repo);
if($mode==='prepare'){
    $marker='RNF04_PERSISTENCIA_'.bin2hex(random_bytes(5));
    $db->beginTransaction();
    try{
        $q=$db->prepare('INSERT INTO usuario (login,senha_hash) VALUES (?,?)');
        $q->execute([$marker,password_hash(bin2hex(random_bytes(16)),PASSWORD_DEFAULT)]);
        $user=(int)$db->lastInsertId();
        $data=['titulo'=>$marker,'descricao'=>'RNF04 — teste temporario de persistencia','prioridade'=>'media','situacao'=>'pendente','vencimento'=>'','seguro_id'=>''];
        $id=$service->criar($data,$user);
        $data['titulo']=$marker.'_EDITADA';
        $service->atualizar($id,1,$data,$user);
        $service->excluir($id,2,$user);
        if(count($repo->historico($id))!==3){throw new RuntimeException('Historico incompleto');}
        $db->commit();
        echo 'PREPARED_TASK='.$id.PHP_EOL;
        echo 'MARKER='.$marker.PHP_EOL;
    }catch(Throwable $e){$db->rollBack();throw $e;}
    exit(0);
}
if($taskId<=0 || !preg_match('/^RNF04_PERSISTENCIA_[a-f0-9]{10}$/',$marker)){fwrite(STDERR,"Identificador de teste invalido\n");exit(2);}
$q=$db->prepare('SELECT t.*,u.login FROM tarefa_acompanhamento t JOIN usuario u ON u.id=t.criado_por WHERE t.id=?');
$q->execute([$taskId]);
$t=$q->fetch(PDO::FETCH_ASSOC);
if(!$t || $t['login']!==$marker || !str_starts_with($t['titulo'],$marker)) {fwrite(STDERR,"Registro de teste nao localizado\n");exit(3);}
$events=$repo->historico($taskId);
$valid=count($events)===3 && array_column($events,'acao')===['criacao','edicao','arquivamento'] && (int)$t['versao']===3 && $t['excluido_em']!==null;
if($mode==='check'){
    echo ($valid?'PERSISTENCE_OK':'PERSISTENCE_FAILED')." task=$taskId events=".count($events)." version=".$t['versao'].PHP_EOL;
    exit($valid?0:1);
}
if($mode==='cleanup'){
    if(!$valid){fwrite(STDERR,"Nao limpar: evidencia inconsistente\n");exit(4);}
    $db->beginTransaction();
    try{
        $q=$db->prepare('DELETE FROM historico_tarefa_acompanhamento WHERE tarefa_id=?');$q->execute([$taskId]);$n=$q->rowCount();
        $q=$db->prepare('DELETE FROM tarefa_acompanhamento WHERE id=? AND criado_por=?');$q->execute([$taskId,$t['criado_por']]);$m=$q->rowCount();
        $q=$db->prepare('DELETE FROM usuario WHERE id=? AND login=?');$q->execute([$t['criado_por'],$marker]);$u=$q->rowCount();
        if($n!==3 || $m!==1 || $u!==1){throw new RuntimeException('Cleanup nao corresponde aos registros temporarios');}
        $db->commit();echo "CLEANUP_OK events=$n task=$m user=$u\n";
    }catch(Throwable $e){$db->rollBack();throw $e;}
    exit(0);
}
exit(2);
