<?php

declare(strict_types=1);

namespace Koravik\Districts\Gather;

use Koravik\Platform\Database\Database;
use Koravik\Platform\Mail\MailQueue;
use RuntimeException;

final class GatherLifecycleService
{
    public function __construct(private readonly Database $database) {}

    public function agenda(string $eventId): array { $s=$this->database->pdo()->prepare('SELECT * FROM gather_agenda_items WHERE event_id=:event ORDER BY starts_at,position,title');$s->execute(['event'=>$eventId]);return $s->fetchAll(); }

    public function addAgenda(string $accountId,string $eventId,array $input): string
    {
        $event=$this->ownedEvent($accountId,$eventId);$title=trim((string)($input['title']??''));$starts=str_replace('T',' ',trim((string)($input['starts_at']??'')));if($title===''||$starts==='')throw new RuntimeException('Agenda title and start time are required.');
        $id=self::uuid();$this->database->pdo()->prepare('INSERT INTO gather_agenda_items (id,event_id,title,description,starts_at,ends_at,location_label,position,status,created_at,updated_at) VALUES (:id,:event,:title,:description,:starts,:ends,:location,:position,"scheduled",UTC_TIMESTAMP(),UTC_TIMESTAMP())')->execute(['id'=>$id,'event'=>$eventId,'title'=>$title,'description'=>(string)($input['description']??''),'starts'=>$starts,'ends'=>($input['ends_at']??'')!==''?str_replace('T',' ',(string)$input['ends_at']):null,'location'=>(string)($input['location']??''),'position'=>max(0,(int)($input['position']??0))]);return $id;
    }

    public function favorite(string $agendaId,?string $accountId,string $email,int $minutes): string
    {
        $email=strtolower(trim($email));if(!$accountId&&!filter_var($email,FILTER_VALIDATE_EMAIL))throw new RuntimeException('Enter a valid email for reminders.');$token=bin2hex(random_bytes(24));$status=$minutes>0?'pending':'none';
        $this->database->pdo()->prepare('INSERT INTO gather_agenda_favorites (id,agenda_item_id,account_id,guest_email,reminder_minutes,reminder_status,unsubscribe_token_hash,created_at,updated_at) VALUES (:id,:agenda,:account,:email,:minutes,:status,:token,UTC_TIMESTAMP(),UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE reminder_minutes=VALUES(reminder_minutes),reminder_status=VALUES(reminder_status),unsubscribe_token_hash=VALUES(unsubscribe_token_hash),cancelled_at=NULL,updated_at=UTC_TIMESTAMP()')->execute(['id'=>self::uuid(),'agenda'=>$agendaId,'account'=>$accountId,'email'=>$email!==''?$email:null,'minutes'=>$minutes>0?$minutes:null,'status'=>$status,'token'=>hash('sha256',$token)]);return $token;
    }

    public function unsubscribe(string $token): void
    {
        $s=$this->database->pdo()->prepare('UPDATE gather_agenda_favorites SET reminder_status="cancelled",cancelled_at=UTC_TIMESTAMP(),updated_at=UTC_TIMESTAMP() WHERE unsubscribe_token_hash=:hash AND reminder_status IN ("pending","queued")');$s->execute(['hash'=>hash('sha256',$token)]);if($s->rowCount()!==1)throw new RuntimeException('That reminder is already cancelled or unavailable.');
    }

    public function attendeeSearch(string $accountId,string $eventId,string $query): array { $this->ownedEvent($accountId,$eventId);$q='%'.trim($query).'%';$s=$this->database->pdo()->prepare('SELECT id,guest_name,guest_email,response,party_size,status FROM gather_rsvps WHERE event_id=:event AND (guest_name LIKE :q OR guest_email LIKE :q) ORDER BY guest_name LIMIT 50');$s->execute(['event'=>$eventId,'q'=>$q]);return $s->fetchAll(); }

