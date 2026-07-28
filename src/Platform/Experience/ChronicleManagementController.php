<?php

declare(strict_types=1);
namespace Koravik\Platform\Experience;

use Koravik\Platform\Database\Database;
use Koravik\Platform\Security\Security;
use RuntimeException;

final class ChronicleManagementController
{
    public function __construct(private readonly Database $database) {}
    public function handle(string $method,string $path): bool
    {
        if(!str_starts_with($path,'/chronicle'))return false;$account=Security::account();if(!$account)return false;$a=(string)$account['id'];$s=new ChronicleManagementService($this->database);
        if($method==='GET'&&$path==='/chronicle/new'){$this->form();return true;}
        if($method==='POST'&&$path==='/chronicle/entries'){$this->csrf();try{$id=$s->create($a,$_POST);header('Location: /chronicle/entries/'.$id,true,303);}catch(RuntimeException $e){$_SESSION['flash']=$e->getMessage();header('Location: /chronicle/new',true,303);}return true;}
        if($method==='GET'&&$path==='/chronicle/archived'){$this->list($s->list($a,true),true);return true;}
        if(preg_match('#^/chronicle/entries/([a-f0-9-]{36})$#',$path,$m)){if($method==='GET'){$this->detail($s->get($a,$m[1]));return true;}if($method==='POST'){$this->csrf();try{$s->update($a,$m[1],$_POST);$_SESSION['flash']='Chronicle entry updated.';}catch(RuntimeException $e){$_SESSION['flash']=$e->getMessage();}header('Location: '.$path,true,303);return true;}}
        if($method==='POST'&&preg_match('#^/chronicle/entries/([a-f0-9-]{36})/(archive|restore|delete)$#',$path,$m)){$this->csrf();try{$s->lifecycle($a,$m[1],$m[2]);$_SESSION['flash']='Chronicle entry '.$m[2].'d.';}catch(RuntimeException $e){$_SESSION['flash']=$e->getMessage();}header('Location: /chronicle',true,303);return true;}
        if($method==='GET'&&$path==='/chronicle/manage'){$this->list($s->list($a),false);return true;}
        return false;
    }
    private function form(): void {$this->render('New Chronicle entry',$this->flash().'<section class="page-heading"><div><p class="eyebrow">Chronicle</p><h1>Write something worth keeping.</h1><p>Private by default and owned by Chronicle.</p></div></section><form class="panel" method="post" action="/chronicle/entries"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'"><label>Title<input name="title" maxlength="180" required></label><label>Reflection<textarea name="body" maxlength="8000" required></textarea></label><label>Tags, separated by commas<input name="tags" maxlength="300"></label><button class="button">Save to Chronicle</button></form>');}
    private function list(array $rows,bool $archived): void {$cards='';foreach($rows as $r)$cards.='<article class="chronicle-entry"><p class="eyebrow">'.self::e((string)$r['provenance_label']).'</p><h2>'.self::e((string)$r['title']).'</h2><p>'.self::e(mb_strimwidth((string)$r['body'],0,240,'…')).'</p><a href="/chronicle/entries/'.self::e((string)$r['id']).'">Open entry</a></article>';$this->render('Chronicle',$this->flash().'<section class="page-heading"><div><p class="eyebrow">Chronicle</p><h1>'.($archived?'Archived entries':'Your Chronicle entries').'</h1></div><div><a class="button" href="/chronicle/new">New entry</a> <a href="/chronicle/archived">Archived</a></div></section><div class="chronicle-list">'.($cards?:'<article class="empty-state"><h2>No entries here.</h2></article>').'</div>');}
    private function detail(array $e): void {$tags=implode(', ',(array)$e['tags']);$edit=(bool)$e['editable']?'<form class="panel" method="post" action="/chronicle/entries/'.self::e((string)$e['id']).'"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'"><label>Title<input name="title" maxlength="180" value="'.self::e((string)$e['title']).'" required></label><label>Reflection<textarea name="body" maxlength="8000" required>'.self::e((string)$e['body']).'</textarea></label><label>Tags<input name="tags" value="'.self::e($tags).'"></label><button class="button">Save changes</button></form>':'<section class="panel"><p>'.nl2br(self::e((string)$e['body'])).'</p><p class="meta">This generated historical entry is read-only.</p></section>';$actions='<div class="inline-actions"><form method="post" action="/chronicle/entries/'.self::e((string)$e['id']).'/archive"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'"><button class="quiet-button">Archive</button></form>'.((bool)$e['editable']?'<form method="post" action="/chronicle/entries/'.self::e((string)$e['id']).'/delete"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'"><button class="quiet-button">Delete</button></form>':'').'</div>';$this->render((string)$e['title'],$this->flash().'<section class="page-heading"><div><p class="eyebrow">'.self::e((string)$e['provenance_label']).'</p><h1>'.self::e((string)$e['title']).'</h1><p>Created '.self::e((string)$e['created_at']).' UTC</p></div><a href="/chronicle/manage">All entries</a></section>'.$edit.$actions);}
    private function csrf(): void {if(!Security::verifyCsrf(isset($_POST['csrf'])?(string)$_POST['csrf']:null))throw new RuntimeException('Your session changed. Please try again.');}
    private function flash(): string{$f=isset($_SESSION['flash'])?(string)$_SESSION['flash']:'';unset($_SESSION['flash']);return $f?'<div class="notice" role="status">'.self::e($f).'</div>':'';}
    private function render(string $title,string $body): void {echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.self::e($title).' · Koravik</title><link rel="stylesheet" href="/assets/app.css"></head><body><main class="page">'.$body.'</main></body></html>';}
    private static function e(string $v): string{return htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
}
