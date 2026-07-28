<?php

declare(strict_types=1);
namespace Koravik\Platform\AccountData;

use Koravik\Platform\Database\Database;
use Koravik\Platform\Security\Security;
use RuntimeException;

final class AccountDataController
{
    public function __construct(private readonly Database $database) {}
    public function handle(string $method,string $path): bool
    {
        if(!str_starts_with($path,'/settings/data'))return false;$account=Security::account();if(!$account)return false;$a=(string)$account['id'];$s=new AccountDataService($this->database);
        if($method==='GET'&&$path==='/settings/data'){$this->index($a);return true;}
        if($method==='POST'&&$path==='/settings/data/exports'){$this->csrf();try{$id=$s->requestExport($a,(string)($_POST['format']??'json'));$_SESSION['flash']='Your export is ready for seven days.';header('Location: /settings/data/exports/'.$id,true,303);}catch(RuntimeException $e){$_SESSION['flash']=$e->getMessage();header('Location: /settings/data',true,303);}return true;}
        if($method==='GET'&&preg_match('#^/settings/data/exports/([a-f0-9-]{36})$#',$path,$m)){$r=$s->export($a,$m[1]);header('Content-Type: application/json; charset=utf-8');header('Content-Disposition: attachment; filename="koravik-export-'.$m[1].'.json"');echo (string)$r['export_json'];return true;}
        if($method==='POST'&&$path==='/settings/data/closure'){$this->csrf();try{$id=$s->requestClosure($a,(string)($_POST['confirmation']??''));$_SESSION['flash']='Account closure requested. You may cancel for seven days.';}catch(RuntimeException $e){$_SESSION['flash']=$e->getMessage();}header('Location: /settings/data',true,303);return true;}
        if($method==='POST'&&preg_match('#^/settings/data/closure/([a-f0-9-]{36})/cancel$#',$path,$m)){$this->csrf();try{$s->cancelClosure($a,$m[1]);$_SESSION['flash']='Account closure cancelled.';}catch(RuntimeException $e){$_SESSION['flash']=$e->getMessage();}header('Location: /settings/data',true,303);return true;}
        return false;
    }
    private function index(string $a): void
    {
        $pdo=$this->database->pdo();$e=$pdo->prepare('SELECT * FROM account_exports WHERE account_id=:a ORDER BY requested_at DESC LIMIT 10');$e->execute(['a'=>$a]);$exports='';foreach($e->fetchAll() as $r)$exports.='<li>'.self::e((string)$r['format']).' · '.self::e((string)$r['status']).($r['status']==='completed'?'<a href="/settings/data/exports/'.self::e((string)$r['id']).'">Download</a>':'').'</li>';$c=$pdo->prepare('SELECT * FROM account_closures WHERE account_id=:a AND status IN ("pending_cancellation","processing") ORDER BY requested_at DESC LIMIT 1');$c->execute(['a'=>$a]);$closure=$c->fetch();$closureHtml=$closure?'<section class="notice"><h2>Closure pending</h2><p>You may cancel until '.self::e((string)$closure['cancellable_until']).' UTC.</p><form method="post" action="/settings/data/closure/'.self::e((string)$closure['id']).'/cancel"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'"><button class="button secondary">Cancel closure</button></form></section>':'<section class="settings-card"><h2>Close account</h2><p>This begins a seven-day cancellation window. After it ends, each module handles its own records and a retention ledger is produced.</p><form method="post" action="/settings/data/closure"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'"><label>Type CLOSE MY ACCOUNT<input name="confirmation" required></label><button class="button secondary">Request account closure</button></form></section>';
        echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Data controls · Koravik</title><link rel="stylesheet" href="/assets/app.css"></head><body><main class="page">'.$this->flash().'<section class="page-heading"><div><p class="eyebrow">Settings · Data controls</p><h1>Export or close your account.</h1><p>Exports exclude credentials and secrets. Closure is staged, cancellable, and owner-specific.</p></div><a href="/settings">Back to Settings</a></section><section class="settings-card"><h2>Account export</h2><form method="post" action="/settings/data/exports"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'"><select name="format"><option value="json">Structured JSON</option><option value="html">Human-readable export manifest</option></select><button class="button">Create export</button></form><ul>'.$exports.'</ul></section>'.$closureHtml.'</main></body></html>';
    }
    private function csrf(): void {if(!Security::verifyCsrf(isset($_POST['csrf'])?(string)$_POST['csrf']:null))throw new RuntimeException('Your session changed. Please try again.');}
    private function flash(): string{$f=isset($_SESSION['flash'])?(string)$_SESSION['flash']:'';unset($_SESSION['flash']);return $f?'<div class="notice" role="status">'.self::e($f).'</div>':'';}
    private static function e(string $v): string{return htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
}
