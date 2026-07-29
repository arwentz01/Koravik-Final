<?php

declare(strict_types=1);

namespace Koravik\Districts\Gather;

use Koravik\Platform\Database\Database;
use Koravik\Platform\Security\Security;
use RuntimeException;

final class GatherController
{
    public function __construct(private readonly Database $database) {}

    public function handle(string $method,string $path): bool
    {
        if($method==='GET'&&$path==='/gather'){$this->index();return true;}
        if($method==='POST'&&$path==='/gather/events'){$this->createEvent();return true;}
        if($method==='GET'&&preg_match('#^/gather/events/([a-f0-9-]{36})$#',$path,$m)){$this->event($m[1]);return true;}
        if($method==='POST'&&preg_match('#^/gather/events/([a-f0-9-]{36})/rsvp$#',$path,$m)){$this->rsvp($m[1]);return true;}
        if($method==='POST'&&preg_match('#^/gather/events/([a-f0-9-]{36})/slots$#',$path,$m)){$this->slot($m[1]);return true;}
        if($method==='POST'&&preg_match('#^/gather/slots/([a-f0-9-]{36})/claim$#',$path,$m)){$this->claim($m[1]);return true;}
        return false;
    }

    private function index(): void
    {
        $a=Security::requireAccount();$events=(new GatherService($this->database))->dashboard((string)$a['id']);$cards='';
        foreach($events as $e){$cards.='<article class="surface"><p class="eyebrow">'.self::e(ucfirst((string)$e['status'])).'</p><h2>'.self::e((string)$e['title']).'</h2><p>'.self::e((string)$e['starts_at']).' UTC</p><p>'.self::e((string)($e['venue']??'')).'</p><a class="button secondary" href="/gather/events/'.self::e((string)$e['id']).'">Plan event</a></article>';}
        $body='<section class="page-heading"><div><p class="eyebrow">Gather</p><h1>Plan the whole event in one place.</h1><p>RSVPs, shifts, potluck items, tasks, and attendance orbit the event—not a generic social feed.</p></div></section><section class="surface"><h2>Create an event</h2><form method="post" action="/gather/events" class="form-grid"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'"><label>Title<input name="title" maxlength="180" required></label><label>Starts<input name="starts_at" type="datetime-local" required></label><label>Ends<input name="ends_at" type="datetime-local"></label><label>Venue<input name="venue"></label><label>Capacity<input name="capacity" type="number" min="1"></label><label>Visibility<select name="visibility"><option value="unlisted">Unlisted</option><option value="private">Private</option><option value="public">Public</option></select></label><label class="full">Description<textarea name="description"></textarea></label><button class="button" type="submit">Create event</button></form></section><section><h2>Your events</h2><div class="grid">'.($cards?:'<article class="empty-state"><h3>No events yet.</h3><p>Create one and Gather will quietly provision its Beacon sharing tools.</p></article>').'</div></section>';
        echo $this->page('Gather',$body);
    }

