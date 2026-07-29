<?php

declare(strict_types=1);

namespace Koravik\Districts\Gather;

use Koravik\Platform\Database\Database;
use Koravik\Platform\Mail\MailQueue;
use RuntimeException;

final class GatherLifecycleService
{
    public function __construct(private readonly Database $database) {}

    public function agenda(string $eventId): array
    {
        $s=$this->database->pdo()->prepare('SELECT * FROM gather_agenda_items WHERE event_id=:event ORDER BY starts_at,position,title');$s->execute(['event'=>$eventId]);return $s->fetchAll();
    }

    public function addAgenda(string $accountId,string $eventId,array $input): string
    {
        $this->ownedEvent($accountId,$eventId);$title=trim((string)($input['title']??''));$starts=str_replace('T',' ',trim((string)($input['starts_at']??'')));if($title===''||$starts==='')throw new RuntimeException('Agenda title and start time are required.');
        $id=self::uuid();$this->database->pdo()->prepare('INSERT INTO gather_agenda_items (id,event_id,title,description,starts_at,ends_at,location_label,position,status,created_at,updated_at) VALUES (:id,:event,:title,:description,:starts,:ends,:location,:position,"scheduled",UTC_TIMESTAMP(),UTC_TIMESTAMP())')->execute(['id'=>$id,'event'=>$eventId,'title'=>$title,'description'=>(string)($input['description']??''),'starts'=>$starts,'ends'=>($input['ends_at']??'')!==''?str_replace('T',' ',(string)$input['ends_at']):null,'location'=>(string)($input['location']??''),'position'=>max(0,(int)($input['position']??0))]);return $id;
    }

    public function favorite(string $agendaId,?string $accountId,string $email,int $minutes): void
    {
        $email=strtolower(trim($email));if(!$accountId&&!filter_var($email,FILTER_VALIDATE_EMAIL))throw new RuntimeException('Enter a valid email for reminders.');$id=self::uuid();$status=$minutes>0?'pending':'none';
        $this->database->pdo()->prepare('INSERT INTO gather_agenda_favorites (id,agenda_item_id,account_id,guest_email,reminder_minutes,reminder_status,created_at,updated_at) VALUES (:id,:agenda,:account,:email,:minutes,:status,UTC_TIMESTAMP(),UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE reminder_minutes=VALUES(reminder_minutes),reminder_status=VALUES(reminder_status),updated_at=UTC_TIMESTAMP()')->execute(['id'=>$id,'agenda'=>$agendaId,'account'=>$accountId,'email'=>$email!==''?$email:null,'minutes'=>$minutes>0?$minutes:null,'status'=>$status]);
    }

    public function attendeeSearch(string $accountId,string $eventId,string $query): array
    {
        $this->ownedEvent($accountId,$eventId);$q='%'.trim($query).'%';$s=$this->database->pdo()->prepare('SELECT id,guest_name,guest_email,response,party_size,status FROM gather_rsvps WHERE event_id=:event AND (guest_name LIKE :q OR guest_email LIKE :q) ORDER BY guest_name LIMIT 50');$s->execute(['event'=>$eventId,'q'=>$q]);return $s->fetchAll();
    }

    public function addWalkin(string $accountId,string $eventId,array $input): string
    {
        $this->ownedEvent($accountId,$eventId);$name=trim((string)($input['name']??''));if($name==='')throw new RuntimeException('Enter the walk-in attendee name.');$email=strtolower(trim((string)($input['email']??'')));if($email!==''&&!filter_var($email,FILTER_VALIDATE_EMAIL))throw new RuntimeException('Enter a valid email address.');$id=self::uuid();$this->database->pdo()->prepare('INSERT INTO gather_walkins (id,event_id,attendee_name,attendee_email,party_count,checked_in_by_account_id,checked_in_at,note) VALUES (:id,:event,:name,:email,:party,:account,UTC_TIMESTAMP(),:note)')->execute(['id'=>$id,'event'=>$eventId,'name'=>$name,'email'=>$email?:null,'party'=>max(1,(int)($input['party_count']??1)),'account'=>$accountId,'note'=>(string)($input['note']??'')]);return $id;
    }

