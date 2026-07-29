<?php

declare(strict_types=1);

namespace Koravik\Districts\Gather;

use Koravik\Platform\Database\Database;
use Koravik\Platform\Security\Security;

final class GatherCloseoutController
{
    public function __construct(private readonly Database $database) {}

    public function handle(string $method,string $path): bool
    {
        if($method==='GET'&&preg_match('#^/gather/events/([a-f0-9-]{36})/closeout$#',$path,$m)){$this->closeout($m[1]);return true;}
        if($method==='GET'&&preg_match('#^/gather/outcomes/([a-f0-9-]{36})/review$#',$path,$m)){$this->review($m[1]);return true;}
        return false;
    }

    private function closeout(string $eventId): void
    {
        $a=Security::requireAccount();$s=$this->database->pdo()->prepare('SELECT e.*,(SELECT COALESCE(SUM(party_size),0) FROM gather_rsvps r WHERE r.event_id=e.id AND r.response="yes") confirmed_people,(SELECT COUNT(*) FROM gather_checkins c WHERE c.event_id=e.id) checked_in_parties,(SELECT COUNT(*) FROM gather_walkins w WHERE w.event_id=e.id) walkin_parties FROM gather_events e WHERE e.id=:id AND e.account_id=:account LIMIT 1');$s->execute(['id'=>$eventId,'account'=>$a['id']]);$event=$s->fetch();if(!$event){http_response_code(404);$this->page('Event unavailable','<section class="empty-state"><h1>Event unavailable.</h1></section>');return;}
        $body='<section class="page-heading"><div><p class="eyebrow">Event closeout</p><h1>'.self::e((string)$event['title']).'</h1><p>Finish the event deliberately while preserving RSVP, attendance, announcement, signup, and delivery history.</p></div></section><section class="grid"><article class="surface"><h2>Final snapshot</h2><dl><div><dt>Confirmed people</dt><dd>'.(int)$event['confirmed_people'].'</dd></div><div><dt>Checked-in parties</dt><dd>'.(int)$event['checked_in_parties'].'</dd></div><div><dt>Walk-in parties</dt><dd>'.(int)$event['walkin_parties'].'</dd></div><div><dt>Current lifecycle</dt><dd>'.self::e((string)$event['lifecycle_status']).'</dd></div></dl></article><article class="surface"><h2>Close event</h2><form method="post" action="/gather/events/'.self::e($eventId).'/closeout"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'"><label>Outcome<select name="status"><option value="completed">Completed</option><option value="cancelled">Cancelled</option><option value="archived">Archive</option></select></label><label>Closeout note<textarea name="note">'.self::e((string)($event['closeout_note']??'')).'</textarea></label><button class="button" type="submit">Save closeout</button></form></article></section><section class="surface"><h2>Follow-up</h2><p>Use the command center announcement tools for bounded thank-you, cancellation, or follow-up messages. Delivery remains visible through Platform Mail.</p><p><a href="/gather/events/'.self::e($eventId).'/command">Open command center</a> <a href="/gather/events/'.self::e($eventId).'/reflect">Open reflection</a></p></section>';$this->page('Event closeout',$body);
    }

    private function review(string $id): void
    {
        $a=Security::requireAccount();$s=$this->database->pdo()->prepare('SELECT o.*,e.title event_title FROM gather_outcome_proposals o JOIN gather_events e ON e.id=o.event_id WHERE o.id=:id AND o.account_id=:account LIMIT 1');$s->execute(['id'=>$id,'account'=>$a['id']]);$row=$s->fetch();if(!$row){http_response_code(404);$this->page('Proposal unavailable','<section class="empty-state"><h1>Proposal unavailable.</h1></section>');return;}$payload=self::e((string)$row['minimized_payload_json']);$body='<section class="page-heading"><div><p class="eyebrow">Consent review</p><h1>Review before anything crosses boundaries.</h1><p>'.self::e((string)$row['event_title']).'</p></div></section><section class="surface"><dl><div><dt>Destination workflow</dt><dd>'.self::e(str_replace('_',' ',(string)$row['outcome_type'])).'</dd></div><div><dt>Your summary</dt><dd>'.self::e((string)$row['summary']).'</dd></div><div><dt>Minimized payload</dt><dd><code>'.$payload.'</code></dd></div><div><dt>Status</dt><dd>'.self::e((string)$row['status']).'</dd></div></dl>'.($row['status']==='proposed'?'<form method="post" action="/gather/outcomes/'.self::e($id).'/approve"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'"><button class="button" type="submit">Approve this proposal</button></form>':'<p>This proposal is no longer awaiting approval.</p>').'</section>';$this->page('Review outcome proposal',$body);
    }

    private function page(string $title,string $body):void{$flash='';if(isset($_SESSION['flash'])){$flash='<div class="notice" role="status">'.self::e((string)$_SESSION['flash']).'</div>';unset($_SESSION['flash']);}echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.self::e($title).' · Koravik</title><link rel="stylesheet" href="/assets/app.css"><link rel="stylesheet" href="/assets/journey.css"></head><body><main id="main" class="page">'.$flash.$body.'</main><footer>Koravik · Gather</footer></body></html>';}
    private static function e(string $v):string{return htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
}