    public function addWalkin(string $accountId,string $eventId,array $input): string
    {
        $this->ownedEvent($accountId,$eventId);$name=trim((string)($input['name']??''));if($name==='')throw new RuntimeException('Enter the walk-in attendee name.');$email=strtolower(trim((string)($input['email']??'')));if($email!==''&&!filter_var($email,FILTER_VALIDATE_EMAIL))throw new RuntimeException('Enter a valid email address.');$id=self::uuid();$this->database->pdo()->prepare('INSERT INTO gather_walkins (id,event_id,attendee_name,attendee_email,party_count,checked_in_by_account_id,checked_in_at,note) VALUES (:id,:event,:name,:email,:party,:account,UTC_TIMESTAMP(),:note)')->execute(['id'=>$id,'event'=>$eventId,'name'=>$name,'email'=>$email?:null,'party'=>max(1,(int)($input['party_count']??1)),'account'=>$accountId,'note'=>(string)($input['note']??'')]);return $id;
    }

    public function closeEvent(string $accountId,string $eventId,string $status,string $note): void { $this->ownedEvent($accountId,$eventId);if(!in_array($status,['completed','cancelled','archived'],true))throw new RuntimeException('Choose a valid closeout state.');$this->database->pdo()->prepare('UPDATE gather_events SET lifecycle_status=:status,closed_at=UTC_TIMESTAMP(),closeout_note=:note,updated_at=UTC_TIMESTAMP() WHERE id=:id')->execute(['status'=>$status,'note'=>trim($note),'id'=>$eventId]); }

    public function proposeOutcome(string $accountId,string $eventId,string $type,string $summary): string
    {
        $s=$this->database->pdo()->prepare('SELECT id FROM gather_events WHERE id=:id');$s->execute(['id'=>$eventId]);if(!$s->fetchColumn())throw new RuntimeException('Event not found.');if(!in_array($type,['chronicle_reflection','quest_progress','journey_invitation','world_fact'],true))throw new RuntimeException('Choose a valid outcome type.');$summary=trim($summary);if($summary==='')throw new RuntimeException('Describe what this experience meant.');$id=self::uuid();$payload=json_encode(['event_id'=>$eventId,'summary'=>$summary],JSON_THROW_ON_ERROR);$this->database->pdo()->prepare('INSERT INTO gather_outcome_proposals (id,event_id,account_id,outcome_type,summary,minimized_payload_json,status,application_status,created_at,updated_at) VALUES (:id,:event,:account,:type,:summary,:payload,"proposed","not_ready",UTC_TIMESTAMP(),UTC_TIMESTAMP())')->execute(['id'=>$id,'event'=>$eventId,'account'=>$accountId,'type'=>$type,'summary'=>$summary,'payload'=>$payload]);return $id;
    }

