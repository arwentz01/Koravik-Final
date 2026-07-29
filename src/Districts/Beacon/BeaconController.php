<?php

declare(strict_types=1);

namespace Koravik\Districts\Beacon;

use Koravik\Platform\Database\Database;
use Koravik\Platform\Security\Security;
use RuntimeException;

final class BeaconController
{
    public function __construct(private readonly Database $database) {}

    public function handle(string $method,string $path): bool
    {
        if($method==='GET'&&$path==='/beacon'){$this->index();return true;}
        if($method==='POST'&&$path==='/beacon/links'){$this->createLink();return true;}
        if($method==='POST'&&$path==='/beacon/pages'){$this->createPage();return true;}
        if($method==='GET'&&preg_match('#^/b/([a-z0-9]+)$#',$path,$m)){$this->redirectLink($m[1]);return true;}
        if($method==='GET'&&preg_match('#^/p/([a-z0-9]+)$#',$path,$m)){$this->publicPage($m[1]);return true;}
        return false;
    }

    private function index(): void
    {
        $a=Security::requireAccount();$data=(new BeaconService($this->database))->dashboard((string)$a['id']);
        $links='';foreach($data['links'] as $l){$links.='<article class="surface"><h3>'.self::e((string)$l['label']).'</h3><p><a href="/b/'.self::e((string)$l['slug']).'">/b/'.self::e((string)$l['slug']).'</a></p><small>'.(int)$l['visit_count'].' visits</small></article>';}
        $pages='';foreach($data['pages'] as $p){$pages.='<article class="surface"><h3>'.self::e((string)$p['title']).'</h3><p>'.self::e(ucwords(str_replace('_',' ',(string)$p['page_type']))).'</p><a href="/p/'.self::e((string)$p['page_key']).'">Open public page</a></article>';}
        $body='<section class="page-heading"><div><p class="eyebrow">Beacon</p><h1>Share the right thing, cleanly.</h1><p>Short links, QR-ready destinations, link hubs, digital cards, and Wi-Fi sharing live here.</p></div></section><section class="grid"><article class="surface"><h2>Create a short link</h2><form method="post" action="/beacon/links"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'"><label>Label<input name="label" maxlength="180" required></label><label>Destination URL<input name="destination" type="url" required></label><button class="button" type="submit">Create link</button></form></article><article class="surface"><h2>Create a public page</h2><form method="post" action="/beacon/pages"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'"><label>Type<select name="type"><option value="link_hub">Link hub</option><option value="business_card">Digital business card</option><option value="wifi">Wi-Fi card</option></select></label><label>Title<input name="title" required></label><label>Summary<textarea name="summary"></textarea></label><label>Primary value<input name="value" placeholder="URL, contact detail, or Wi-Fi note"></label><button class="button" type="submit">Create page</button></form></article></section><section><h2>Short links</h2><div class="grid">'.($links?:'<article class="empty-state"><h3>No links yet.</h3></article>').'</div></section><section><h2>Public pages</h2><div class="grid">'.($pages?:'<article class="empty-state"><h3>No pages yet.</h3></article>').'</div></section>';
        echo $this->page('Beacon',$body);
    }

    private function createLink(): void
    {
        $this->csrf();$a=Security::requireAccount();try{(new BeaconService($this->database))->createLink((string)$a['id'],(string)($_POST['label']??''),(string)($_POST['destination']??''));$_SESSION['flash']='Short link created.';}catch(RuntimeException $e){$_SESSION['flash']=$e->getMessage();}$this->go('/beacon');
    }

    private function createPage(): void
    {
        $this->csrf();$a=Security::requireAccount();try{(new BeaconService($this->database))->createPage((string)$a['id'],(string)($_POST['type']??''),(string)($_POST['title']??''),(string)($_POST['summary']??''),['value'=>(string)($_POST['value']??'')]);$_SESSION['flash']='Beacon page created.';}catch(RuntimeException $e){$_SESSION['flash']=$e->getMessage();}$this->go('/beacon');
    }

    private function redirectLink(string $slug): void
    {
        $row=(new BeaconService($this->database))->resolve($slug);if(!$row){http_response_code(404);echo $this->page('Link not found','<section class="empty-state"><h1>This Beacon is unavailable.</h1></section>');return;}header('Location: '.(string)$row['destination_url'],true,302);exit;
    }

    private function publicPage(string $key): void
    {
        $p=(new BeaconService($this->database))->page($key);if(!$p){http_response_code(404);echo $this->page('Page not found','<section class="empty-state"><h1>This Beacon page is unavailable.</h1></section>');return;}$payload=$p['payload'];$value=self::e((string)($payload['value']??''));$body='<section class="surface public-beacon"><p class="eyebrow">'.self::e(ucwords(str_replace('_',' ',(string)$p['page_type']))).'</p><h1>'.self::e((string)$p['title']).'</h1><p>'.self::e((string)($p['summary']??'')).'</p>'.($value!==''?'<p class="prominent-value">'.$value.'</p>':'').'</section>';echo $this->page((string)$p['title'],$body);
    }

    private function csrf():void{if(!Security::verifyCsrf((string)($_POST['csrf']??'')))throw new RuntimeException('Your session token expired.');}
    private function go(string $to):never{header('Location: '.$to,true,303);exit;}
    private function page(string $title,string $body):string{$flash=isset($_SESSION['flash'])?'<div class="notice">'.self::e((string)array_shift($_SESSION)).'</div>':'';return '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.self::e($title).' · Koravik</title><link rel="stylesheet" href="/assets/app.css"><link rel="stylesheet" href="/assets/journey.css"></head><body><main id="main" class="page">'.$flash.$body.'</main><footer>Koravik · Beacon</footer></body></html>';}
    private static function e(string $v):string{return htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
}
