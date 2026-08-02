<?php

declare(strict_types=1);

namespace Koravik\Districts\Gather;

use Koravik\Platform\Database\Database;
use Koravik\Platform\Security\Security;
use RuntimeException;

final class GatherLifecycleController
{
    public function __construct(private readonly Database $database) {}

    public function handle(string $method,string $path): bool
    {
        if($method==='GET'&&preg_match('#^/gather/events/([a-f0-9-]{36})/agenda$#',$path,$m)){$this->agenda($m[1]);return true;}
        if($method==='POST'&&preg_match('#^/gather/events/([a-f0-9-]{36})/agenda$#',$path,$m)){$this->addAgenda($m[1]);return true;}
        if($method==='POST'&&preg_match('#^/gather/agenda/([a-f0-9-]{36})/favorite$#',$path,$m)){$this->favorite($m[1]);return true;}
        if($method==='GET'&&preg_match('#^/gather/events/([a-f0-9-]{36})/day-of$#',$path,$m)){$this->dayOf($m[1]);return true;}
        if($method==='POST'&&preg_match('#^/gather/events/([a-f0-9-]{36})/walk-ins$#',$path,$m)){$this->walkin($m[1]);return true;}
        if($method==='POST'&&preg_match('#^/gather/events/([a-f0-9-]{36})/closeout$#',$path,$m)){$this->closeout($m[1]);return true;}
        if($method==='GET'&&preg_match('#^/gather/events/([a-f0-9-]{36})/reflect$#',$path,$m)){$this->reflect($m[1]);return true;}
        if($method==='POST'&&preg_match('#^/gather/events/([a-f0-9-]{36})/outcomes$#',$path,$m)){$this->outcome($m[1]);return true;}
        if($method==='POST'&&preg_match('#^/gather/outcomes/([a-f0-9-]{36})/approve$#',$path,$m)){$this->approve($m[1]);return true;}
        return false;
    }

