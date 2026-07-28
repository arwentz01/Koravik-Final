<?php

declare(strict_types=1);

namespace Koravik\Platform\ReturnExperience;

use Koravik\Platform\Database\Database;
use Koravik\Platform\Security\Security;
use RuntimeException;
use Throwable;

final class ReturnController
{
    public function __construct(private readonly Database $database) {}

    public function handle(): bool
    {
        $account=Security::account();
        if(!$account) return false;
        $method=strtoupper($_SERVER['REQUEST_METHOD']??'GET');
        $path=parse_url($_SERVER['REQUEST_URI']??'/',PHP_URL_PATH)?:'/';
        $service=new ReturnService($this->database);
        if($method==='GET' && $path==='/hearth') {
            $service->observe((string)$account['id']);
            if($service->pending((string)$account['id'])) { header('Location: /return',true,303); return true; }
            return false;
        }
        if($method==='GET' && $path==='/return') { $this->returnPage($service,(string)$account['id']); return true; }
        if($method==='POST' && $path==='/return/continue') { $this->csrf(); $service->acknowledge((string)$account['id']); header('Location: /hearth',true,303); return true; }
        if($method==='POST' && preg_match('#^/return/occurrences/([a-f0-9-]{36})/(resume|skip|dismiss|reschedule)$#',$path,$m)) {
            $this->csrf();
            try { $service->decide((string)$account['id'],$m[1],$m[2],isset($_POST['scheduled_for'])?(string)$_POST['scheduled_for']:null); $_SESSION['flash']='That intention has been updated.'; }
            catch(RuntimeException $e) { $_SESSION['flash']=$e->getMessage(); }
            header('Location: /return',true,303); return true;
        }
        return false;
    }

    private function returnPage(ReturnService $service,string $accountId): void
    {
        $summary=$service->summary($accountId);
        $stale='';
        foreach($summary['stale'] as $item) {
            $id=self::e((string)$item['occurrence_id']);
            $stale.='<article class="card"><div><p class="eyebrow">Does this still matter?</p><h2>'.self::e((string)$item['title']).'</h2><p>Originally scheduled '.self::e((string)$item['scheduled_for']).'.'.(!empty($item['frequency'])?' The repeating schedule will continue.':'').'</p></div><div class="triage-actions"><form method="post" action="/return/occurrences/'.$id.'/resume">'.$this->csrfField().'<button class="button" type="submit">Resume</button></form><form method="post" action="/return/occurrences/'.$id.'/skip">'.$this->csrfField().'<button class="quiet-button" type="submit">Skip this occurrence</button></form><form method="post" action="/return/occurrences/'.$id.'/dismiss">'.$this->csrfField().'<button class="quiet-button" type="submit">Dismiss</button></form><form class="reschedule-form" method="post" action="/return/occurrences/'.$id.'/reschedule">'.$this->csrfField().'<input type="date" name="scheduled_for" required><button class="quiet-button" type="submit">Reschedule</button></form></div></article>';
        }
        $groups='';
        foreach(['relevant'=>'Still relevant','upcoming'=>'Upcoming','completed'=>'Recently completed','archived'=>'Archived'] as $key=>$label) {
            $cards='';
            foreach($summary[$key] as $item) $cards.='<article class="mini-card"><strong>'.self::e((string)$item['title']).'</strong><span>'.self::e((string)($item['scheduled_for']??ucfirst((string)$item['lifecycle_status']))).'</span></article>';
            if($cards!=='') $groups.='<section><h2>'.$label.'</h2><div class="mini-grid">'.$cards.'</div></section>';
        }
        $flash=isset($_SESSION['flash'])?'<div class="notice" role="status">'.self::e((string)$_SESSION['flash']).'</div>':'';unset($_SESSION['flash']);
        $body=$flash.'<section class="hero welcome-back"><p class="eyebrow">Welcome back</p><h1>A few things changed while you were away.</h1><p>Nothing here is a failure. Choose what still belongs in your life and leave the rest behind.</p></section>'.($stale?'<section><div class="section-heading"><h2>Review older intentions</h2><span>'.count($summary['stale']).' to review</span></div><div class="grid">'.$stale.'</div></section>':'<section class="panel"><h2>No stale intentions need review.</h2><p>You can return to Hearth whenever you are ready.</p></section>').$groups.'<form method="post" action="/return/continue">'.$this->csrfField().'<button class="button" type="submit">Continue to Hearth</button></form>';
        $this->render('Welcome back',$body);
    }

    private function render(string $title,string $body): void
    {
        echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>'.self::e($title).' · Koravik</title><link rel="stylesheet" href="/assets/app.css"></head><body><a class="skip-link" href="#main">Skip to content</a><header class="app-header"><a class="brand" href="/hearth">Koravik</a><nav aria-label="Primary"><a href="/hearth">Hearth</a><a href="/quests">Quests</a><a href="/world/epic-ordinary">World</a><a href="/chronicle">Chronicle</a></nav></header><main id="main" class="page">'.$body.'</main><footer>Koravik helps you act, then get back to living.</footer></body></html>';
    }

    private function csrf(): void { if(!Security::verifyCsrf(isset($_POST['csrf'])?(string)$_POST['csrf']:null)) throw new RuntimeException('Your session changed. Please try again.'); }
    private function csrfField(): string { return '<input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'">'; }
    private static function e(string $value): string { return htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
}
