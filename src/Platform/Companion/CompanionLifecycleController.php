<?php

declare(strict_types=1);
namespace Koravik\Platform\Companion;

use Koravik\Platform\Database\Database;
use Koravik\Platform\Security\Security;
use RuntimeException;

final class CompanionLifecycleController
{
    public function __construct(private readonly Database $database) {}
    public function handle(string $method,string $path): bool
    {
        if(!str_starts_with($path,'/companion')) return false;
        $account=Security::account(); if(!$account) return false; $accountId=(string)$account['id'];
        $service=new CompanionLifecycleService($this->database); $service->expireDue($accountId);
        if($method==='GET' && $path==='/companion/activity'){ $this->activity($accountId); return true; }
        if($method==='POST' && preg_match('#^/companion/proposals/([a-f0-9-]{36})/(clarify|renew)$#',$path,$m)){
            if(!Security::verifyCsrf(isset($_POST['csrf'])?(string)$_POST['csrf']:null)) throw new RuntimeException('Your session changed. Please try again.');
            try { $m[2]==='clarify' ? $service->clarify($accountId,$m[1],(string)($_POST['question']??'')) : $service->renew($accountId,$m[1]); $_SESSION['flash']=$m[2]==='clarify'?'Clarification added.':'Proposal renewed for a fresh review.'; }
            catch(RuntimeException $e){ $_SESSION['flash']=$e->getMessage(); }
            header('Location: /companion/proposals/'.$m[1],true,303); return true;
        }
        return false;
    }
    private function activity(string $accountId): void
    {
        $s=$this->database->pdo()->prepare('SELECT action,subject_id,occurred_at FROM audit_log WHERE account_id=:account_id AND action LIKE "companion.%" ORDER BY occurred_at DESC LIMIT 100');$s->execute(['account_id'=>$accountId]);$rows=$s->fetchAll();$items='';
        foreach($rows as $r)$items.='<li><strong>'.self::e(str_replace('.',' ',(string)$r['action'])).'</strong><span>'.self::e((string)$r['occurred_at']).' UTC</span></li>';
        echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Companion activity · Koravik</title><link rel="stylesheet" href="/assets/app.css"></head><body><main class="page"><section class="page-heading"><div><p class="eyebrow">Companion</p><h1>Proposal activity</h1><p>A read-only record of proposal decisions and outcomes.</p></div><a href="/companion">Back to Companion</a></section><ol class="memory-list">'.($items?:'<li>No Companion activity yet.</li>').'</ol></main></body></html>';
    }
    private static function e(string $v): string{return htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
}
