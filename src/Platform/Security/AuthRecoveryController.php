<?php

declare(strict_types=1);

namespace Koravik\Platform\Security;

use Koravik\Platform\Database\Database;
use RuntimeException;

final class AuthRecoveryController
{
    public function __construct(private readonly Database $database) {}

    public function handle(string $method,string $path): bool
    {
        $service=new AuthRecoveryService($this->database);
        if($method==='GET'&&$path==='/recover'){ $this->render('Recover access',$this->flash().'<section class="auth-card"><p class="eyebrow">Account recovery</p><h1>Reset your password.</h1><p>Enter your email. Koravik gives the same response whether or not an account exists.</p><form method="post" action="/recover"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'"><label>Email<input type="email" name="email" autocomplete="email" required></label><button class="button" type="submit">Send recovery instructions</button></form><p><a href="/login">Return to sign in</a></p></section>');return true; }
        if($method==='POST'&&$path==='/recover'){ $this->csrf();$service->request((string)($_POST['email']??''),$_SERVER['REMOTE_ADDR']??null);$_SESSION['flash']='If that account exists, recovery instructions are now available through the configured delivery channel.';header('Location: /recover',true,303);return true; }
        if($method==='GET'&&$path==='/recover/reset'){ $token=(string)($_GET['token']??'');$valid=$service->inspect($token);$body=$valid?'<section class="auth-card"><p class="eyebrow">Secure recovery</p><h1>Choose a new password.</h1><form method="post" action="/recover/reset"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'"><input type="hidden" name="token" value="'.self::e($token).'"><label>New password<input type="password" name="password" minlength="8" autocomplete="new-password" required></label><button class="button" type="submit">Reset password</button></form></section>':'<section class="auth-card"><h1>This recovery link is unavailable.</h1><p>It may be invalid, expired, or already used.</p><a class="button" href="/recover">Request another link</a></section>';$this->render('Reset password',$body);return true; }
        if($method==='POST'&&$path==='/recover/reset'){ $this->csrf();try{$service->reset((string)($_POST['token']??''),(string)($_POST['password']??''));Security::logout();Security::startSession();$_SESSION['flash']='Your password was reset. Sign in with the new password.';header('Location: /login',true,303);}catch(RuntimeException $e){$_SESSION['flash']=$e->getMessage();header('Location: /recover/reset?token='.rawurlencode((string)($_POST['token']??'')),true,303);}return true; }
        if($method==='GET'&&$path==='/settings/security'){ $account=Security::account();if(!$account)return false;$this->render('Security',$this->flash().'<section class="page-heading"><div><p class="eyebrow">Settings · Security</p><h1>Protect your account.</h1><p>Changing your password invalidates older signed-in sessions.</p></div></section><form class="panel" method="post" action="/settings/security/password"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'"><label>Current password<input type="password" name="current_password" autocomplete="current-password" required></label><label>New password<input type="password" name="new_password" minlength="8" autocomplete="new-password" required></label><button class="button" type="submit">Change password</button></form>');return true; }
        if($method==='POST'&&$path==='/settings/security/password'){ $account=Security::account();if(!$account)return false;$this->csrf();try{$service->changePassword((string)$account['id'],(string)($_POST['current_password']??''),(string)($_POST['new_password']??''));Security::logout();Security::startSession();$_SESSION['flash']='Password changed. Sign in again so older sessions remain invalid.';header('Location: /login',true,303);}catch(RuntimeException $e){$_SESSION['flash']=$e->getMessage();header('Location: /settings/security',true,303);}return true; }
        return false;
    }
    private function csrf():void{if(!Security::verifyCsrf(isset($_POST['csrf'])?(string)$_POST['csrf']:null))throw new RuntimeException('Your session changed. Please try again.');}
    private function flash():string{$f=isset($_SESSION['flash'])?(string)$_SESSION['flash']:'';unset($_SESSION['flash']);return $f!==''?'<div class="notice" role="status">'.self::e($f).'</div>':'';}
    private function render(string $title,string $body):void{echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.self::e($title).' · Koravik</title><link rel="stylesheet" href="/assets/app.css"></head><body><a class="skip-link" href="#main">Skip to content</a><header class="app-header"><a class="brand" href="/hearth">Koravik</a></header><main id="main" class="page" tabindex="-1">'.$body.'</main></body></html>';}
    private static function e(string $v):string{return htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
}