    public function closeEvent(string $accountId,string $eventId,string $status,string $note): void
    {
        $this->ownedEvent($accountId,$eventId);if(!in_array($status,['completed','cancelled','archived'],true))throw new RuntimeException('Choose a valid closeout state.');$this->database->pdo()->prepare('UPDATE gather_events SET lifecycle_status=:status,closed_at=UTC_TIMESTAMP(),closeout_note=:note,updated_at=UTC_TIMESTAMP() WHERE id=:id')->execute(['status'=>$status,'note'=>trim($note),'id'=>$eventId]);
    }

    public function proposeOutcome(string $accountId,string $eventId,string $type,string $summary): string
    {
        $this->database->pdo()->prepare('SELECT id FROM gather_events WHERE id=:id')->execute(['id'=>$eventId]);if(!in_array($type,['chronicle_reflection','quest_progress','journey_invitation','world_fact'],true))throw new RuntimeException('Choose a valid outcome type.');$summary=trim($summary);if($summary==='')throw new RuntimeException('Describe what this experience meant.');$id=self::uuid();$payload=json_encode(['event_id'=>$eventId,'summary'=>$summary],JSON_THROW_ON_ERROR);$this->database->pdo()->prepare('INSERT INTO gather_outcome_proposals (id,event_id,account_id,outcome_type,summary,minimized_payload_json,status,created_at,updated_at) VALUES (:id,:event,:account,:type,:summary,:payload,"proposed",UTC_TIMESTAMP(),UTC_TIMESTAMP())')->execute(['id'=>$id,'event'=>$eventId,'account'=>$accountId,'type'=>$type,'summary'=>$summary,'payload'=>$payload]);return $id;
    }

    public function approveOutcome(string $accountId,string $id): void
    {
        $s=$this->database->pdo()->prepare('UPDATE gather_outcome_proposals SET status="approved",approved_at=UTC_TIMESTAMP(),updated_at=UTC_TIMESTAMP() WHERE id=:id AND account_id=:account AND status="proposed"');$s->execute(['id'=>$id,'account'=>$accountId]);if($s->rowCount()!==1)throw new RuntimeException('That proposal is no longer available.');
    }

    public function queueDueReminders(int $limit=100): int
    {
        $s=$this->database->pdo()->prepare('SELECT f.*,a.title,a.starts_at,e.title event_title FROM gather_agenda_favorites f JOIN gather_agenda_items a ON a.id=f.agenda_item_id JOIN gather_events e ON e.id=a.event_id LEFT JOIN platform_accounts p ON p.id=f.account_id WHERE f.reminder_status="pending" AND DATE_SUB(a.starts_at,INTERVAL f.reminder_minutes MINUTE)<=UTC_TIMESTAMP() ORDER BY a.starts_at LIMIT '.$limit);$s->execute();$count=0;$queue=new MailQueue($this->database);foreach($s->fetchAll() as $row){$email=(string)($row['guest_email']??'');if($email===''){$a=$this->database->pdo()->prepare('SELECT email FROM platform_accounts WHERE id=:id');$a->execute(['id'=>$row['account_id']]);$email=(string)$a->fetchColumn();}if(!filter_var($email,FILTER_VALIDATE_EMAIL))continue;$delivery=$queue->enqueue('gather.agenda_reminder',$email,'','Reminder: '.(string)$row['title'],'<p>'.htmlspecialchars((string)$row['event_title']).'</p><p>'.htmlspecialchars((string)$row['title']).' begins at '.htmlspecialchars((string)$row['starts_at']).' UTC.</p>',(string)$row['title'].' begins at '.(string)$row['starts_at'].' UTC.');$this->database->pdo()->prepare('UPDATE gather_agenda_favorites SET reminder_status="queued",reminder_delivery_id=:delivery,updated_at=UTC_TIMESTAMP() WHERE id=:id')->execute(['delivery'=>$delivery,'id'=>$row['id']]);$count++;}return $count;
    }

    private function ownedEvent(string $accountId,string $eventId): array
    {
        $s=$this->database->pdo()->prepare('SELECT * FROM gather_events WHERE id=:id AND account_id=:account LIMIT 1');$s->execute(['id'=>$eventId,'account'=>$accountId]);$event=$s->fetch();if(!$event)throw new RuntimeException('Event not found.');return $event;
    }

    private static function uuid(): string{$d=random_bytes(16);$d[6]=chr((ord($d[6])&0x0f)|0x40);$d[8]=chr((ord($d[8])&0x3f)|0x80);return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($d),4));}
}