<?php

declare(strict_types=1);

namespace Koravik\Worlds;

use Koravik\Platform\Security\Security;
use RuntimeException;

final class WorldLifecycleController
{
    public function handle(string $method,string $path): bool
    {
        if(!str_starts_with($path,'/worlds/installed')&&!preg_match('#^/worlds/([a-z0-9-]+)/manage(?:/(activate|suspend|resume|uninstall-retain|restart|delete-state|update))?$#',$path,$m)) return false;
        $account=Security::requireAccount();$service=new WorldLifecycleService(\database());
        try{
            if($method==='GET'&&$path==='/worlds/installed'){$this->installed($service->installed((string)$account['id']));return true;}
            if($method==='GET'&&isset($m[1])&&!isset($m[2])){$this->manage($service->detail((string)$account['id'],$m[1]));return true;}
            if($method==='POST'&&isset($m[1],$m[2])){$this->csrf();$world=$m[1];match($m[2]){
                'activate','resume'=>$service->activate((string)$account['id'],$world),
                'suspend'=>$service->suspend((string)$account['id'],$world),
                'uninstall-retain'=>$service->retainUninstall((string)$account['id'],$world),
                'restart'=>$service->restart((string)$account['id'],$world,(string)($_POST['confirmation']??'')),
                'delete-state'=>$service->deleteState((string)$account['id'],$world,(string)($_POST['confirmation']??'')),
                'update'=>$service->updatePackage((string)$account['id'],$world),
            };$_SESSION['flash']='World lifecycle updated.';$this->redirect('/worlds/'.$world.'/manage');}
        }catch(RuntimeException $e){http_response_code(422);$this->render('World lifecycle','<section class="state-panel"><h1>That change was not completed.</h1><p>'.self::e($e->getMessage()).'</p><a href="/worlds/installed">Return to installed Worlds</a></section>');return true;}
        return false;
    }

    private function installed(array $worlds):void
    {
        $cards='';foreach($worlds as $w){$chapter=$w['current_chapter']?:'No saved chapter';$cards.='<article class="surface"><p class="eyebrow">'.self::e(ucfirst($w['status'])).'</p><h2>'.self::e($w['name']).'</h2><p>'.self::e($w['tagline']).'</p><p>'.self::e($chapter).($w['current_scene']?' · '.self::e($w['current_scene']):'').'</p><p class="meta">Installed '.self::e($w['installed_version']).' · Available '.self::e($w['available_version']).' · '.self::e((string)$w['permission_count']).' active permissions</p><a class="button" href="/worlds/'.self::e($w['world_key']).'/manage">Manage World</a></article>';}
        $this->render('Installed Worlds','<section class="page-heading"><div><p class="eyebrow">Worlds</p><h1>Installed Worlds</h1><p>Each World keeps independent story progress. Changing the active World does not reset another one.</p></div><a href="/worlds">World catalog</a></section><div class="grid">'.($cards?:'<div class="empty-state">No Worlds are installed.</div>').'</div>');
    }

    private function manage(array $w):void
    {
        $csrf='<input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'">';$update=$w['installed_version']!==$w['available_version']?'<form method="post" action="/worlds/'.self::e($w['world_key']).'/manage/update">'.$csrf.'<button class="button" type="submit">Update package</button></form>':'';
        $body=$this->flash().'<section class="page-heading"><div><p class="eyebrow">World management</p><h1>'.self::e($w['name']).'</h1><p>Status: '.self::e(ucfirst($w['status'])).' · State '.($w['state_retained']?'retained':'deleted').'</p></div><a href="/worlds/installed">Installed Worlds</a></section><section class="surface"><h2>Story position</h2><p>'.self::e($w['current_chapter']?:'No retained chapter').($w['current_scene']?' · '.self::e($w['current_scene']):'').'</p><p class="meta">Installed '.self::e($w['installed_version']).' · Available '.self::e($w['available_version']).'</p><div class="local-actions"><form method="post" action="/worlds/'.self::e($w['world_key']).'/manage/activate">'.$csrf.'<button class="button" type="submit">Activate or resume</button></form><form method="post" action="/worlds/'.self::e($w['world_key']).'/manage/suspend">'.$csrf.'<button class="button secondary" type="submit">Suspend, preserve state</button></form>'.$update.'</div></section><section class="trust-panel"><h2>Uninstall and retain state</h2><p>Removes active access while preserving recoverable chapter, choices, relationships, objectives, reactions, and keepsakes.</p><form method="post" action="/worlds/'.self::e($w['world_key']).'/manage/uninstall-retain">'.$csrf.'<button type="submit">Uninstall, retain state</button></form></section><section class="trust-panel"><h2>Restart this World</h2><p>Resets only this World’s account-specific story progress. Quests, Chronicle, Companion memory, identity, other Worlds, and audit evidence remain untouched.</p><form method="post" action="/worlds/'.self::e($w['world_key']).'/manage/restart">'.$csrf.'<label>Type RESTART WORLD<input name="confirmation" autocomplete="off"></label><button class="button secondary" type="submit">Restart World</button></form></section><section class="trust-panel"><h2>Delete eligible World State</h2><p>Deletes this account’s story progress while preserving the shared catalog/package definition and lifecycle evidence.</p><form method="post" action="/worlds/'.self::e($w['world_key']).'/manage/delete-state">'.$csrf.'<label>Type DELETE WORLD STATE<input name="confirmation" autocomplete="off"></label><button type="submit">Delete World State</button></form></section>';
        $this->render('Manage '.$w['name'],$body);
    }

    private function csrf():void{if(!Security::verifyCsrf((string)($_POST['csrf']??''))) throw new RuntimeException('Your session changed. Please try again.');}
    private function flash():string{$f=$_SESSION['flash']??null;unset($_SESSION['flash']);return $f?'<div class="notice">'.self::e((string)$f).'</div>':'';}
    private function render(string $title,string $body):void{echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.self::e($title).' · Koravik</title><link rel="stylesheet" href="/assets/app.css"></head><body><main id="main" class="page">'.$body.'</main></body></html>';}
    private function redirect(string $to):never{header('Location: '.$to,true,303);exit;}
    private static function e(string $v):string{return htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
}