    private function event(string $id): void
    {
        $a=Security::requireAccount();$e=(new GatherService($this->database))->event((string)$a['id'],$id);if(!$e){http_response_code(404);echo $this->page('Event not found','<section class="empty-state"><h1>Event not found.</h1></section>');return;}
        $r='';foreach($e['rsvps'] as $x){$r.='<li><strong>'.self::e(ucfirst((string)$x['response'])).'</strong> · '.(int)$x['party_size'].' person(s)</li>';}
        $slots='';foreach($e['slots'] as $s){$claims='';foreach($s['commitments'] as $c){$claims.='<li>'.(int)$c['quantity'].' claimed</li>';}$slots.='<article class="surface"><p class="eyebrow">'.self::e(ucfirst((string)$s['slot_type'])).'</p><h3>'.self::e((string)$s['title']).'</h3><p>'.(int)$s['quantity_claimed'].' of '.(int)$s['quantity_needed'].' claimed</p><ul>'.$claims.'</ul><form method="post" action="/gather/slots/'.self::e((string)$s['id']).'/claim"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'"><input name="quantity" type="number" min="1" value="1"><button class="button secondary" type="submit">Claim</button></form></article>';}
        $body='<section class="page-heading"><div><p class="eyebrow">Gather event</p><h1>'.self::e((string)$e['title']).'</h1><p>'.self::e((string)$e['description']).'</p><p><strong>'.self::e((string)$e['starts_at']).' UTC</strong> · '.self::e((string)($e['venue']??'Location pending')).'</p></div><a href="/gather">All events</a></section><section class="grid"><article class="surface"><h2>RSVP</h2><form method="post" action="/gather/events/'.self::e($id).'/rsvp"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'"><label>Response<select name="response"><option value="yes">Yes</option><option value="maybe">Maybe</option><option value="no">No</option></select></label><label>Party size<input name="party_size" type="number" min="1" value="1"></label><label>Note<input name="note"></label><button class="button" type="submit">Save RSVP</button></form><ul>'.($r?:'<li>No responses yet.</li>').'</ul></article><article class="surface"><h2>Add a planning signup</h2><form method="post" action="/gather/events/'.self::e($id).'/slots"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'"><label>Type<select name="type"><option value="shift">Volunteer shift</option><option value="potluck">Potluck item</option><option value="item">Needed item</option><option value="task">Planning task</option></select></label><label>Title<input name="title" required></label><label>Quantity needed<input name="quantity" type="number" min="1" value="1"></label><button class="button" type="submit">Add signup</button></form></article></section><section><h2>Planning board</h2><div class="grid">'.($slots?:'<article class="empty-state"><h3>No signup needs yet.</h3></article>').'</div></section>';
        echo $this->page((string)$e['title'],$body);
    }

    private function createEvent():void{$this->csrf();$a=Security::requireAccount();try{$id=(new GatherService($this->database))->createEvent((string)$a['id'],$_POST);$_SESSION['flash']='Event created with Beacon sharing tools.';$this->go('/gather/events/'.$id);}catch(RuntimeException $e){$_SESSION['flash']=$e->getMessage();$this->go('/gather');}}
    private function rsvp(string $id):void{$this->csrf();$a=Security::requireAccount();try{(new GatherService($this->database))->addRsvp((string)$a['id'],$id,(string)($_POST['response']??''),(int)($_POST['party_size']??1),(string)($_POST['note']??''));$_SESSION['flash']='RSVP saved.';}catch(RuntimeException $e){$_SESSION['flash']=$e->getMessage();}$this->go('/gather/events/'.$id);}
    private function slot(string $id):void{$this->csrf();$a=Security::requireAccount();try{(new GatherService($this->database))->addSlot((string)$a['id'],$id,(string)($_POST['type']??''),(string)($_POST['title']??''),(int)($_POST['quantity']??1));$_SESSION['flash']='Planning signup added.';}catch(RuntimeException $e){$_SESSION['flash']=$e->getMessage();}$this->go('/gather/events/'.$id);}
    private function claim(string $slotId):void{$this->csrf();$a=Security::requireAccount();try{(new GatherService($this->database))->claimSlot((string)$a['id'],$slotId,(int)($_POST['quantity']??1));$_SESSION['flash']='Signup claimed.';}catch(RuntimeException $e){$_SESSION['flash']=$e->getMessage();}$this->go('/gather');}
    private function csrf():void{if(!Security::verifyCsrf((string)($_POST['csrf']??'')))throw new RuntimeException('Your session token expired.');}
    private function go(string $to):never{header('Location: '.$to,true,303);exit;}
    private function page(string $title,string $body):string{$flash=isset($_SESSION['flash'])?'<div class="notice">'.self::e((string)array_shift($_SESSION)).'</div>':'';return '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.self::e($title).' · Koravik</title><link rel="stylesheet" href="/assets/app.css"><link rel="stylesheet" href="/assets/journey.css"></head><body><main id="main" class="page">'.$flash.$body.'</main><footer>Koravik · Gather</footer></body></html>';}
    private static function e(string $v):string{return htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
}
