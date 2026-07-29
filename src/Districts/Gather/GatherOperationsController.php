<?php

declare(strict_types=1);

namespace Koravik\Districts\Gather;

use Koravik\Platform\Database\Database;
use Koravik\Platform\Security\Security;
use RuntimeException;

final class GatherOperationsController
{
    public function __construct(private readonly Database $database) {}

    public function handle(string $method,string $path):bool
    {
        if($method==='GET'&&preg_match('#^/gather/events/([a-f0-9-]{36})/command$#',$path,$m)){$this->command($m[1]);return true;}
        if($method==='POST'&&preg_match('#^/gather/events/([a-f0-9-]{36})/settings$#',$path,$m)){$this->settings($m[1]);return true;}
        if($method==='POST'&&preg_match('#^/gather/events/([a-f0-9-]{36})/check-in$#',$path,$m)){$this->checkIn($m[1]);return true;}
        if($method==='POST'&&preg_match('#^/gather/events/([a-f0-9-]{36})/check-in/correct$#',$path,$m)){$this->correct($m[1]);return true;}
        if($method==='POST'&&preg_match('#^/gather/events/([a-f0-9-]{36})/announce$#',$path,$m)){$this->announce($m[1]);return true;}
        return false;
    }

    private function command(string $eventId):void
    {
        $a=Security::requireAccount();try{$e=(new GatherCommandService($this->database))->dashboard((string)$a['id'],$eventId);}catch(RuntimeException){http_response_code(404);echo $this->page('Event not found','<section class="empty-state"><h1>Event not found.</h1></section>');return;}
        $rows='';foreach($e['rsvps'] as $r){$rows.='<tr><td>'.self::e((string)($r['guest_name']?:'Koravik member')).'</td><td>'.self::e((string)$r['response']).'</td><td>'.(int)$r['party_size'].'</td><td><form method="post" action="/gather/events/'.self::e($eventId).'/check-in"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'"><input type="hidden" name="rsvp_id" value="'.self::e((string)$r['id']).'"><input name="party_count" type="number" min="1" max="'.(int)$r['party_size'].'" value="'.(int)$r['party_size'].'"><button class="button secondary">Check in</button></form></td></tr>';}
        $mail='';foreach($e['mail'] as $m){$mail.='<li>'.self::e((string)$m['subject']).' · '.self::e((string)$m['recipient_email']).' · '.self::e((string)$m['status']).'</li>';}
        $body='<section class="page-heading"><div><p class="eyebrow">Gather command center</p><h1>'.self::e((string)$e['title']).'</h1><p>Run the event from one place.</p></div><a href="/gather/events/'.self::e($eventId).'">Public event view</a></section><section class="grid"><article class="surface"><h2>Confirmed</h2><p class="metric">'.(int)$e['confirmed'].'</p></article><article class="surface"><h2>Waitlisted</h2><p class="metric">'.(int)$e['waitlisted'].'</p></article><article class="surface"><h2>Checked in</h2><p class="metric">'.(int)$e['checked_in'].'</p></article><article class="surface"><h2>Open signup units</h2><p class="metric">'.(int)$e['open_signup_units'].'</p></article></section><section class="surface"><h2>Event settings</h2><form method="post" action="/gather/events/'.self::e($eventId).'/settings" class="form-grid"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'"><label>Visibility<select name="visibility"><option value="unlisted">Anyone with link</option><option value="public">Public</option><option value="restricted">Restricted</option></select></label><label>Capacity<input name="capacity" type="number" min="1" value="'.self::e((string)($e['capacity']??'')).'"></label><label><input type="checkbox" name="guest_registration_enabled"'.((int)$e['guest_registration_enabled']?' checked':'').'> Guest registration</label><label><input type="checkbox" name="additional_guests_enabled"'.((int)$e['additional_guests_enabled']?' checked':'').'> Additional guests</label><label>Max additional guests<input name="max_additional_guests" type="number" min="0" value="'.(int)$e['max_additional_guests'].'"></label><label><input type="checkbox" name="waitlist_enabled"'.((int)$e['waitlist_enabled']?' checked':'').'> Waitlist</label><label><input type="checkbox" name="automatic_waitlist_promotion"'.((int)$e['automatic_waitlist_promotion']?' checked':'').'> Automatic promotion</label><label>Max signups per participant<input name="max_signups_per_participant" type="number" min="1" value="'.self::e((string)($e['max_signups_per_participant']??'')).'"></label><label>Offer minutes<input name="waitlist_offer_minutes" type="number" min="15" value="'.(int)$e['waitlist_offer_minutes'].'"></label><label>Organizer reply-to<input name="organizer_reply_to_email" type="email" value="'.self::e((string)($e['organizer_reply_to_email']??'')).'"></label><button class="button">Save settings</button></form></section><section class="surface"><h2>Attendees and check-in</h2><div class="table-wrap"><table><thead><tr><th>Name</th><th>RSVP</th><th>Party</th><th>Day-of</th></tr></thead><tbody>'.($rows?:'<tr><td colspan="4">No RSVPs yet.</td></tr>').'</tbody></table></div></section><section class="grid"><article class="surface"><h2>Send an update</h2><form method="post" action="/gather/events/'.self::e($eventId).'/announce"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'"><label>Audience<select name="audience"><option value="all">Everyone</option><option value="confirmed">Confirmed</option><option value="waitlisted">Waitlisted</option><option value="volunteers">Volunteers</option></select></label><label>Title<input name="title" required></label><label>Message<textarea name="message" required></textarea></label><label><input type="checkbox" name="urgent"> Urgent</label><label><input type="checkbox" name="email_enabled" checked> Email recipients</label><button class="button">Send update</button></form></article><article class="surface"><h2>Recent email delivery</h2><ul>'.($mail?:'<li>No messages yet.</li>').'</ul></article></section>';
        echo $this->page((string)$e['title'],$body);
    }

    private function settings(string $id):void{$this->csrf();$a=Security::requireAccount();try{(new GatherCommandService($this->database))->updateSettings((string)$a['id'],$id,$_POST);$_SESSION['flash']='Event settings updated.';}catch(RuntimeException $e){$_SESSION['flash']=$e->getMessage();}$this->go('/gather/events/'.$id.'/command');}
    private function checkIn(string $id):void{$this->csrf();$a=Security::requireAccount();try{(new GatherDayOfService($this->database))->checkIn((string)$a['id'],$id,(string)($_POST['rsvp_id']??''),(int)($_POST['party_count']??1));$_SESSION['flash']='Check-in recorded.';}catch(RuntimeException $e){$_SESSION['flash']=$e->getMessage();}$this->go('/gather/events/'.$id.'/command');}
    private function correct(string $id):void{$this->csrf();$a=Security::requireAccount();try{(new GatherDayOfService($this->database))->correct((string)$a['id'],$id,(string)($_POST['rsvp_id']??''),(string)($_POST['note']??''));$_SESSION['flash']='Check-in corrected.';}catch(RuntimeException $e){$_SESSION['flash']=$e->getMessage();}$this->go('/gather/events/'.$id.'/command');}
    private function announce(string $id):void{$this->csrf();$a=Security::requireAccount();try{$count=(new GatherCommunicationService($this->database))->send((string)$a['id'],$id,$_POST);$_SESSION['flash']='Update saved; '.$count.' email delivery record(s) queued.';}catch(RuntimeException $e){$_SESSION['flash']=$e->getMessage();}$this->go('/gather/events/'.$id.'/command');}
    private function csrf():void{if(!Security::verifyCsrf((string)($_POST['csrf']??'')))throw new RuntimeException('Your session token expired.');}
    private function go(string $to):never{header('Location: '.$to,true,303);exit;}
    private function page(string $title,string $body):string{$flash='';if(isset($_SESSION['flash'])){$flash='<div class="notice">'.self::e((string)$_SESSION['flash']).'</div>';unset($_SESSION['flash']);}return '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.self::e($title).' · Koravik</title><link rel="stylesheet" href="/assets/app.css"><link rel="stylesheet" href="/assets/journey.css"></head><body><main id="main" class="page">'.$flash.$body.'</main><footer>Koravik · Gather</footer></body></html>';}
    private static function e(string $v):string{return htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
}
