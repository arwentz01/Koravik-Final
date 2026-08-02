<?php

declare(strict_types=1);

namespace Koravik\Districts\Gather;

use Koravik\Platform\Database\Database;
use Koravik\Platform\Security\Security;
use PDO;
use RuntimeException;

final class GatherCloseoutController
{
    public function __construct(private readonly Database $database) {}

    public function handle(string $method,string $path): bool
    {
        if($method==='GET'&&preg_match('#^/gather/events/([a-f0-9-]{36})/closeout$#',$path,$m)){$this->closeout($m[1]);return true;}
        if($method==='POST'&&preg_match('#^/gather/events/([a-f0-9-]{36})/followups$#',$path,$m)){$this->createFollowup($m[1]);return true;}
        if($method==='GET'&&preg_match('#^/gather/outcomes/([a-f0-9-]{36})/review$#',$path,$m)){$this->review($m[1]);return true;}
        return false;
    }

    private function closeout(string $eventId): void
    {
        $a=Security::requireAccount();try{$authorized=(new GatherAuthorization($this->database))->requireManage((string)$a['id'],$eventId);}catch(RuntimeException){http_response_code(404);$this->page('Event unavailable','<section class="empty-state"><h1>Event unavailable.</h1></section>');return;}$s=$this->database->pdo()->prepare('SELECT e.*,(SELECT COALESCE(SUM(party_size),0) FROM gather_rsvps r WHERE r.event_id=e.id AND r.response="yes") confirmed_people,(SELECT COUNT(*) FROM gather_checkins c WHERE c.event_id=e.id) checked_in_parties,(SELECT COUNT(*) FROM gather_walkins w WHERE w.event_id=e.id) walkin_parties FROM gather_events e WHERE e.id=:id LIMIT 1');$s->execute(['id'=>$authorized['id']]);$event=$s->fetch();
        $body='<section class="page-heading"><div><p class="eyebrow">Event closeout</p><h1>'.self::e((string)$event['title']).'</h1><p>Finish the event deliberately while preserving RSVP, attendance, announcement, signup, and delivery history.</p></div></section><section class="grid"><article class="surface"><h2>Final snapshot</h2><dl><div><dt>Confirmed people</dt><dd>'.(int)$event['confirmed_people'].'</dd></div><div><dt>Checked-in parties</dt><dd>'.(int)$event['checked_in_parties'].'</dd></div><div><dt>Walk-in parties</dt><dd>'.(int)$event['walkin_parties'].'</dd></div><div><dt>Current lifecycle</dt><dd>'.self::e((string)$event['lifecycle_status']).'</dd></div></dl></article><article class="surface gather-followup-panel"><h2>Draft post-event follow-up</h2><p>Follow-up remains Gather-owned until the host sends it. Chronicle or Quest outcomes are proposed only after explicit review.</p><form method="post" action="/gather/events/'.self::e($eventId).'/followups"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'"><label>Title<input name="title" maxlength="180" required value="Thank you for coming"></label><label>Message<textarea name="message" required></textarea></label><label>Audience<select name="audience"><option value="confirmed">Confirmed guests</option><option value="attended">Checked-in attendees</option><option value="volunteers">Volunteers</option><option value="all">Everyone invited</option></select></label><label class="check-row"><input type="checkbox" name="chronicle_proposal" value="1"> Offer optional Chronicle reflection</label><label class="check-row"><input type="checkbox" name="quest_proposal" value="1"> Offer optional follow-up Quest</label><button class="button" type="submit">Save follow-up draft</button></form></article></section><section class="surface gather-aftercare-loop"><h2>Gather Aftercare Loop</h2><p>Aftercare guides hosts from closeout to optional follow-up, reflection, Quest creation, Moment preservation, and Source Inbox review without sending guest communication automatically.</p></section><section class="surface"><h2>Follow-up review</h2><p>Use the command center announcement tools for bounded thank-you, cancellation, or follow-up messages. Delivery remains visible through Platform Mail. Drafts can also appear in the Source Inbox for Gather Follow-Up to Quest/Chronicle Drafts.</p><p><a href="/gather/events/'.self::e($eventId).'/command">Open command center</a> <a href="/gather/events/'.self::e($eventId).'/reflect">Open reflection</a> <a href="/source-review">Open Source Inbox</a></p></section>';$this->page('Event closeout',$body);
    }

