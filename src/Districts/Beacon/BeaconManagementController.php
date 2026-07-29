<?php

declare(strict_types=1);

namespace Koravik\Districts\Beacon;

use Koravik\Platform\Database\Database;
use Koravik\Platform\Security\Security;
use RuntimeException;

final class BeaconManagementController
{
    public function __construct(private readonly Database $database) {}
    public function handle(string $method,string $path): bool
    {
        if($method==='GET'&&$path==='/beacon/manage'){$this->index();return true;}
        if($method==='POST'&&$path==='/beacon/domains'){$this->domain();return true;}
        if($method==='POST'&&preg_match('#^/beacon/domains/([a-f0-9-]{36})/verify$#',$path,$m)){$this->verify($m[1]);return true;}
        if($method==='POST'&&preg_match('#^/beacon/domains/([a-f0-9-]{36})/(verified|suspended)$#',$path,$m)){$this->domainState($m[1],$m[2]);return true;}
        if($method==='POST'&&preg_match('#^/beacon/links/([a-f0-9-]{36})$#',$path,$m)){$this->link($m[1]);return true;}
        if($method==='POST'&&preg_match('#^/beacon/links/([a-f0-9-]{36})/(active|paused|archived)$#',$path,$m)){$this->linkState($m[1],$m[2]);return true;}
        return false;
    }
    private function index():void
    {
        $a=Security::requireAccount();$service=new BeaconManagementService($this->database);$domains='';foreach($service->domains() as $d){$domains.='<article class="surface"><h3>'.self::e((string)$d['hostname']).'</h3><p>'.self::e((string)$d['verification_status']).' · root → '.self::e((string)($d['root_redirect_url']??'none')).'</p></article>';}$links='';foreach($service->links((string)$a['id']) as $l){$links.='<article class="surface"><form method="post" action="/beacon/links/'.self::e((string)$l['id']).'"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'"><label>Label<input name="label" value="'.self::e((string)$l['label']).'"></label><label>Slug<input name="slug" value="'.self::e((string)$l['slug']).'"></label><label>Destination<input name="destination" value="'.self::e((string)$l['destination_url']).'"></label><input type="hidden" name="domain_id" value="'.self::e((string)$l['domain_id']).'"><button class="button">Save</button></form><p>Status: '.self::e((string)$l['status']).'</p></article>';}
        $body='<section class="page-heading"><div><p class="eyebrow">Beacon administration</p><h1>Domains and links</h1><p>Manage stable Beacon identities without tying them to one hostname.</p></div></section><section class="surface"><h2>Register domain</h2><form method="post" action="/beacon/domains"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'"><label>Hostname<input name="hostname" required></label><label>Root redirect<input name="root_redirect" type="url"></label><button class="button">Register</button></form></section><section><h2>Domains</h2><div class="grid">'.$domains.'</div></section><section><h2>Links</h2><div class="grid">'.$links.'</div></section>';$this->page('Beacon management',$body);
    }
    private function domain():void{$this->csrf();$a=$this->admin();try{$token=(new BeaconManagementService($this->database))->registerDomain((string)$a['id'],(string)($_POST['hostname']??''),(string)($_POST['root_redirect']??''));$_SESSION['flash']='Domain registered. DNS verification token: '.$token;}catch(RuntimeException $e){$_SESSION['flash']=$e->getMessage();}$this->go('/beacon/manage');}
    private function verify(string $id):void{$this->csrf();$a=$this->admin();try{(new BeaconManagementService($this->database))->verifyDomain((string)$a['id'],$id,(string)($_POST['token']??''));$_SESSION['flash']='Domain verified.';}catch(RuntimeException $e){$_SESSION['flash']=$e->getMessage();}$this->go('/beacon/manage');}
    private function domainState(string $id,string $state):void{$this->csrf();$a=$this->admin();try{(new BeaconManagementService($this->database))->setDomainState((string)$a['id'],$id,$state);}catch(RuntimeException $e){$_SESSION['flash']=$e->getMessage();}$this->go('/beacon/manage');}
    private function link(string $id):void{$this->csrf();$a=Security::requireAccount();try{(new BeaconManagementService($this->database))->updateLink((string)$a['id'],$id,$_POST);$_SESSION['flash']='Beacon link updated.';}catch(RuntimeException $e){$_SESSION['flash']=$e->getMessage();}$this->go('/beacon/manage');}
    private function linkState(string $id,string $state):void{$this->csrf();$a=Security::requireAccount();try{(new BeaconManagementService($this->database))->setLinkState((string)$a['id'],$id,$state);}catch(RuntimeException $e){$_SESSION['flash']=$e->getMessage();}$this->go('/beacon/manage');}
    private function admin():array{$a=Security::requireAccount();if(!in_array((string)($a['role']??''),['owner','admin'],true)){http_response_code(403);throw new RuntimeException('Owner or Admin access is required.');}return $a;}
    private function csrf():void{if(!Security::verifyCsrf((string)($_POST['csrf']??'')))throw new RuntimeException('Your session token expired.');}
    private function go(string $to):never{header('Location: '.$to,true,303);exit;}
    private function page(string $title,string $body):void{$flash='';if(isset($_SESSION['flash'])){$flash='<div class="notice">'.self::e((string)$_SESSION['flash']).'</div>';unset($_SESSION['flash']);}echo '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.self::e($title).' · Koravik</title><link rel="stylesheet" href="/assets/app.css"></head><body><main class="page">'.$flash.$body.'</main></body></html>';}
    private static function e(string $v):string{return htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
}
