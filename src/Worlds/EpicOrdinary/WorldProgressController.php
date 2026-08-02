<?php

declare(strict_types=1);

namespace Koravik\Worlds\EpicOrdinary;

use Koravik\Platform\Security\Security;
use RuntimeException;

final class WorldProgressController
{
    public function handle(string $method,string $path): bool
    {
        if($method!=='GET') return false;
        if($path!=='/worlds/epic-ordinary/progress'&&!preg_match('#^/worlds/epic-ordinary/reactions/([0-9a-f-]{36})$#',$path,$m)) return false;
        $account=Security::requireAccount();$service=new WorldProgressService(\database());
        try{
            if($path==='/worlds/epic-ordinary/progress'){$this->progress($service->progress((string)$account['id']));return true;}
            $this->reaction($service->reaction((string)$account['id'],$m[1]));return true;
        }catch(RuntimeException $e){http_response_code(404);$this->render('World progress unavailable','<section class="state-panel"><h1>World progress is unavailable.</h1><p>'.self::e($e->getMessage()).'</p><a class="button" href="/worlds">Return to Worlds</a></section>');return true;}
    }

    private function progress(array $s): void
    {
        $objectives='';foreach($s['objectives'] as $o)$objectives.='<article class="surface"><h3>'.self::e($o['title']).'</h3><p>'.self::e($o['description']).'</p><p class="meta">World objective · '.self::e(ucfirst($o['status'])).'</p></article>';
        $reactions='';foreach($s['reactions'] as $r)$reactions.='<article class="surface"><h3>'.self::e($r['title']).'</h3><p>'.self::e($r['explanation']).'</p><a href="/worlds/epic-ordinary/reactions/'.self::e($r['id']).'">Why did this change?</a></article>';
        $history='';foreach($s['history'] as $h)$history.='<li><strong>'.self::e($h['title']).'</strong><span>'.self::e($h['explanation']).'</span><small>'.self::e($h['occurred_at']).'</small></li>';
        $keepsakes='';foreach($s['keepsakes'] as $k)$keepsakes.='<article class="surface"><h3>'.self::e($k['name']).'</h3><p>'.self::e($k['description']).'</p><p class="meta">Fictional keepsake</p></article>';
        $body='<section class="page-heading"><div><p class="eyebrow">Epic Ordinary · World State</p><h1>Your story progress</h1><p>Everything here is fictional World progress. Your real-life Quests and Chronicle remain owned elsewhere.</p></div><a class="button" href="/worlds/epic-ordinary/play">Continue story</a></section><section class="surface"><h2>'.self::e($s['current_chapter']).'</h2><p>Scene: '.self::e($s['current_scene']).' · Caretaker: '.self::e((string)$s['relationship_stage']).' · Trust '.self::e((string)$s['trust_score']).'</p><p class="meta">Package '.self::e($s['package_version']).' · Installation '.self::e($s['status']).'</p></section><section><h2>World objectives</h2><div class="grid">'.($objectives?:'<div class="empty-state">No World objectives yet.</div>').'</div></section><section><h2>Recent reactions</h2><div class="grid">'.($reactions?:'<div class="empty-state">No approved fact has changed this World yet.</div>').'</div></section><section><h2>Keepsakes</h2><div class="grid">'.($keepsakes?:'<div class="empty-state">No fictional keepsakes yet.</div>').'</div></section><section class="surface"><h2>Story history</h2><ul class="memory-list">'.($history?:'<li>No story history yet.</li>').'</ul></section>';
        $this->render('Epic Ordinary progress',$body);
    }

    private function reaction(array $r): void
    {
        $body='<section class="page-heading worlds-reaction-explanation-polish"><div><p class="eyebrow">World reaction explanation</p><h1>'.self::e($r['title']).'</h1></div></section><section class="surface"><h2>What changed?</h2><p>'.self::e($r['message']).'</p><h2>Why?</h2><p>'.self::e($r['explanation']).'</p><h2>Approved fact received</h2><p>'.self::e($r['received']).'</p><h2>Permission and review state</h2><p>'.self::e($r['permission_state']).' '.self::e($r['review_state']).'</p><h2>World rule</h2><p>'.self::e($r['rule_key']?:'Epic Ordinary interpreted an approved minimized fact through its current narrative rules.').'</p><h2>Interpreted</h2><p>'.self::e($r['interpreted_at']).'</p><h2>Deliberately excluded</h2><p>'.self::e($r['excluded']).'</p><div class="local-actions"><a class="button" href="/worlds/epic-ordinary/play">Continue story</a><a href="/worlds/epic-ordinary/progress">Back to progress</a></div></section>';
        $this->render('World reaction',$body);
    }

    private function render(string $title,string $body):void{echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.self::e($title).' · Koravik</title><link rel="stylesheet" href="/assets/app.css"></head><body><main id="main" class="page">'.$body.'</main></body></html>';}
    private static function e(string $v):string{return htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
}