    private function createFollowup(string $eventId): void
    {
        $this->csrf();$a=Security::requireAccount();
        try{
            (new GatherAuthorization($this->database))->requireManage((string)$a['id'],$eventId);
            $title=trim((string)($_POST['title']??''));$message=trim((string)($_POST['message']??''));$audience=(string)($_POST['audience']??'confirmed');
            if($title===''||$message==='')throw new RuntimeException('Follow-up title and message are required.');
            if(!in_array($audience,['all','confirmed','attended','volunteers'],true))throw new RuntimeException('Choose a valid follow-up audience.');
            $id=self::uuid();
            $this->database->transaction(function(PDO $pdo)use($id,$eventId,$a,$title,$message,$audience):void{$pdo->prepare('INSERT INTO gather_event_followups (id,event_id,author_account_id,title,message,audience,status,created_chronicle_proposal,created_quest_proposal,created_at) VALUES (:id,:event,:author,:title,:message,:audience,"draft",:chronicle,:quest,UTC_TIMESTAMP())')->execute(['id'=>$id,'event'=>$eventId,'author'=>(string)$a['id'],'title'=>$title,'message'=>$message,'audience'=>$audience,'chronicle'=>isset($_POST['chronicle_proposal'])?1:0,'quest'=>isset($_POST['quest_proposal'])?1:0]);$pdo->prepare('INSERT INTO audit_log (id,account_id,action,subject_type,subject_id,occurred_at) VALUES (:id,:account_id,"gather.followup.created","gather_followup",:subject,UTC_TIMESTAMP())')->execute(['id'=>self::uuid(),'account_id'=>(string)$a['id'],'subject'=>$id]);});
            $_SESSION['flash']='Gather follow-up draft saved for review.';
        }catch(RuntimeException $e){$_SESSION['flash']=$e->getMessage();}
        $this->redirect('/gather/events/'.$eventId.'/closeout');
    }

    private function review(string $id): void
    {
        $a=Security::requireAccount();$s=$this->database->pdo()->prepare('SELECT o.*,e.title event_title FROM gather_outcome_proposals o JOIN gather_events e ON e.id=o.event_id WHERE o.id=:id AND o.account_id=:account LIMIT 1');$s->execute(['id'=>$id,'account'=>$a['id']]);$row=$s->fetch();if(!$row){http_response_code(404);$this->page('Proposal unavailable','<section class="empty-state"><h1>Proposal unavailable.</h1></section>');return;}$payload=self::e((string)$row['minimized_payload_json']);$body='<section class="page-heading"><div><p class="eyebrow">Consent review</p><h1>Review before anything crosses boundaries.</h1><p>'.self::e((string)$row['event_title']).'</p></div></section><section class="surface"><dl><div><dt>Destination workflow</dt><dd>'.self::e(str_replace('_',' ',(string)$row['outcome_type'])).'</dd></div><div><dt>Your summary</dt><dd>'.self::e((string)$row['summary']).'</dd></div><div><dt>Minimized payload</dt><dd><code>'.$payload.'</code></dd></div><div><dt>Status</dt><dd>'.self::e((string)$row['status']).'</dd></div></dl>'.($row['status']==='proposed'?'<form method="post" action="/gather/outcomes/'.self::e($id).'/approve"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'"><button class="button" type="submit">Approve this proposal</button></form>':'<p>This proposal is no longer awaiting approval.</p>').'</section>';$this->page('Review outcome proposal',$body);
    }

    private function page(string $title,string $body):void{$flash='';if(isset($_SESSION['flash'])){$flash='<div class="notice" role="status">'.self::e((string)$_SESSION['flash']).'</div>';unset($_SESSION['flash']);}echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.self::e($title).' · Koravik</title><link rel="stylesheet" href="/assets/app.css"><link rel="stylesheet" href="/assets/journey.css"></head><body><main id="main" class="page">'.$flash.$body.'</main><footer>Koravik · Gather</footer></body></html>';}
    private function csrf():void{if(!Security::verifyCsrf((string)($_POST['csrf']??'')))throw new RuntimeException('Your session changed. Please try again.');}
    private function redirect(string $to):never{header('Location: '.$to,true,303);exit;}
    private static function uuid():string{$d=random_bytes(16);$d[6]=chr((ord($d[6])&0x0f)|0x40);$d[8]=chr((ord($d[8])&0x3f)|0x80);return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($d),4));}
    private static function e(string $v):string{return htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
}
