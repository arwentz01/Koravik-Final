<?php

declare(strict_types=1);

namespace Koravik\Platform\Privacy;

use Koravik\Platform\Database\Database;
use Koravik\Platform\Security\Security;
use RuntimeException;

final class PrivacyController
{
    public function __construct(private readonly Database $database) {}

    public function handle(string $method,string $path): bool
    {
        $account=Security::account();
        if(!$account) return false;
        $accountId=(string)$account['id'];
        $service=new PrivacyService($this->database);
        if($method==='GET' && $path==='/privacy') { $this->privacy($service,$accountId); return true; }
        if($method==='GET' && $path==='/audit') { $this->audit($service,$accountId); return true; }
        if($method==='POST' && preg_match('#^/privacy/([a-f0-9-]{36})/([a-z.]+)/(grant|revoke)$#',$path,$m)) {
            $this->csrf();
            try { $service->setGrant($accountId,$m[1],$m[2],$m[3]==='grant'); $_SESSION['flash']=$m[3]==='grant'?'Permission granted.':'Permission revoked. Future delivery has stopped.'; }
            catch(RuntimeException $e) { $_SESSION['flash']=$e->getMessage(); }
            $this->redirect('/privacy');
        }
        return false;
    }

    private function privacy(PrivacyService $service,string $accountId): void
    {
        $cards='';
        foreach($service->grants($accountId) as $grant) {
            $active=(bool)$grant['granted'];
            $cards.='<article class="consent-card"><div><p class="eyebrow">'.self::e((string)$grant['source']).' → '.self::e((string)$grant['recipient']).'</p><h2>'.self::e((string)$grant['label']).'</h2><p>'.self::e((string)$grant['purpose']).'</p><dl><div><dt>Status</dt><dd>'.($active?'Granted':'Revoked').'</dd></div><div><dt>World status</dt><dd>'.self::e((string)$grant['installation_status']).'</dd></div><div><dt>Last used</dt><dd>'.self::e((string)($grant['last_used_at']??'Never')).'</dd></div><div><dt>Revocation effect</dt><dd>Stops future delivery. Existing source records, World State, reactions, and audit history remain.</dd></div></dl></div><form method="post" action="/privacy/'.self::e((string)$grant['installation_id']).'/'.self::e((string)$grant['fact_key']).'/'.($active?'revoke':'grant').'">'.$this->csrfField().'<button class="button '.($active?'secondary':'').'" type="submit">'.($active?'Revoke permission':'Grant permission').'</button></form></article>';
        }
        $body=$this->flash().'<section class="page-heading"><div><p class="eyebrow">Privacy and consent</p><h1>What may Koravik use?</h1><p>Review each future fact grant, its purpose, recipient, last use, and the effect of revocation.</p></div><a class="button secondary" href="/audit">View audit activity</a></section><div class="consent-list">'.($cards?:'<section class="empty-state"><h2>No active capability grants.</h2></section>').'</div>';
        $this->render('Privacy and consent',$body,'privacy');
    }

    private function audit(PrivacyService $service,string $accountId): void
    {
        $items='';
        foreach($service->audit($accountId) as $row) {
            $items.='<article class="audit-row"><div><p class="eyebrow">'.self::e((string)$row['module']).'</p><h2>'.self::e((string)$row['summary']).'</h2><p class="meta">Actor: your account · '.self::e((string)$row['occurred_at']).' UTC</p></div><details><summary>Technical context</summary><p>Action: <code>'.self::e((string)$row['action']).'</code></p><p>Affected record: '.self::e((string)$row['subject_type']).' / '.self::e((string)$row['subject_id']).'</p><p>This historical record cannot be edited.</p></details></article>';
        }
        $body='<section class="page-heading"><div><p class="eyebrow">Audit activity</p><h1>What consequential actions occurred?</h1><p>A read-only account history of changes, approvals, reversals, and consent decisions.</p></div><a class="button secondary" href="/privacy">Privacy controls</a></section><div class="audit-list">'.($items?:'<section class="empty-state"><h2>No audit activity yet.</h2></section>').'</div>';
        $this->render('Audit activity',$body,'audit');
    }

    private function render(string $title,string $body,string $active): void
    {
        echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>'.self::e($title).' · Koravik</title><link rel="stylesheet" href="/assets/app.css"><link rel="stylesheet" href="/assets/privacy.css"></head><body><a class="skip-link" href="#main">Skip to content</a><header class="app-header"><a class="brand" href="/hearth">Koravik</a><nav aria-label="Primary"><a href="/hearth">Hearth</a><a href="/quests">Quests</a><a href="/chronicle">Chronicle</a><a href="/worlds">Worlds</a><a href="/search">Search</a><a href="/notifications">Notifications</a><a href="/privacy"'.($active==='privacy'?' aria-current="page"':'').'>Privacy</a></nav></header><main id="main" class="page" tabindex="-1">'.$body.'</main><footer>Koravik helps you act, then get back to living.</footer></body></html>';
    }

    private function flash(): string { $value=isset($_SESSION['flash'])?(string)$_SESSION['flash']:'';unset($_SESSION['flash']);return $value!==''?'<div class="notice" role="status">'.self::e($value).'</div>':''; }
    private function csrf(): void { if(!Security::verifyCsrf(isset($_POST['csrf'])?(string)$_POST['csrf']:null)) throw new RuntimeException('Your session changed. Please try again.'); }
    private function csrfField(): string { return '<input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'">'; }
    private function redirect(string $location): never { header('Location: '.$location,true,303);exit; }
    private static function e(string $value): string { return htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
}
