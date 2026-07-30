<?php

declare(strict_types=1);

namespace Koravik\Platform\Resilience;

use Koravik\Platform\Database\Database;
use Koravik\Platform\Security\Security;
use RuntimeException;

final class ResilienceController
{
    public function __construct(private readonly Database $database) {}

    public function handle(string $method,string $path): bool
    {
        if(!str_starts_with($path,'/recovery-center')&&!str_starts_with($path,'/settings/sessions')) return false;
        $account=Security::account();if(!$account)return false;$accountId=(string)$account['id'];
        $service=new ResilienceService($this->database);
        if($method==='GET'&&$path==='/recovery-center'){$this->recovery($service,$accountId);return true;}
        if($method==='POST'&&$path==='/recovery-center/drafts'){$this->csrf();$request=(string)($_POST['request_key']??'');if(!$service->claim($accountId,'recovery.draft.save',$request)){$_SESSION['flash']='That submission was already processed.';$this->go('/recovery-center');}$service->saveDraft($accountId,(string)($_POST['form_key']??'recovery.note'),$_POST);$_SESSION['flash']='Draft saved for later.';$this->go('/recovery-center');}
        if($method==='POST'&&preg_match('#^/recovery-center/drafts/([a-f0-9-]{36})/delete$#',$path,$m)){$this->csrf();$service->deleteDraft($accountId,$m[1]);$_SESSION['flash']='Draft removed.';$this->go('/recovery-center');}
        if($method==='GET'&&$path==='/settings/sessions'){$this->sessions($service,$accountId);return true;}
        if($method==='POST'&&$path==='/settings/sessions/revoke-others'){$this->csrf();$count=$service->revokeOthers($accountId,session_id());$_SESSION['flash']=$count.' other session(s) signed out.';$this->go('/settings/sessions');}
        if($method==='POST'&&preg_match('#^/settings/sessions/([a-f0-9-]{36})/revoke$#',$path,$m)){$this->csrf();$service->revokeSession($accountId,$m[1],session_id());$_SESSION['flash']='Session signed out.';$this->go('/settings/sessions');}
        return false;
    }

    private function recovery(ResilienceService $service,string $accountId): void
    {
        $items='';foreach($service->drafts($accountId) as $draft){$summary=implode(', ',array_map('strval',array_slice((array)$draft['payload'],0,3)));$items.='<article class="settings-card"><p class="eyebrow">'.self::e((string)$draft['form_key']).'</p><h2>Saved '.self::e((string)$draft['updated_at']).' UTC</h2><p>'.self::e($summary?:'Empty draft').'</p><form method="post" action="/recovery-center/drafts/'.self::e((string)$draft['id']).'/delete">'.$this->csrfField().'<button class="quiet-button">Remove draft</button></form></article>';}
        $body=$this->flash().'<section class="page-heading"><div><p class="eyebrow">Recovery center</p><h1>Pick up safely after an interruption.</h1><p>Review unfinished work and jump to account, Organization, Household, or delivery recovery without exposing credentials.</p></div></section><section class="grid"><article class="settings-card"><h2>Account access</h2><p>Change your password or review signed-in devices.</p><p><a href="/settings/security">Security</a> · <a href="/settings/sessions">Active sessions</a></p></article><article class="settings-card"><h2>Shared spaces</h2><p>Archived Organizations and Households keep recovery records and ownership history.</p><p><a href="/organizations">Organizations</a> · <a href="/households">Households</a></p></article><article class="settings-card"><h2>Delivery operations</h2><p>Authorized operators can inspect failed and stale Platform Mail deliveries.</p><a href="/system/mail">Platform Mail</a></article></section><section><h2>Unfinished work</h2><div class="grid">'.($items?:'<article class="empty-state"><h3>No saved drafts.</h3><p>Nothing is waiting for recovery.</p></article>').'</div></section><details class="settings-card"><summary>Try draft recovery</summary><form method="post" action="/recovery-center/drafts">'.$this->csrfField().'<input type="hidden" name="request_key" value="'.bin2hex(random_bytes(32)).'"><input type="hidden" name="form_key" value="recovery.note"><label id="note">Recovery note<textarea name="note" maxlength="1000"></textarea></label><button class="button">Save draft</button></form></details>';
        $this->page('Recovery center',$body);
    }

    private function sessions(ResilienceService $service,string $accountId): void
    {
        $cards='';foreach($service->sessions($accountId,session_id()) as $session){$current=(bool)$session['current'];$cards.='<article class="settings-card"><p class="eyebrow">'.($current?'Current session':'Signed-in session').'</p><h2>'.self::e((string)($session['user_agent']?:'Unknown browser')).'</h2><p>Last active '.self::e((string)$session['last_seen_at']).' UTC'.($session['ip_address']?' · '.self::e((string)$session['ip_address']):'').'</p>'.(!$current?'<form method="post" action="/settings/sessions/'.self::e((string)$session['id']).'/revoke">'.$this->csrfField().'<button class="button secondary">Sign out this session</button></form>':'<p class="notice">You are using this session.</p>').'</article>';}
        $body=$this->flash().'<section class="page-heading"><div><p class="eyebrow">Settings · Security</p><h1>Signed-in devices</h1><p>Review recent sessions and revoke access you no longer recognize.</p></div><a href="/settings/security">Password settings</a></section><div class="grid">'.($cards?:'<article class="empty-state"><h2>No tracked sessions yet.</h2></article>').'</div><form method="post" action="/settings/sessions/revoke-others">'.$this->csrfField().'<button class="button secondary">Sign out all other sessions</button></form>';
        $this->page('Signed-in devices',$body);
    }

    private function flash():string{$f=(string)($_SESSION['flash']??'');unset($_SESSION['flash']);return $f!==''?'<div class="notice" role="status">'.self::e($f).'</div>':'';}
    private function csrf():void{if(!Security::verifyCsrf((string)($_POST['csrf']??'')))throw new RuntimeException('Your session changed. Please try again.');}
    private function csrfField():string{return '<input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'">';}
    private function go(string $path):never{header('Location: '.\app_with_base_path($path),true,303);exit;}
    private function page(string $title,string $body):void{echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.self::e($title).' · Koravik</title><link rel="stylesheet" href="/assets/app.css"></head><body><a class="skip-link" href="#main">Skip to content</a><main id="main" class="page" tabindex="-1">'.$body.'</main></body></html>';}
    private static function e(string $value):string{return htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
}
