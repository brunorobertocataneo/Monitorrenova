<?php
declare(strict_types=1);
/**
 * Entrada HTTP autenticada do módulo de competências (MVC).
 * A única escrita permitida é a aprovação de competência estável,
 * protegida por sessão, token CSRF e transação no Repository.
 */
require_once '/var/www/src/Auth.php';
Auth::requireLogin();
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store');
require_once '/var/www/src/CompetenciaRepository.php';
require_once '/var/www/src/CompetenciaController.php';

$erro=null;
$ok=false;
try{
    $controller=new CompetenciaController(
        new CompetenciaRepository(Database::connection())
    );
}catch(Throwable $e){
    error_log('Competencias controller indisponivel: '.$e->getMessage());
    http_response_code(503);
    exit('Competências indisponíveis');
}
$method=$_SERVER['REQUEST_METHOD']??'GET';
if($method==='POST'){
    if(!Auth::verifyCsrf($_POST['csrf']??null)){
        http_response_code(403);
        exit('Confirmação inválida. Recarregue a página.');
    }
    try{
        $competencia=$_POST['competencia']??null;
        if(!is_string($competencia)){
            throw new InvalidArgumentException('Competência inválida');
        }
        $controller->autorizar($competencia);
        header('Location: /competencias.php?resultado=autorizada',true,303);
        exit;
    }catch(InvalidArgumentException|DomainException $e){
        http_response_code(409);
        $erro='Não foi possível autorizar. Verifique se a competência foi detectada duas vezes sem alterações.';
    }catch(Throwable $e){
        error_log('Aprovacao de competencia: '.$e->getMessage());
        http_response_code(503);
        $erro='A autorização está indisponível no momento.';
    }
}elseif($method!=='GET'){
    http_response_code(405);
    exit('Método não permitido');
}
try{
    $dados=$controller->index();
}catch(Throwable $e){
    error_log('Consulta de competencias: '.$e->getMessage());
    http_response_code(503);
    exit('Competências indisponíveis');
}
$ok=($_GET['resultado']??null)==='autorizada';
require '/var/www/src/views/competencias.php';
