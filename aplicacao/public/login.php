<?php
declare(strict_types=1);
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
require_once '/var/www/src/Auth.php';
Auth::boot();
$error=null;
try{
 if(!Auth::usersExist()){header('Location: /configurar-acesso.php',true,303);exit;}
 if(Auth::loggedIn()){header('Location: /',true,303);exit;}
 if($_SERVER['REQUEST_METHOD']==='POST'){
  if(!Auth::verifyCsrf($_POST['csrf']??null))throw new RuntimeException('Sessão expirada. Atualize a página.');
  if(!Auth::checkAttempts())throw new RuntimeException('Muitas tentativas. Aguarde 15 minutos.');
  $pass=$_POST['senha']??null;
  $db=Database::connection();$query=$db->prepare('SELECT id,senha_hash FROM usuario WHERE login=? LIMIT 1');
  $query->execute(['admin']);$user=$query->fetch();
  if(!is_string($pass)||!$user||!password_verify($pass,$user['senha_hash']))throw new RuntimeException('Senha incorreta.');
  Auth::signin((int)$user['id']);
  header('Location: /',true,303);exit;
 }
}catch(Throwable $e){$error='Não foi possível entrar. Confira a senha ou aguarde alguns minutos.';Auth::failedAttempt();}
?>
<!doctype html>
<html lang="pt-BR"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#07523c">
<title>Entrar — MonitorRenova</title>
<style>
*{box-sizing:border-box}body{margin:0;font:15px/1.55 system-ui,-apple-system,"Segoe UI",sans-serif;background:#f1f5f8;color:#183047}
.auth-layout{display:grid;grid-template-columns:minmax(360px,1fr) minmax(390px,1fr);min-height:100vh}
.auth-brand{background:linear-gradient(142deg,#0f2741 0%,#102c42 56%,#0b5152 100%);color:#fff;padding:8vh 8vw;display:flex;flex-direction:column;justify-content:space-between}
.identity{display:flex;align-items:center;gap:12px;text-decoration:none;color:white}
.logo{display:grid;place-items:center;background:#16a09a;font-size:20px;font-weight:850;width:48px;height:48px;border-radius:14px}
.identity strong{display:block;font-size:20px;line-height:1.1}.identity small{display:block;font-size:12px;color:#bfd9e3;margin-top:5px}
.auth-brand h1{font-size:clamp(30px,3.5vw,49px);line-height:1.14;max-width:570px;letter-spacing:-1.5px;margin:0 0 18px}
.auth-brand p{font-size:16px;max-width:480px;color:#c5dce7;margin:0}
.brand-note{font-size:12px;color:#c2d9e2}
.auth-main{display:flex;align-items:center;justify-content:center;padding:50px 25px}
.auth-card{width:100%;max-width:440px}
.eyebrow{font-size:11px;color:#168986;letter-spacing:1.5px;text-transform:uppercase;font-weight:850;margin-bottom:10px}
.auth-card h2{font-size:29px;line-height:1.2;letter-spacing:-.7px;margin:0 0 8px}
.muted{color:#667a8b;font-size:13px;margin:0 0 26px}
.form-field{margin-top:18px}
label{display:block;font-size:13px;font-weight:750;color:#334d64;margin:0 0 7px}
input{width:100%;font:inherit;border:1px solid #cbd9e4;border-radius:10px;background:#fff;padding:13px 14px;color:#17334a;min-height:47px}
input:disabled{background:#edf2f6;color:#687e90}
input:focus-visible,button:focus-visible{outline:3px solid #71bbd2;outline-offset:2px}
button{font:inherit;width:100%;background:#118783;color:#fff;border:1px solid #118783;border-radius:10px;padding:13px;margin-top:23px;font-weight:800;cursor:pointer;min-height:48px}
button:hover{background:#0d726f}
.error{background:#fff0f0;color:#9a3733;padding:12px 14px;border:1px solid #f3cece;border-radius:10px;margin-top:14px}
.footnote{color:#718496;font-size:12px;margin-top:21px}
@media(max-width:850px){.auth-layout{grid-template-columns:1fr}.auth-brand{padding:22px 23px 27px;gap:30px}.auth-brand h1{font-size:27px}.auth-brand p{font-size:13px}.brand-note{display:none}.auth-main{padding:35px 20px 55px}.auth-card h2{font-size:25px}}
</style><link rel="stylesheet" href="/assets/login.css?v=1"></head>
<body>
<div class="auth-layout">
  <section class="auth-brand" aria-label="Sobre o MonitorRenova">
    <div class="identity">
      <span class="logo" aria-hidden="true">MR</span>
      <span><strong>MonitorRenova</strong><small>DEMONSTRAÇÃO • dados fictícios</small></span>
    </div>
    <div>
      <h1>Seus vencimentos sob controle.</h1>
      <p>Consulte apólices, acompanhe as competências aprovadas e visualize as rotinas de automação em um painel seguro.</p>
    </div>
    <span class="brand-note">Ambiente local · PHP + MySQL + n8n</span>
  </section>
  <main class="auth-main">
    <div class="auth-card">
      <div class="eyebrow">Acesso administrativo</div>
      <h2>Bem-vindo de volta</h2>
      <p class="muted">Entre com sua senha para acessar os dados fictícios da demonstração acadêmica.</p>
      <?php if($error):?>
        <div class="error" role="alert"><?=htmlspecialchars($error,ENT_QUOTES,'UTF-8')?></div>
      <?php endif;?>
      <form method="post">
        <input type="hidden" name="csrf" value="<?=htmlspecialchars(Auth::csrf(),ENT_QUOTES,'UTF-8')?>">
        <div class="form-field"><label for="login">Usuário</label>
          <input id="login" value="admin" disabled autocomplete="username">
        </div>
        <div class="form-field"><label for="senha">Senha</label>
          <input type="password" name="senha" id="senha" autocomplete="current-password" required autofocus>
        </div>
        <button type="submit">Entrar no painel →</button>
      </form>
      <p class="footnote">A senha é verificada no servidor e não é exibida nem compartilhada.</p>
    </div>
  </main>
</div>
</body></html>
