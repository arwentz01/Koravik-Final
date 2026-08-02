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
        $path=\app_request_path();
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
        $continuity='';
        if($summary['world']){$world=$summary['world'];$continuity.='<article class="return-continuity-card"><p class="eyebrow">Story continuation</p><h2>'.self::e((string)$world['name']).'</h2><p>'.self::e((string)($world['current_chapter']??'Your story')).' · '.self::e((string)($world['current_scene']??'Ready to continue')).'</p><a href="/worlds/'.self::e((string)$world['world_key']).'/play">Continue story</a></article>';}
        if($summary['drafts']){$drafts='';foreach($summary['drafts'] as $draft)$drafts.='<li><a href="/recovery-center">Unfinished '.self::e(str_replace(['_','.'], ' ',(string)$draft['form_key'])).'</a><span>Preserved securely</span></li>';$continuity.='<article class="return-continuity-card"><p class="eyebrow">Preserved safely</p><h2>Unfinished drafts</h2><ul>'.$drafts.'</ul><a href="/recovery-center">Open recovery center</a></article>';}
        if($summary['notifications']){$notices='';foreach($summary['notifications'] as $notice)$notices.='<li><a href="'.self::e((string)($notice['target_url']?:'/notifications')).'">'.self::e((string)$notice['title']).'</a><span>'.self::e((string)$notice['source_module']).'</span></li>';$continuity.='<article class="return-continuity-card"><p class="eyebrow">Meaningful changes</p><h2>Unread notices</h2><ul>'.$notices.'</ul><a href="/notifications">Open notifications</a></article>';}
        $flash=isset($_SESSION['flash'])?'<div class="notice" role="status">'.self::e((string)$_SESSION['flash']).'</div>':'';unset($_SESSION['flash']);
        $homecoming='<section class="panel homecoming-return-experience-upgrade"><h2>Homecoming / Return Experience Upgrade</h2><p>Your return composes recent Moments, Quests, Worlds, Gather, Health-derived signals, drafts, and unread notices into one gentle re-entry. Nothing is auto-completed, auto-dismissed, or marked read.</p><p><a href="/moments">Review Moments</a> · <a href="/source-review">Review source decisions</a></p></section>';
        $body=$flash.'<section class="hero welcome-back returning-user-orientation-upgrade"><p class="eyebrow">Returning User Orientation Upgrade</p><h1>You do not have to catch up.</h1><p>Here is what changed, what may be stale, what is safe to ignore, and one manageable next step. Choose one thread, review older intentions only if useful, or go straight to Hearth.</p><form method="post" action="/return/continue">'.$this->csrfField().'<button class="button" type="submit">Continue to Hearth</button></form></section>'.$homecoming.($continuity?'<section aria-labelledby="return-continuity-title"><div class="section-heading"><h2 id="return-continuity-title">Choose one thread</h2><span>Optional</span></div><div class="return-continuity-grid">'.$continuity.'</div></section>':'').($stale?'<section><div class="section-heading"><h2>Review older intentions</h2><span>'.count($summary['stale']).' to review · optional</span></div><div class="grid">'.$stale.'</div></section>':'<section class="panel"><h2>No stale intentions need review.</h2><p>You can return to Hearth whenever you are ready.</p></section>').$groups.'<form class="return-finish" method="post" action="/return/continue">'.$this->csrfField().'<button class="button" type="submit">Finish and open Hearth</button></form>';
        $this->render('Welcome back',$body);
    }

    private function render(string $title,string $body): void
    {
        echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>'.self::e($title).' · Koravik</title><link rel="stylesheet" href="/assets/app.css"><link rel="stylesheet" href="/assets/return-experience.css"></head><body><a class="skip-link" href="#main">Skip to content</a><header class="app-header"><a class="brand" href="/hearth">Koravik</a><nav aria-label="Primary"><a href="/hearth">Hearth</a><a href="/quests">Quests</a><a href="/world/epic-ordinary">World</a><a href="/chronicle">Chronicle</a></nav></header><main id="main" class="page">'.$body.'</main><footer>Koravik helps you act, then get back to living.</footer></body></html>';
    }

    private function csrf(): void { if(!Security::verifyCsrf(isset($_POST['csrf'])?(string)$_POST['csrf']:null)) throw new RuntimeException('Your session changed. Please try again.'); }
    private function csrfField(): string { return '<input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'">'; }
    private static function e(string $value): string { return htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
}
