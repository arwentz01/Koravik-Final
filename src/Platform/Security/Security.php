<?php

declare(strict_types=1);

namespace Koravik\Platform\Security;

use PDO;

final class Security
{
    public static function startSession(): void
    {
        if(session_status()===PHP_SESSION_ACTIVE)return;
        session_name((string)(\env('SESSION_NAME','koravik_session')??'koravik_session'));
        session_set_cookie_params(['httponly'=>true,'secure'=>filter_var(\env('SESSION_SECURE','false'),FILTER_VALIDATE_BOOL),'samesite'=>'Lax','path'=>'/']);
        session_start();
    }
    public static function csrfToken():string{self::startSession();if(!isset($_SESSION['csrf']))$_SESSION['csrf']=bin2hex(random_bytes(32));return(string)$_SESSION['csrf'];}
    public static function verifyCsrf(?string $token):bool{self::startSession();return is_string($token)&&isset($_SESSION['csrf'])&&hash_equals((string)$_SESSION['csrf'],$token);}

    public static function attempt(PDO $pdo,string $email,string $password):bool
    {
        $email=mb_strtolower(trim($email));
        $s=$pdo->prepare('SELECT a.id,a.display_name,a.role,c.password_hash,COALESCE(sec.failed_attempts,0) failed_attempts,sec.locked_until,COALESCE(sec.session_version,1) session_version FROM platform_accounts a JOIN auth_credentials c ON c.account_id=a.id LEFT JOIN auth_security_state sec ON sec.account_id=a.id WHERE a.email=:email AND a.status="active" LIMIT 1');
        $s->execute(['email'=>$email]);$account=$s->fetch();
        if($account && $account['locked_until'] && strtotime((string)$account['locked_until'])>time())return false;
        if(!$account||!password_verify($password,(string)$account['password_hash'])){
            if($account){$attempts=(int)$account['failed_attempts']+1;$lock=$attempts>=5?gmdate('Y-m-d H:i:s',time()+900):null;$pdo->prepare('INSERT INTO auth_security_state (account_id,failed_attempts,locked_until,session_version,last_failed_at,updated_at) VALUES (:account_id,:attempts,:locked_until,1,UTC_TIMESTAMP(),UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE failed_attempts=:attempts_update,locked_until=:locked_until_update,last_failed_at=UTC_TIMESTAMP(),updated_at=UTC_TIMESTAMP()')->execute(['account_id'=>$account['id'],'attempts'=>$attempts,'locked_until'=>$lock,'attempts_update'=>$attempts,'locked_until_update'=>$lock]);}
            return false;
        }
        $pdo->prepare('INSERT INTO auth_security_state (account_id,failed_attempts,locked_until,session_version,last_success_at,updated_at) VALUES (:account_id,0,NULL,1,UTC_TIMESTAMP(),UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE failed_attempts=0,locked_until=NULL,last_success_at=UTC_TIMESTAMP(),updated_at=UTC_TIMESTAMP()')->execute(['account_id'=>$account['id']]);
        self::startSession();session_regenerate_id(true);$_SESSION['account']=['id'=>(string)$account['id'],'display_name'=>(string)$account['display_name'],'role'=>(string)$account['role'],'session_version'=>(int)$account['session_version']];return true;
    }
    public static function account():?array
    {
        self::startSession();$account=isset($_SESSION['account'])&&is_array($_SESSION['account'])?$_SESSION['account']:null;if(!$account)return null;
        try{$s=\database()->pdo()->prepare('SELECT a.status,COALESCE(sec.session_version,1) session_version FROM platform_accounts a LEFT JOIN auth_security_state sec ON sec.account_id=a.id WHERE a.id=:id LIMIT 1');$s->execute(['id'=>$account['id']]);$state=$s->fetch();if(!$state||$state['status']!=='active'||(int)$state['session_version']!==(int)($account['session_version']??1)){self::logout();return null;}}catch(\Throwable){return $account;}
        return $account;
    }
    public static function requireAccount():array{$a=self::account();if($a===null){$_SESSION['intended_path']=self::safeIntendedPath();header('Location: /login',true,302);exit;}return$a;}
    public static function consumeIntendedPath():string{self::startSession();$p=(string)($_SESSION['intended_path']??'/hearth');unset($_SESSION['intended_path']);return str_starts_with($p,'/')&&!str_starts_with($p,'//')?$p:'/hearth';}
    private static function safeIntendedPath():string{$p=parse_url($_SERVER['REQUEST_URI']??'/hearth',PHP_URL_PATH)?:'/hearth';return str_starts_with($p,'/')&&!str_starts_with($p,'//')?$p:'/hearth';}
    public static function logout():void{self::startSession();$_SESSION=[];if(ini_get('session.use_cookies')){$p=session_get_cookie_params();setcookie(session_name(),'',time()-42000,$p['path'],$p['domain']??'',(bool)$p['secure'],(bool)$p['httponly']);}session_destroy();}
}
