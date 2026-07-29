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
        $account=Security::requireAccount();
        try{$event=(new GatherCommandService($this->database))->dashboard((string)$account['id'],$eventId);}
        catch(RuntimeException){http_response_code(404);echo $this->page('Event not found','<section class="empty-state"><h1>Event not found.</h1></section>');return;}

        $rows='';
        foreach($event['rsvps'] as $rsvp){
            $name=self::e((string)($rsvp['guest_name']?:'Koravik member'));
            $checked=(int)($rsvp['checked_in_party_count']??0);
            $dayOf=$checked>0
                ?'<p><strong>'.$checked.' checked in</strong></p><form method="post" action="/gather/events/'.self::e($eventId).'/check-in/correct"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'"><input type="hidden" name="rsvp_id" value="'.self::e((string)$rsvp['id']).'"><label>Correction note<input name="note" maxlength="500" required></label><button class="button secondary" type="submit">Void check-in</button></form>'
                :'<form method="post" action="/gather/events/'.self::e($eventId).'/check-in"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'"><input type="hidden" name="rsvp_id" value="'.self::e((string)$rsvp['id']).'"><label>Party arriving<input name="party_count" type="number" min="1" max="'.(int)$rsvp['party_size'].'" value="'.(int)$rsvp['party_size'].'"></label><button class="button secondary" type="submit">Check in</button></form>';
            $rows.='<tr><td>'.$name.'</td><td>'.self::e(ucfirst((string)$rsvp['response'])).'</td><td>'.(int)$rsvp['party_size'].'</td><td>'.$dayOf.'</td></tr>';
        }

        $mail='';
        foreach($event['mail'] as $delivery){
            $detail=$delivery['status']==='failed'&&$delivery['failure_reason']?' · '.self::e((string)$delivery['failure_reason']):'';
            $mail.='<li><strong>'.self::e((string)$delivery['subject']).'</strong><br>'.self::e((string)$delivery['recipient_email']).' · '.self::e((string)$delivery['status']).' · attempt '.(int)$delivery['attempts'].$detail.'</li>';
        }

        $history='';
        foreach($event['announcements'] as $announcement){
            $history.='<li><strong>'.self::e((string)$announcement['title']).'</strong> · '.self::e((string)$announcement['audience']).' · '.self::e((string)$announcement['urgency']).'<br>'.nl2br(self::e((string)$announcement['message'])).'</li>';
        }

        $slotOptions='';
        foreach($event['slots'] as $slot){$slotOptions.='<option value="'.self::e((string)$slot['id']).'">'.self::e((string)$slot['title']).'</option>';}
        $visibility=(string)$event['visibility'];
        $option=static fn(string $value,string $label):string=>'<option value="'.$value.'"'.($visibility===$value?' selected':'').'>'.$label.'</option>';

        $body='<section class="page-heading"><div><p class="eyebrow">Gather command center</p><h1>'.self::e((string)$event['title']).'</h1><p>Run the event from one place.</p></div><p><a href="/gather/events/'.self::e($eventId).'">Event view</a> · <a href="/gather">All events</a></p></section>'
            .'<section class="grid"><article class="surface"><h2>Confirmed</h2><p class="metric">'.(int)$event['confirmed'].'</p></article><article class="surface"><h2>Waitlisted</h2><p class="metric">'.(int)$event['waitlisted'].'</p></article><article class="surface"><h2>Checked in</h2><p class="metric">'.(int)$event['checked_in'].'</p></article><article class="surface"><h2>Open signup units</h2><p class="metric">'.(int)$event['open_signup_units'].'</p></article></section>'
            .'<section class="surface"><h2>Event settings</h2><form method="post" action="/gather/events/'.self::e($eventId).'/settings" class="form-grid"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'"><label>Visibility<select name="visibility">'.$option('unlisted','Anyone with link').$option('public','Public').$option('restricted','Restricted').'</select></label><label>Capacity<input name="capacity" type="number" min="1" value="'.self::e((string)($event['capacity']??'')).'"></label><label><input type="checkbox" name="guest_registration_enabled"'.((int)$event['guest_registration_enabled']?' checked':'').'> Guest registration</label><label><input type="checkbox" name="additional_guests_enabled"'.((int)$event['additional_guests_enabled']?' checked':'').'> Additional guests</label><label>Max additional guests<input name="max_additional_guests" type="number" min="0" value="'.(int)$event['max_additional_guests'].'"></label><label><input type="checkbox" name="waitlist_enabled"'.((int)$event['waitlist_enabled']?' checked':'').'> Waitlist</label><label><input type="checkbox" name="automatic_waitlist_promotion"'.((int)$event['automatic_waitlist_promotion']?' checked':'').'> Automatic promotion</label><label>Max signups per participant<input name="max_signups_per_participant" type="number" min="1" value="'.self::e((string)($event['max_signups_per_participant']??'')).'"></label><label>Offer minutes<input name="waitlist_offer_minutes" type="number" min="15" value="'.(int)$event['waitlist_offer_minutes'].'"></label><label>Organizer reply-to<input name="organizer_reply_to_email" type="email" value="'.self::e((string)($event['organizer_reply_to_email']??'')).'"></label><button class="button" type="submit">Save settings</button></form></section>'
            .'<section class="surface"><h2>Attendees and check-in</h2><div class="table-wrap"><table><thead><tr><th>Name</th><th>RSVP</th><th>Party</th><th>Day-of</th></tr></thead><tbody>'.($rows?:'<tr><td colspan="4">No RSVPs yet.</td></tr>').'</tbody></table></div></section>'
            .'<section class="grid"><article class="surface"><h2>Send an update</h2><form method="post" action="/gather/events/'.self::e($eventId).'/announce"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'"><label>Audience<select name="audience"><option value="all">Everyone with an RSVP</option><option value="confirmed">Confirmed attendees</option><option value="waitlisted">Waitlisted guests</option><option value="volunteers">All volunteer shifts</option>'.($slotOptions?'<option value="slot">One signup slot</option>':'').'</select></label>'.($slotOptions?'<label>Signup slot<select name="audience_reference"><option value="">Choose when targeting one slot</option>'.$slotOptions.'</select></label>':'').'<label>Title<input name="title" maxlength="180" required></label><label>Message<textarea name="message" required></textarea></label><label><input type="checkbox" name="urgent"> Urgent</label><label><input type="checkbox" name="email_enabled" checked> Email recipients</label><button class="button" type="submit">Save and send update</button></form></article><article class="surface"><h2>Recent updates</h2><ul>'.($history?:'<li>No updates yet.</li>').'</ul></article></section>'
            .'<section class="surface"><h2>Recent email delivery</h2><ul>'.($mail?:'<li>No event-linked mail yet.</li>').'</ul></section>';
        echo $this->page((string)$event['title'],$body);
    }

    private function settings(string $id):void{$this->csrf();$a=Security::requireAccount();try{(new GatherCommandService($this->database))->updateSettings((string)$a['id'],$id,$_POST);$_SESSION['flash']='Event settings updated.';}catch(RuntimeException $e){$_SESSION['flash']=$e->getMessage();}$this->go('/gather/events/'.$id.'/command');}
    private function checkIn(string $id):void{$this->csrf();$a=Security::requireAccount();try{(new GatherDayOfService($this->database))->checkIn((string)$a['id'],$id,(string)($_POST['rsvp_id']??''),(int)($_POST['party_count']??1));$_SESSION['flash']='Check-in recorded.';}catch(RuntimeException $e){$_SESSION['flash']=$e->getMessage();}$this->go('/gather/events/'.$id.'/command');}
    private function correct(string $id):void{$this->csrf();$a=Security::requireAccount();try{(new GatherDayOfService($this->database))->correct((string)$a['id'],$id,(string)($_POST['rsvp_id']??''),(string)($_POST['note']??''));$_SESSION['flash']='Check-in voided with a correction record.';}catch(RuntimeException $e){$_SESSION['flash']=$e->getMessage();}$this->go('/gather/events/'.$id.'/command');}
    private function announce(string $id):void{$this->csrf();$a=Security::requireAccount();try{$count=(new GatherCommunicationService($this->database))->send((string)$a['id'],$id,$_POST);$_SESSION['flash']='Update saved; '.$count.' email delivery record(s) queued.';}catch(RuntimeException $e){$_SESSION['flash']=$e->getMessage();}$this->go('/gather/events/'.$id.'/command');}
    private function csrf():void{if(!Security::verifyCsrf((string)($_POST['csrf']??'')))throw new RuntimeException('Your session token expired.');}
    private function go(string $to):never{header('Location: '.$to,true,303);exit;}
    private function page(string $title,string $body):string{$flash='';if(isset($_SESSION['flash'])){$flash='<div class="notice">'.self::e((string)$_SESSION['flash']).'</div>';unset($_SESSION['flash']);}return '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.self::e($title).' · Koravik</title><link rel="stylesheet" href="/assets/app.css"><link rel="stylesheet" href="/assets/journey.css"></head><body><main id="main" class="page">'.$flash.$body.'</main><footer>Koravik · Gather</footer></body></html>';}
    private static function e(string $v):string{return htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
}
