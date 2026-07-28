<?php

declare(strict_types=1);

namespace Koravik\Platform\Notifications;

use Koravik\Platform\Database\Database;
use Koravik\Platform\Security\Security;
use RuntimeException;

final class NotificationController
{
    public function __construct(private readonly Database $database) {}

    public function handle(string $method,string $path): bool
    {
        $account=Security::account();
        if(!$account) return false;
        $accountId=(string)$account['id'];
        $service=new NotificationService($this->database);
        if($method==='GET' && $path==='/notifications') { $this->index($service,$accountId); return true; }
        if($method==='GET' && $path==='/notifications/preferences') { $this->preferences($service,$accountId); return true; }
        if($method==='POST' && $path==='/notifications/read-all') { $this->csrf(); $service->markAllRead($accountId); $_SESSION['flash']='Notifications marked as read.'; $this->redirect('/notifications'); }
        if($method==='POST' && $path==='/notifications/preferences') { $this->csrf(); $service->savePreferences($accountId,(array)($_POST['categories']??[])); $_SESSION['flash']='Notification preferences saved.'; $this->redirect('/notifications/preferences'); }
        if($method==='POST' && preg_match('#^/notifications/([a-f0-9-]{36})/(read|unread|dismiss)$#',$path,$m)) {
            $this->csrf();
            try { $service->changeState($accountId,$m[1],$m[2]); $_SESSION['flash']='Notification updated.'; }
            catch(RuntimeException $e) { $_SESSION['flash']=$e->getMessage(); }
            $this->redirect('/notifications');
        }
        return false;
    }

    private function index(NotificationService $service,string $accountId): void
    {
        $items=$service->list($accountId);
        $unread='';$read='';
        foreach($items as $item) {
            $id=self::e((string)$item['id']);
            $isUnread=$item['read_at']===null;
            $card='<article class="notification-card'.($isUnread?' unread':'').'"><div><p class="eyebrow">'.self::e((string)$item['source_module']).'</p><h2>'.self::e((string)$item['title']).'</h2><p>'.self::e((string)$item['body']).'</p><details><summary>Why did I receive this?</summary><p>'.self::e((string)$item['reason']).'</p></details><p class="meta">'.self::e((string)$item['created_at']).' UTC</p></div><div class="notification-actions"><a class="button secondary" href="'.self::e((string)$item['target_url']).'">Open source</a><form method="post" action="/notifications/'.$id.'/'.($isUnread?'read':'unread').'">'.$this->csrfField().'<button class="quiet-button" type="submit">Mark '.($isUnread?'read':'unread').'</button></form><form method="post" action="/notifications/'.$id.'/dismiss">'.$this->csrfField().'<button class="quiet-button" type="submit">Dismiss</button></form></div></article>';
            if($isUnread) $unread.=$card; else $read.=$card;
        }
        $flash=$this->flash();
        $body=$flash.'<section class="page-heading"><div><p class="eyebrow">Notifications</p><h1>What changed that deserves attention?</h1><p>Source-owned updates, kept bounded and explainable.</p></div><a class="button secondary" href="/notifications/preferences">Preferences</a></section>';
        if($unread!=='') $body.='<section><div class="section-heading"><h2>Unread</h2><form method="post" action="/notifications/read-all">'.$this->csrfField().'<button class="quiet-button" type="submit">Mark all read</button></form></div><div class="notification-list">'.$unread.'</div></section>';
        if($read!=='') $body.='<section><h2>Earlier</h2><div class="notification-list">'.$read.'</div></section>';
        if($unread==='' && $read==='') $body.='<section class="empty-state"><h2>Nothing needs your attention.</h2><p>Koravik will keep this space quiet unless a registered source has something meaningful to show.</p></section>';
        $this->render('Notifications',$body,$service->unreadCount($accountId));
    }

    private function preferences(NotificationService $service,string $accountId): void
    {
        $values=$service->preferences($accountId);$rows='';
        foreach(NotificationService::CATEGORIES as $category=>$label) $rows.='<label class="preference-row"><span><strong>'.self::e($label).'</strong><small>'.self::e($category==='world.reactions'?'Show a notice when an installed World responds to an approved fact.':'Show a notice when a welcome-back review is prepared after a meaningful absence.').'</small></span><input type="checkbox" name="categories[]" value="'.self::e($category).'"'.(!empty($values[$category])?' checked':'').'></label>';
        $body=$this->flash().'<section class="form-panel"><p class="eyebrow">Notification preferences</p><h1>Choose which changes enter the center.</h1><p>Turning a category off stops future notifications. It does not alter source records, World permissions, or past history.</p><form method="post" action="/notifications/preferences">'.$this->csrfField().'<div class="preference-list">'.$rows.'</div><div class="form-actions"><button class="button" type="submit">Save preferences</button><a class="button secondary" href="/notifications">Back to notifications</a></div></form></section>';
        $this->render('Notification preferences',$body,$service->unreadCount($accountId));
    }

    private function render(string $title,string $body,int $unread): void
    {
        $badge=$unread>0?'<span class="notification-badge" aria-label="'.$unread.' unread notifications">'.($unread>9?'9+':$unread).'</span>':'';
        echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>'.self::e($title).' · Koravik</title><link rel="stylesheet" href="/assets/app.css"></head><body><a class="skip-link" href="#main">Skip to content</a><header class="app-header"><a class="brand" href="/hearth">Koravik</a><nav aria-label="Primary"><a href="/hearth">Hearth</a><a href="/quests">Quests</a><a href="/chronicle">Chronicle</a><a href="/worlds">Worlds</a><a href="/notifications" aria-current="page">Notifications'.$badge.'</a></nav></header><main id="main" class="page">'.$body.'</main><footer>Koravik helps you act, then get back to living.</footer></body></html>';
    }

    private function flash(): string { $value=isset($_SESSION['flash'])?(string)$_SESSION['flash']:'';unset($_SESSION['flash']);return $value!==''?'<div class="notice" role="status">'.self::e($value).'</div>':''; }
    private function csrf(): void { if(!Security::verifyCsrf(isset($_POST['csrf'])?(string)$_POST['csrf']:null)) throw new RuntimeException('Your session changed. Please try again.'); }
    private function csrfField(): string { return '<input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'">'; }
    private function redirect(string $location): never { header('Location: '.$location,true,303);exit; }
    private static function e(string $value): string { return htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
}
