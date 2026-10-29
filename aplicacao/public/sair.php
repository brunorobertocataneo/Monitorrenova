<?php
declare(strict_types=1);
require_once '/var/www/src/Auth.php';
Auth::boot();
if($_SERVER['REQUEST_METHOD']!=='POST'||!Auth::verifyCsrf($_POST['csrf']??null)){http_response_code(405);exit('Ação não permitida');}
Auth::signout();
header('Location: /login.php',true,303);exit;
