<?php

declare(strict_types=1);
namespace Koravik\Platform\Companion;

use Koravik\Platform\Database\Database;
use Koravik\Platform\Security\Security;
use RuntimeException;

final class CompanionController
{
    public function __construct(private readonly Database $database) {}

    public function handle(string $method,string $path): bool
    {
        if(!str_starts_with($path,'/companion')) return false;
        $account=Security::account();
        if(!$account) return false;
        $service=new CompanionService($this->database);$accountId=(string)$account['id'];
        if($method==='GET' && $path==='/companion'){ $this->index($service->list($accountId)); return true; }
        if($method==='POST' && $path==='/companion/proposals'){
            $this->csrf();
            try{$id=$service->proposeQuest($accountId,(string)($_POST['request_text']??''));$_SESSION['flash']='Proposal created for your review.';header('Location: /companion/proposals/'.$id,true,303);}catch(RuntimeException $e){$_SESSION['flash']=$e->getMessage();header('Location: /companion',true,303);} return true;
        }
        if(preg_match('#^/companion/proposals/([a-f0-9-]{36})$#',$path,$m)){
            if($method==='GET'){ $this->detail($service->get($accountId,$m[1])); return true; }
            if($method==='POST'){ $this->csrf(); try{$service->edit($accountId,$m[1],$_POST);$_SESSION['flash']='Proposal updated. Review the new version before approving.';}catch(RuntimeException $e){$_SESSION['flash']=$e->getMessage();} header('Location: '.$path,true,303); return true; }
        }
        if($method==='POST' && preg_match('#^/companion/proposals/([a-f0-9-]{36})/(approve|dismiss)$#',$path,$m)){
            $this->csrf();try{$service->decide($accountId,$m[1],$m[2],(int)($_POST['version']??0));$_SESSION['flash']=$m[2]==='approve'?'Proposal approved. No Quest has been created yet.':'Proposal dismissed. Nothing was changed.';}catch(RuntimeException $e){$_SESSION['flash']=$e->getMessage();}header('Location: /companion/proposals/'.$m[1],true,303);return true;
        }
        return false;
    }

    private function index(array $rows): void
    {
        $cards='';foreach($rows as $r)$cards.='<article class="proposal-card"><p class="eyebrow">'.self::e(str_replace('_',' ',(string)$r['status'])).'</p><h2>'.self::e((string)$r['title']).'</h2><p>Owner: '.self::e((string)$r['owning_module']).' · Version '.(int)$r['version'].'</p><a href="/companion/proposals/'.self::e((string)$r['id']).'">Review proposal</a></article>';
        $body=$this->flash().'<section class="page-heading"><div><p class="eyebrow">Companion</p><h1>Ask for help without giving up the wheel.</h1><p>Companion may propose. You decide whether anything moves forward.</p></div></section><section class="panel"><h2>Suggest a personal Quest</h2><form method="post" action="/companion/proposals"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'"><label>What feels hard or unclear?<textarea name="request_text" maxlength="1200" required></textarea></label><button class="button" type="submit">Draft a proposal</button></form></section><section><h2>Recent proposals</h2><div class="grid">'.($cards?:'<article class="empty-state"><h3>No proposals yet.</h3><p>A proposal is not a saved Quest.</p></article>').'</div></section>';
        $this->render('Companion',$body);
    }

    private function detail(array $p): void
    {
        $payload=$p['payload'];$editable=in_array($p['status'],['awaiting_approval','draft'],true);
        $actions=$editable?'<form method="post" action="/companion/proposals/'.self::e((string)$p['id']).'"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'"><label>Proposed Quest title<input name="title" maxlength="180" value="'.self::e((string)$payload['title']).'" required></label><label>Proposed notes<textarea name="notes" maxlength="3000">'.self::e((string)$payload['notes']).'</textarea></label><button class="button secondary" type="submit">Save edited proposal</button></form><div class="inline-actions"><form method="post" action="/companion/proposals/'.self::e((string)$p['id']).'/approve"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'"><input type="hidden" name="version" value="'.(int)$p['version'].'"><button class="button" type="submit">Approve this version</button></form><form method="post" action="/companion/proposals/'.self::e((string)$p['id']).'/dismiss"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'"><input type="hidden" name="version" value="'.(int)$p['version'].'"><button class="button secondary" type="submit">Dismiss</button></form></div>':'<p class="notice">This proposal is '.self::e((string)$p['status']).'.</p>';
        $body=$this->flash().'<section class="page-heading"><div><p class="eyebrow">Companion proposal · Version '.(int)$p['version'].'</p><h1>'.self::e((string)$p['title']).'</h1><p><span class="status">Suggestion only</span> This is not a saved Quest.</p></div><a href="/companion">All proposals</a></section><div class="proposal-layout"><section class="panel"><h2>Proposed action</h2><p><strong>Owning District:</strong> '.self::e((string)$p['owning_module']).'</p><p><strong>Expected consequence:</strong> '.self::e((string)$p['consequence']).'</p>'.$actions.'</section><aside class="settings-card"><h2>Why this was suggested</h2><p>'.self::e((string)$p['reasoning']).'</p><h3>Source context</h3><p>'.self::e((string)$p['source_context']).'</p><p class="meta">Approval is specific to version '.(int)$p['version'].'. Editing creates a new version.</p></aside></div>';
        $this->render('Review proposal',$body);
    }

    private function csrf(): void { if(!Security::verifyCsrf(isset($_POST['csrf'])?(string)$_POST['csrf']:null)) throw new RuntimeException('Your session changed. Please try again.'); }
    private function flash(): string{$f=isset($_SESSION['flash'])?(string)$_SESSION['flash']:'';unset($_SESSION['flash']);return $f!==''?'<div class="notice" role="status">'.self::e($f).'</div>':'';}
    private function render(string $title,string $body): void{echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.self::e($title).' · Koravik</title><link rel="stylesheet" href="/assets/app.css"></head><body><a class="skip-link" href="#main">Skip to content</a><header class="app-header"><a class="brand" href="/hearth">Koravik</a><nav aria-label="Primary"><a href="/hearth">Hearth</a><a href="/quests">Quests</a><a href="/chronicle">Chronicle</a><a href="/worlds">Worlds</a><a href="/companion" aria-current="page">Companion</a></nav></header><main id="main" class="page" tabindex="-1">'.$body.'</main><footer>Koravik helps you act, then get back to living.</footer></body></html>';}
    private static function e(string $v): string{return htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
}