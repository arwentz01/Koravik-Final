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
        $event=$this->ownedEvent($ownerId,$eventId);
        $audience=in_array(($input['audience']??''),['all','confirmed','waitlisted','volunteers','slot'],true)?$input['audience']:'all';
        $reference=trim((string)($input['audience_reference']??''))?:null;
        if($audience==='slot'&&$reference===null)throw new RuntimeException('Choose a signup slot for this update.');
        $title=trim((string)($input['title']??''));
        $message=trim((string)($input['message']??''));
        if($title===''||$message==='')throw new RuntimeException('Announcement title and message are required.');
        if(mb_strlen($title)>180)throw new RuntimeException('Announcement titles must be 180 characters or fewer.');

        $id=self::uuid();
        $email=isset($input['email_enabled'])?1:0;
        $this->database->pdo()->prepare('INSERT INTO gather_announcements (id,event_id,author_account_id,audience,audience_reference,title,message,urgency,email_enabled,created_at) VALUES (:id,:event,:author,:audience,:reference,:title,:message,:urgency,:email,UTC_TIMESTAMP())')->execute([
            'id'=>$id,'event'=>$eventId,'author'=>$ownerId,'audience'=>$audience,'reference'=>$reference,'title'=>$title,'message'=>$message,'urgency'=>isset($input['urgent'])?'urgent':'normal','email'=>$email,
        ]);

        if(!$email)return 0;
        $recipients=$this->recipients($eventId,$audience,$reference);
        $queue=new MailQueue($this->database);
        $count=0;
        foreach($recipients as $recipient){
            $subject=(isset($input['urgent'])?'Urgent: ':'').$title;
            $html='<h1>'.htmlspecialchars($title,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8').'</h1><p>'.nl2br(htmlspecialchars($message,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8')).'</p><p><strong>'.htmlspecialchars((string)$event['title'],ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8').'</strong></p>';
            $queue->enqueue('gather.announcement',(string)$recipient['email'],(string)$recipient['name'],$subject,$html,$message,$event['organizer_reply_to_email']??null,'Event organizer',$eventId);
            $count++;
        }
        return $count;
    }

    private function recipients(string $eventId,string $audience,?string $reference):array
    {
        if(in_array($audience,['volunteers','slot'],true)){
            $sql='SELECT DISTINCT COALESCE(c.participant_email,a.email) email,COALESCE(c.participant_name,a.display_name,"Participant") name FROM gather_signup_commitments c JOIN gather_signup_slots s ON s.id=c.slot_id LEFT JOIN platform_accounts a ON a.id=c.account_id WHERE s.event_id=:id AND c.status="active" AND COALESCE(c.participant_email,a.email) IS NOT NULL';
            $params=['id'=>$eventId];
            if($audience==='volunteers')$sql.=' AND s.slot_type="shift"';
            if($audience==='slot'){$sql.=' AND s.id=:slot';$params['slot']=$reference;}
            $statement=$this->database->pdo()->prepare($sql);
            $statement->execute($params);
            return $statement->fetchAll();
        }

        $sql='SELECT DISTINCT COALESCE(r.guest_email,a.email) email,COALESCE(r.guest_name,a.display_name,"Participant") name FROM gather_rsvps r LEFT JOIN platform_accounts a ON a.id=r.account_id WHERE r.event_id=:id AND r.status="active" AND COALESCE(r.guest_email,a.email) IS NOT NULL';
        if($audience==='confirmed')$sql.=' AND r.response="yes"';
        if($audience==='waitlisted')$sql.=' AND r.response="waitlist"';
        $statement=$this->database->pdo()->prepare($sql);
        $statement->execute(['id'=>$eventId]);
        return $statement->fetchAll();
    }

    private function ownedEvent(string $ownerId,string $eventId):array
    {
        $s=$this->database->pdo()->prepare('SELECT * FROM gather_events WHERE id=:id AND account_id=:owner');
        $s->execute(['id'=>$eventId,'owner'=>$ownerId]);
        $event=$s->fetch();
        if(!$event)throw new RuntimeException('Event not found.');
        return $event;
    }

    private static function uuid():string{$d=random_bytes(16);$d[6]=chr((ord($d[6])&0x0f)|0x40);$d[8]=chr((ord($d[8])&0x3f)|0x80);return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($d),4));}
}
