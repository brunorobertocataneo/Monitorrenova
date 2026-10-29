<?php
declare(strict_types=1);
require_once __DIR__.'/Database.php';
final class Auth {
    public static function boot(): void {
        if (session_status()===PHP_SESSION_ACTIVE) return;
        ini_set('session.use_strict_mode','1');
        ini_set('session.use_only_cookies','1');
        ini_set('session.cookie_httponly','1');
        session_name('MONITOR_RENOVA_SESSION');
        session_set_cookie_params([
            'lifetime'=>0,'path'=>'/','secure'=>!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off',
            'httponly'=>true,'samesite'=>'Strict'
        ]);
        session_start();
    }
    public static function usersExist(): bool {
        return (int)Database::connection()->query('SELECT COUNT(*) FROM usuario')->fetchColumn()>0;
    }
    public static function loggedIn(): bool {
        self::boot();
        if (!isset($_SESSION['usuario_id'])) return false;
        return is_int($_SESSION['usuario_id']) && $_SESSION['usuario_id']>0;
    }
    public static function requireLogin(): void {
        self::boot();
        header('Cache-Control: no-store');
        if (self::loggedIn()) return;
        header('Location: '.(self::usersExist()?'/login.php':'/configurar-acesso.php'),true,303);
        exit;
    }
    public static function csrf(): string {
        self::boot();
        if (empty($_SESSION['csrf_login'])) $_SESSION['csrf_login']=bin2hex(random_bytes(32));
        return $_SESSION['csrf_login'];
    }
    public static function verifyCsrf(mixed $value): bool {
        return is_string($value)&&hash_equals(self::csrf(),$value);
    }
    public static function signin(int $id): void {
        self::boot();
        session_regenerate_id(true);
        $_SESSION['usuario_id']=$id;
        $_SESSION['csrf_login']=bin2hex(random_bytes(32));
        unset($_SESSION['login_attempts']);
    }
    public static function signout(): void {
        self::boot();
        $_SESSION=[];
        if(ini_get('session.use_cookies')) {
            $p=session_get_cookie_params();
            setcookie(session_name(),'',time()-3600,$p['path'],$p['domain'],$p['secure'],$p['httponly']);
        }
        session_destroy();
    }
    public static function checkAttempts(): bool {
        self::boot();
        $a=$_SESSION['login_attempts']??[];
        $a=array_values(array_filter($a,fn($t)=>is_int($t)&&$t>time()-900));
        $_SESSION['login_attempts']=$a;
        return count($a)<5;
    }
    public static function failedAttempt(): void {
        self::boot();
        $_SESSION['login_attempts'][]=time();
    }
}