    private function agenda(string $eventId): void
    {
        $event=$this->eventVisible($eventId);if(!$event){$this->notFound();return;}$account=Security::account();$owner=$account&&$account['id']===$event['account_id'];$items='';foreach((new GatherLifecycleService($this->database))->agenda($eventId) as $item){$favorite='<form method="post" action="/gather/agenda/'.self::e((string)$item['id']).'/favorite"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'"><label>Email for reminder<input type="email" name="email"></label><label>Remind me<select name="minutes"><option value="0">Favorite only</option><option value="15">15 minutes before</option><option value="60">1 hour before</option><option value="1440">1 day before</option></select></label><button class="button secondary" type="submit">Save to my plan</button></form>';$items.='<article class="surface"><p class="eyebrow">'.self::e((string)$item['starts_at']).' UTC</p><h2>'.self::e((string)$item['title']).'</h2><p>'.self::e((string)($item['description']??'')).'</p><p>'.self::e((string)($item['location_label']??'')).'</p>'.$favorite.'</article>';}
        $ownerForm=$owner?'<section class="surface"><h2>Add agenda item</h2><form method="post" action="/gather/events/'.self::e($eventId).'/agenda" class="form-grid"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'"><label>Title<input name="title" required></label><label>Starts<input type="datetime-local" name="starts_at" required></label><label>Ends<input type="datetime-local" name="ends_at"></label><label>Location<input name="location"></label><label class="full">Description<textarea name="description"></textarea></label><button class="button" type="submit">Add item</button></form></section>':'';
        $this->page((string)$event['title'].' agenda','<section class="page-heading gather-agenda-presence"><div><p class="eyebrow">Event agenda · Personal Plan Layer</p><h1>'.self::e((string)$event['title']).'</h1><p>Build the shared agenda, let participants favorite items, and choose only the reminders they want. Agenda reminders remain event-management messages, not marketing.</p></div><a href="/gather/events/'.self::e($eventId).'">Event details</a></section>'.$ownerForm.'<section class="grid">'.($items?:'<article class="empty-state"><h2>No agenda items yet.</h2></article>').'</section>');
    }

    private function dayOf(string $eventId): void
    {
        $a=Security::requireAccount();$q=trim((string)($_GET['q']??''));try{$rows=$q!==''?(new GatherLifecycleService($this->database))->attendeeSearch((string)$a['id'],$eventId,$q):[];}catch(RuntimeException){$this->notFound();return;}$results='';foreach($rows as $r)$results.='<article class="surface"><h3>'.self::e((string)($r['guest_name']?:'Guest')).'</h3><p>'.self::e((string)$r['guest_email']).' · '.self::e((string)$r['response']).' · '.(int)$r['party_size'].' person(s)</p></article>';
        $body='<section class="page-heading gather-day-of-operations-layer"><div><p class="eyebrow">Day-of operations · Front Desk Layer</p><h1>Find, check in, and add walk-ins.</h1><p>Optimized for front-desk and mobile use. QR scanning can use Beacon handoffs when deployed over HTTPS; manual lookup remains the fallback and source of truth stays Gather.</p></div></section><section class="surface"><form method="get"><label>Search attendees<input name="q" value="'.self::e($q).'" autofocus></label><button class="button" type="submit">Search</button></form></section><section class="grid">'.$results.'</section><section class="surface"><h2>Add walk-in</h2><form method="post" action="/gather/events/'.self::e($eventId).'/walk-ins" class="form-grid"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'"><label>Name<input name="name" required></label><label>Email<input type="email" name="email"></label><label>Party count<input type="number" name="party_count" min="1" value="1"></label><label>Note<input name="note"></label><button class="button" type="submit">Check in walk-in</button></form></section>';$this->page('Day-of event operations',$body);
    }

    private function reflect(string $eventId): void
    {
        $a=Security::requireAccount();$body='<section class="page-heading gather-aftercare-proposal-layer"><div><p class="eyebrow">Event reflection · Aftercare Proposal Layer</p><h1>What did this experience mean?</h1><p>Nothing crosses into Chronicle, Quests, Journey, Worlds, Moments, or Source Review until you review and approve it. Attendance alone never becomes memory.</p></div></section><section class="surface"><form method="post" action="/gather/events/'.self::e($eventId).'/outcomes"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'"><label>Where might this belong?<select name="type"><option value="chronicle_reflection">Chronicle reflection</option><option value="quest_progress">Quest progress</option><option value="journey_invitation">Journey invitation</option><option value="world_fact">Minimized World fact</option></select></label><label>Reflection<textarea name="summary" required></textarea></label><button class="button" type="submit">Create reviewable proposal</button></form></section>';$this->page('Reflect after event',$body);
    }

    private function addAgenda(string $id):void{$this->csrf();$a=Security::requireAccount();try{(new GatherLifecycleService($this->database))->addAgenda((string)$a['id'],$id,$_POST);$_SESSION['flash']='Agenda item added.';}catch(RuntimeException $e){$_SESSION['flash']=$e->getMessage();}$this->go('/gather/events/'.$id.'/agenda');}
    private function favorite(string $id):void{$this->csrf();$a=Security::account();try{(new GatherLifecycleService($this->database))->favorite($id,$a['id']??null,(string)($_POST['email']??''),(int)($_POST['minutes']??0));$_SESSION['flash']='Saved to your event plan.';}catch(RuntimeException $e){$_SESSION['flash']=$e->getMessage();}$this->go((string)($_SERVER['HTTP_REFERER']??'/gather'));}
    private function walkin(string $id):void{$this->csrf();$a=Security::requireAccount();try{(new GatherLifecycleService($this->database))->addWalkin((string)$a['id'],$id,$_POST);$_SESSION['flash']='Walk-in checked in.';}catch(RuntimeException $e){$_SESSION['flash']=$e->getMessage();}$this->go('/gather/events/'.$id.'/day-of');}
    private function closeout(string $id):void{$this->csrf();$a=Security::requireAccount();try{(new GatherLifecycleService($this->database))->closeEvent((string)$a['id'],$id,(string)($_POST['status']??''),(string)($_POST['note']??''));$_SESSION['flash']='Event closeout saved.';}catch(RuntimeException $e){$_SESSION['flash']=$e->getMessage();}$this->go('/gather/events/'.$id.'/command');}
    private function outcome(string $id):void{$this->csrf();$a=Security::requireAccount();try{$proposal=(new GatherLifecycleService($this->database))->proposeOutcome((string)$a['id'],$id,(string)($_POST['type']??''),(string)($_POST['summary']??''));$_SESSION['flash']='Proposal created. Review and approve before it is applied.';$this->go('/gather/events/'.$id.'/reflect?proposal='.$proposal);}catch(RuntimeException $e){$_SESSION['flash']=$e->getMessage();$this->go('/gather/events/'.$id.'/reflect');}}
    private function approve(string $id):void{$this->csrf();$a=Security::requireAccount();try{(new GatherLifecycleService($this->database))->approveOutcome((string)$a['id'],$id);$_SESSION['flash']='Outcome approved for its destination workflow.';}catch(RuntimeException $e){$_SESSION['flash']=$e->getMessage();}$this->go('/gather');}

    private function eventVisible(string $id):?array{$a=Security::account();return $a?(new GatherService($this->database))->event((string)$a['id'],$id)??(new GatherService($this->database))->publicEvent($id):(new GatherService($this->database))->publicEvent($id);}
    private function csrf():void{if(!Security::verifyCsrf((string)($_POST['csrf']??'')))throw new RuntimeException('Your session token expired.');}
    private function go(string $to):never{header('Location: '.$to,true,303);exit;}
    private function notFound():void{http_response_code(404);$this->page('Not found','<section class="empty-state"><h1>That event experience is unavailable.</h1></section>');}
    private function page(string $title,string $body):void{$flash='';if(isset($_SESSION['flash'])){$flash='<div class="notice" role="status">'.self::e((string)$_SESSION['flash']).'</div>';unset($_SESSION['flash']);}echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.self::e($title).' · Koravik</title><link rel="stylesheet" href="/assets/app.css"><link rel="stylesheet" href="/assets/journey.css"></head><body><main id="main" class="page">'.$flash.$body.'</main><footer>Koravik · Gather</footer></body></html>';}
    private static function e(string $v):string{return htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
}
