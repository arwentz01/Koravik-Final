<?php

declare(strict_types=1);

namespace Koravik\Platform\Media;

use Koravik\Platform\Database\Database;
use Koravik\Platform\Security\Security;
use RuntimeException;

final class MediaController
{
    public function __construct(private readonly Database $database) {}

    public function handle(string $method,string $path): bool
    {
        if($path!=='/media'&&!str_starts_with($path,'/media/attach'))return false;
        $account=Security::account();if(!$account)return false;
        $service=new MediaService($this->database);
        if($method==='POST'&&$path==='/media/attach'){if(!Security::verifyCsrf((string)($_POST['csrf']??'')))throw new RuntimeException('Your session changed. Please try again.');try{$service->link((string)$account['id'],(string)($_POST['media_asset_id']??''),(string)($_POST['owner_module']??''),(string)($_POST['owner_record_id']??''),(string)($_POST['purpose']??''));$_SESSION['flash']='Media attached to source record.';}catch(RuntimeException $e){$_SESSION['flash']=$e->getMessage();}header('Location: '.(string)($_POST['return_to']??'/media'),true,303);return true;}
        if($method==='POST'){if(!Security::verifyCsrf((string)($_POST['csrf']??'')))throw new RuntimeException('Your session changed. Please try again.');try{$service->createReference((string)$account['id'],$_POST);$_SESSION['flash']='Media reference saved.';}catch(RuntimeException $e){$_SESSION['flash']=$e->getMessage();}header('Location: /media',true,303);return true;}
        if($method==='GET'){$this->index($service->list((string)$account['id']));return true;}
        return false;
    }

    private function index(array $rows): void
    {
        $cards='';foreach($rows as $r)$cards.='<article class="surface"><p class="eyebrow">'.self::e((string)$r['owner_module']).' · '.self::e((string)$r['visibility']).'</p><h2>'.self::e((string)$r['original_name']).'</h2><p>'.self::e((string)$r['media_type']).'</p><p class="meta">'.self::e((string)$r['storage_reference']).'</p><p>'.self::e((string)($r['alt_text']??'')).'</p></article>';
        $flash=(string)($_SESSION['flash']??'');unset($_SESSION['flash']);
        $body=($flash?'<div class="notice" role="status">'.self::e($flash).'</div>':'').'<section class="page-heading media-foundation media-attachments-district-records"><div><p class="eyebrow">Platform Media</p><h1>Reference media without stealing ownership.</h1><p>Media stores metadata, visibility, and attachment boundaries. Quests, Chronicle, Gather, Beacon, and Health decide how their own records use attached references.</p></div></section><section class="surface"><h2>Add media reference</h2><form method="post" action="/media" class="form-grid"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'"><label>File or asset name<input name="original_name" maxlength="255" required></label><label>Type<input name="media_type" maxlength="80" placeholder="image/png, application/pdf" required></label><label>Owner<select name="owner_module"><option>Chronicle</option><option>Beacon</option><option>Gather</option><option>Health</option><option>Platform</option></select></label><label>Visibility<select name="visibility"><option value="private">Private</option><option value="unlisted">Unlisted</option><option value="public">Public</option></select></label><label class="full">Storage reference<input name="storage_reference" maxlength="500" required></label><label class="full">Alt text or description<input name="alt_text" maxlength="500"></label><button class="button">Save reference</button></form></section><section class="surface"><h2>Attach to source record</h2><p>Attachments point at a District-owned record. Media does not edit that record or make private content public.</p><form method="post" action="/media/attach" class="form-grid"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'"><input type="hidden" name="return_to" value="/media"><label>Media asset id<input name="media_asset_id" required></label><label>Owner<select name="owner_module"><option>Quests</option><option>Chronicle</option><option>Gather</option><option>Beacon</option><option>Health</option></select></label><label>Source record id<input name="owner_record_id" required></label><label>Purpose<input name="purpose" maxlength="180"></label><button class="button secondary">Attach media reference</button></form></section><section><h2>Your media references</h2><div class="grid">'.($cards?:'<article class="empty-state"><h2>No media references yet.</h2></article>').'</div></section>';
        echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Media · Koravik</title><link rel="stylesheet" href="/assets/app.css"></head><body><main id="main" class="page">'.$body.'</main></body></html>';
    }
    private static function e(string $v): string{return htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
}
