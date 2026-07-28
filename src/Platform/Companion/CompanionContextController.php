<?php

declare(strict_types=1);
namespace Koravik\Platform\Companion;

use Koravik\Platform\Database\Database;
use Koravik\Platform\Security\Security;
use RuntimeException;

final class CompanionContextController
{
    public function __construct(private readonly Database $database) {}
    public function handle(string $method,string $path): bool
    {
        if(!str_starts_with($path,'/companion/context')&&!str_starts_with($path,'/companion/memories')) return false;$account=Security::account();if(!$account)return false;$accountId=(string)$account['id'];$s=new CompanionContextService($this->database);
        if($method==='GET'&&$path==='/companion/context'){ $this->context($s->permissions($accountId));return true; }
        if($method==='POST'&&$path==='/companion/context'){ $this->csrf();$s->savePermissions($accountId,(array)($_POST['allowed']??[]));$_SESSION['flash']='Companion context permissions saved.';header('Location: /companion/context',true,303);return true; }
        if($method==='GET'&&$path==='/companion/memories'){ $this->memories($s->memories($accountId));return true; }
        if($method==='POST'&&$path==='/companion/memories'){ $this->csrf();try{$s->remember($accountId,(string)($_POST['memory_text']??''),(string)($_POST['provenance']??''));$_SESSION['flash']='Memory approved and saved.';}catch(RuntimeException $e){$_SESSION['flash']=$e->getMessage();}header('Location: /companion/memories',true,303);return true; }
        if($method==='POST'&&preg_match('#^/companion/memories/([a-f0-9-]{36})/(active|disabled|deleted)$#',$path,$m)){ $this->csrf();$s->setMemoryStatus($accountId,$m[1],$m[2]);header('Location: /companion/memories',true,303);return true; }
        return false;
    }
    private function context(array $rows): void {$labels=['quest.selected'=>'Selected Quest context','chronicle.selected'=>'Selected Chronicle context','pillars.summary'=>'Pillar summaries','accessibility.preferences'=>'Accessibility preferences','companion.memory'=>'Approved Companion memory'];$checks='';foreach($rows as $r)$checks.='<label class="check-row"><input type="checkbox" name="allowed[]" value="'.self::e((string)$r['context_key']).'"'.((bool)$r['allowed']?' checked':'').'> '.self::e($labels[$r['context_key']]??(string)$r['context_key']).'</label>'; $this->render('Companion context',$this->flash().'<section class="page-heading"><div><p class="eyebrow">Companion permissions</p><h1>What may Companion use?</h1><p>Permissions enable only explicitly selected, minimized context. They do not authorize background scanning.</p></div></section><form class="panel" method="post" action="/companion/context"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'">'.$checks.'<button class="button" type="submit">Save permissions</button></form><p><a href="/companion/memories">Review Companion memories</a></p>');}
    private function memories(array $rows): void {$cards='';foreach($rows as $r)$cards.='<article class="settings-card"><p class="eyebrow">'.self::e((string)$r['status']).'</p><h2>'.self::e((string)$r['memory_text']).'</h2><p>'.self::e((string)$r['provenance']).'</p><div class="inline-actions"><form method="post" action="/companion/memories/'.self::e((string)$r['id']).'/disabled"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'"><button class="quiet-button">Disable</button></form><form method="post" action="/companion/memories/'.self::e((string)$r['id']).'/deleted"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'"><button class="quiet-button">Delete</button></form></div></article>'; $this->render('Companion memories',$this->flash().'<section class="page-heading"><div><p class="eyebrow">Companion memory</p><h1>Remember only what you approve.</h1><p>Memories remain separate from Chronicle, Quests, and World State.</p></div></section><form class="panel" method="post" action="/companion/memories"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'"><label>Memory to approve<input name="memory_text" maxlength="500" required></label><label>Provenance<input name="provenance" maxlength="500" value="Approved directly by the player."></label><button class="button">Approve memory</button></form><div class="grid">'.($cards?:'<article class="empty-state"><h2>No approved memories.</h2></article>').'</div>');}
    private function csrf(): void {if(!Security::verifyCsrf(isset($_POST['csrf'])?(string)$_POST['csrf']:null))throw new RuntimeException('Your session changed. Please try again.');}
    private function flash(): string{$f=isset($_SESSION['flash'])?(string)$_SESSION['flash']:'';unset($_SESSION['flash']);return $f?'<div class="notice" role="status">'.self::e($f).'</div>':'';}
    private function render(string $title,string $body): void {echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.self::e($title).' · Koravik</title><link rel="stylesheet" href="/assets/app.css"></head><body><main class="page">'.$body.'</main></body></html>';}
    private static function e(string $v): string{return htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
}
