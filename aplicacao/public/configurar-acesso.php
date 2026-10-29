<?php
declare(strict_types=1);
header('X-Frame-Options: DENY');header('X-Content-Type-Options: nosniff');header('Cache-Control: no-store');
require_once '/var/www/src/Auth.php';
Auth::boot();
$error=null;
try{
 if(Auth::usersExist()){header('Location: /login.php',true,303);exit;}
 if($_SERVER['REQUEST_METHOD']==='POST'){
  if(!Auth::verifyCsrf($_POST['csrf']??null))throw new RuntimeException('Sessão expirada. Atualize a página.');
  if(!Auth::checkAttempts())throw new RuntimeException('Muitas tentativas. Aguarde 15 minutos.');
  $pass=$_POST['senha']??null;$confirm=$_POST['confirmar_senha']??null;
  if(!is_string($pass)||strlen($pass)<12||strlen($pass)>200)throw new RuntimeException('Use uma senha de 12 a 200 caracteres.');
  if(!is_string($confirm)||!hash_equals($pass,$confirm))throw new RuntimeException('As duas senhas precisam coincidir.');
  $db=Database::connection();
  $db->beginTransaction();
  try{
   $db->prepare("INSERT INTO usuario(login,senha_hash) VALUES('admin',?)")->execute([password_hash($pass,PASSWORD_DEFAULT)]);
   $id=(int)$db->lastInsertId();
   $db->commit();
  }catch(Throwable $e){$db->rollBack();throw $e;}
  Auth::signin($id);
  header('Location: /',true,303);exit;
 }
}catch(Throwable $e){$error=$e->getMessage();Auth::failedAttempt();}
?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Primeiro acesso — Monitor Renova</title><style>*{box-sizing:border-box}body{margin:0;background:#f2f5fa;font:16px system-ui,-apple-system,Segoe UI,sans-serif;color:#15283e}.wrap{max-width:470px;margin:8vh auto;padding:0 18px}.card{background:white;border:1px solid #dde5ef;border-radius:14px;padding:30px;box-shadow:0 8px 24px #132b4214}h1{font-size:25px;margin:0 0 9px}.muted{color:#65778d;font-size:14px}label{display:block;margin-top:20px;font-size:14px;font-weight:650}input,button{width:100%;margin-top:7px;padding:12px;border-radius:8px;font:inherit;border:1px solid #c6d4e3}button{margin-top:24px;background:#18568c;color:white;border:0;cursor:pointer}.error{background:#ffe8e8;color:#913030;border-radius:7px;padding:12px;margin-top:16px}a{color:#165ea7}</style></head>
<body><div class="wrap"><div class="card"><h1>Configurar acesso</h1><p class="muted">Crie a senha do administrador diretamente no seu computador. Ela será armazenada somente como hash no MySQL. Não compartilhe sua senha com ninguém.</p>
<?php if($error):?><div class="error"><?=htmlspecialchars($error,ENT_QUOTES,'UTF-8')?></div><?php endif;?>
<form method="post" autocomplete="off"><input type="hidden" name="csrf" value="<?=htmlspecialchars(Auth::csrf(),ENT_QUOTES,'UTF-8')?>">
<label>Usuário</label><input value="admin" disabled>
<label for="senha">Criar senha (mínimo 12 caracteres)</label><input type="password" id="senha" name="senha" minlength="12" maxlength="200" autocomplete="new-password" required>
<label for="confirmar_senha">Repetir senha</label><input type="password" id="confirmar_senha" name="confirmar_senha" minlength="12" maxlength="200" autocomplete="new-password" required>
<button type="submit">Criar acesso e entrar</button></form>
<p class="muted">O sistema está restrito a este computador enquanto a porta permanecer vinculada a 127.0.0.1.</p></div></div></body></html>