    public function approveOutcome(string $accountId,string $id): void
    {
        $pdo=$this->database->pdo();$pdo->beginTransaction();try{$s=$pdo->prepare('SELECT * FROM gather_outcome_proposals WHERE id=:id AND account_id=:account FOR UPDATE');$s->execute(['id'=>$id,'account'=>$accountId]);$p=$s->fetch();if(!$p||$p['status']!=='proposed')throw new RuntimeException('That proposal is no longer available.');$destination=['chronicle_reflection'=>'chronicle','quest_progress'=>'quest','journey_invitation'=>'journey','world_fact'=>'world'][(string)$p['outcome_type']];$pdo->prepare('UPDATE gather_outcome_proposals SET status="approved",application_status="pending",approved_at=UTC_TIMESTAMP(),updated_at=UTC_TIMESTAMP() WHERE id=:id')->execute(['id'=>$id]);$pdo->prepare('INSERT INTO gather_outcome_applications (id,proposal_id,destination_type,status,attempts,created_at,updated_at) VALUES (:id,:proposal,:destination,"pending",0,UTC_TIMESTAMP(),UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE status=IF(status="applied",status,"pending"),updated_at=UTC_TIMESTAMP()')->execute(['id'=>self::uuid(),'proposal'=>$id,'destination'=>$destination]);$pdo->commit();}catch(\Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
    }

    public function applyApprovedOutcome(string $accountId,string $proposalId): string
    {
        $pdo=$this->database->pdo();$pdo->beginTransaction();try{$s=$pdo->prepare('SELECT p.*,a.id application_id,a.destination_type,a.status application_state FROM gather_outcome_proposals p JOIN gather_outcome_applications a ON a.proposal_id=p.id WHERE p.id=:id AND p.account_id=:account FOR UPDATE');$s->execute(['id'=>$proposalId,'account'=>$accountId]);$row=$s->fetch();if(!$row||$row['status']!=='approved')throw new RuntimeException('That approved outcome is unavailable.');if($row['application_state']==='applied')return (string)$row['application_reference'];$reference='proposal:'.$proposalId.':'.$row['destination_type'];$pdo->prepare('UPDATE gather_outcome_applications SET status="applied",attempts=attempts+1,destination_reference=:reference,applied_at=UTC_TIMESTAMP(),updated_at=UTC_TIMESTAMP() WHERE id=:id')->execute(['reference'=>$reference,'id'=>$row['application_id']]);$pdo->prepare('UPDATE gather_outcome_proposals SET status="applied",application_status="applied",application_reference=:reference,application_error=NULL,applied_at=UTC_TIMESTAMP(),updated_at=UTC_TIMESTAMP() WHERE id=:id')->execute(['reference'=>$reference,'id'=>$proposalId]);$pdo->commit();return $reference;}catch(\Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
    }

    public function queueDueReminders(int $limit=100): int
    {
        $limit=max(1,min(500,$limit));$s=$this->database->pdo()->query('SELECT f.*,a.title,a.starts_at,e.title event_title FROM gather_agenda_favorites f JOIN gather_agenda_items a ON a.id=f.agenda_item_id JOIN gather_events e ON e.id=a.event_id WHERE f.reminder_status="pending" AND f.cancelled_at IS NULL AND DATE_SUB(a.starts_at,INTERVAL f.reminder_minutes MINUTE)<=UTC_TIMESTAMP() ORDER BY a.starts_at LIMIT '.$limit);$count=0;$queue=new MailQueue($this->database);foreach($s->fetchAll() as $row){$email=(string)($row['guest_email']??'');if($email===''){$a=$this->database->pdo()->prepare('SELECT email FROM platform_accounts WHERE id=:id');$a->execute(['id'=>$row['account_id']]);$email=(string)$a->fetchColumn();}if(!filter_var($email,FILTER_VALIDATE_EMAIL))continue;$delivery=$queue->enqueue('gather.agenda_reminder',$email,'','Reminder: '.(string)$row['title'],'<p>'.htmlspecialchars((string)$row['event_title']).'</p><p>'.htmlspecialchars((string)$row['title']).' begins at '.htmlspecialchars((string)$row['starts_at']).' UTC.</p>',(string)$row['title'].' begins at '.(string)$row['starts_at'].' UTC.');$u=$this->database->pdo()->prepare('UPDATE gather_agenda_favorites SET reminder_status="queued",reminder_delivery_id=:delivery,updated_at=UTC_TIMESTAMP() WHERE id=:id AND reminder_status="pending" AND cancelled_at IS NULL');$u->execute(['delivery'=>$delivery,'id'=>$row['id']]);if($u->rowCount()===1)$count++;}return $count;
    }

    private function ownedEvent(string $accountId,string $eventId): array { return (new GatherAuthorization($this->database))->requireManage($accountId,$eventId); }
    private static function uuid(): string{$d=random_bytes(16);$d[6]=chr((ord($d[6])&0x0f)|0x40);$d[8]=chr((ord($d[8])&0x3f)|0x80);return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($d),4));}
}
