<?php

declare(strict_types=1);

namespace Koravik\Districts\Gather;

use Koravik\Platform\Database\Database;
use Koravik\Platform\Mail\MailQueue;
use RuntimeException;

final class GatherCommunicationService
{
    public function __construct(private readonly Database $database) {}

    public function send(string $ownerId,string $eventId,array $input):int
    {
        $event=$this->ownedEvent($ownerId,$eventId);$audience=in_array(($input['audience']??''),['all','confirmed','waitlisted','volunteers','slot'],true)?$input['audience']:'all';$title=trim((string)($input['title']??''));$message=trim((string)($input['message']??''));if($title===''||$message==='')throw new RuntimeException('Announcement title and message are required.');$reference=($input['audience_reference']??'')?:null;$id=self::uuid();$email=isset($input['email_enabled'])?1:0;
        $this->database->pdo()->prepare('INSERT INTO gather_announcements (id,event_id,author_account_id,audience,audience_reference,title,message,urgency,email_enabled,created_at) VALUES (:id,:event,:author,:audience,:reference,:title,:message,:urgency,:email,UTC_TIMESTAMP())')->execute(['id'=>$id,'event'=>$eventId,'author'=>$ownerId,'audience'=>$audience,'reference'=>$reference,'title'=>$title,'message'=>$message,'urgency'=>isset($input['urgent'])?'urgent':'normal','email'=>$email]);
        if(!$email)return 0;$recipients=$this->recipients($eventId,$audience,$reference);$queue=new MailQueue($this->database);$count=0;foreach($recipients as $r){$subject=(isset($input['urgent'])?'Urgent: ':'').$title;$html='<h1>'.htmlspecialchars($title,ENT_QUOTES).'</h1><p>'.nl2br(htmlspecialchars($message,ENT_QUOTES)).'</p><p><strong>'.htmlspecialchars((string)$event['title'],ENT_QUOTES).'</strong></p>';$queue->enqueue('gather.announcement',(string)$r['email'],(string)$r['name'],$subject,$html,$message,$event['organizer_reply_to_email']??null,'Event organizer');$count++;}return $count;
    }

    public function history(string $ownerId,string $eventId):array{$this->ownedEvent($ownerId,$eventId);$s=$this->database->pdo()->prepare('SELECT * FROM gather_announcements WHERE event_id=:id ORDER BY created_at DESC LIMIT 50');$s->execute(['id'=>$eventId]);return $s->fetchAll();}

    private function recipients(string $eventId,string $audience,?string $reference):array
    {
        $sql='SELECT DISTINCT COALESCE(r.guest_email,a.email) email,COALESCE(r.guest_name,a.display_name,"Participant") name FROM gather_rsvps r LEFT JOIN platform_accounts a ON a.id=r.account_id WHERE r.event_id=:id AND r.status="active" AND COALESCE(r.guest_email,a.email) IS NOT NULL';$params=['id'=>$eventId];
        if($audience==='confirmed')$sql.=' AND r.response="yes"';elseif($audience==='waitlisted')$sql.=' AND r.response="waitlist"';elseif($audience==='volunteers')$sql.=' AND EXISTS (SELECT 1 FROM gather_signup_commitments c JOIN gather_signup_slots s ON s.id=c.slot_id WHERE c.rsvp_id=r.id AND c.status="active" AND s.slot_type="shift")';elseif($audience==='slot'){$sql.=' AND EXISTS (SELECT 1 FROM gather_signup_commitments c WHERE c.rsvp_id=r.id AND c.slot_id=:slot AND c.status="active")';$params['slot']=$reference;}
        $s=$this->database->pdo()->prepare($sql);$s->execute($params);return $s->fetchAll();
    }

    private function ownedEvent(string $ownerId,string $eventId):array{$s=$this->database->pdo()->prepare('SELECT * FROM gather_events WHERE id=:id AND account_id=:owner');$s->execute(['id'=>$eventId,'owner'=>$ownerId]);$event=$s->fetch();if(!$event)throw new RuntimeException('Event not found.');return $event;}
    private static function uuid():string{$d=random_bytes(16);$d[6]=chr((ord($d[6])&0x0f)|0x40);$d[8]=chr((ord($d[8])&0x3f)|0x80);return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($d),4));}
}
