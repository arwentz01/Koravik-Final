<?php

declare(strict_types=1);

namespace Koravik\Districts\Gather;

use Koravik\Platform\Database\Database;
use Koravik\Platform\Mail\MailQueue;
use PDO;
use RuntimeException;

final class GatherWorkflowService
{
    public function __construct(private readonly Database $database) {}

    public function grantAccess(string $ownerId,string $eventId,string $type,string $reference):void
    {
        if(!in_array($type,['email','account','friend','organization','household'],true))throw new RuntimeException('Unsupported access grant.');
        $this->assertOwner($ownerId,$eventId);$reference=$type==='email'?strtolower(trim($reference)):trim($reference);
        $this->database->pdo()->prepare('INSERT IGNORE INTO gather_event_access_grants (id,event_id,grant_type,grant_reference,created_by_account_id,created_at) VALUES (:id,:event,:type,:reference,:owner,UTC_TIMESTAMP())')->execute(['id'=>self::uuid(),'event'=>$eventId,'type'=>$type,'reference'=>$reference,'owner'=>$ownerId]);
    }

    public function canAccess(array $event,?string $accountId,?string $email):bool
    {
        if(in_array($event['visibility'],['public','unlisted'],true))return true;if($accountId!==null&&$accountId===$event['account_id'])return true;
        $conditions=[];$params=['event'=>$event['id']];if($accountId){$conditions[]='(grant_type="account" AND grant_reference=:account)';$params['account']=$accountId;}if($email){$conditions[]='(grant_type="email" AND grant_reference=:email)';$params['email']=strtolower(trim($email));}
        if($conditions===[])return false;$s=$this->database->pdo()->prepare('SELECT 1 FROM gather_event_access_grants WHERE event_id=:event AND ('.implode(' OR ',$conditions).') LIMIT 1');$s->execute($params);return (bool)$s->fetchColumn();
    }

    public function manageByToken(string $token):?array
    {
        $pdo=$this->database->pdo();$s=$pdo->prepare('SELECT r.*,e.title,e.description,e.starts_at,e.ends_at,e.venue,e.capacity,e.status event_status,e.lifecycle_status,e.additional_guests_enabled,e.max_additional_guests FROM gather_rsvps r JOIN gather_events e ON e.id=r.event_id WHERE r.management_token_hash=:hash AND r.management_token_revoked_at IS NULL AND (r.management_token_expires_at IS NULL OR r.management_token_expires_at>UTC_TIMESTAMP()) LIMIT 1');$s->execute(['hash'=>hash('sha256',$token)]);$row=$s->fetch();if(!$row)return null;
        $g=$pdo->prepare('SELECT id,guest_name,guest_email FROM gather_rsvp_guests WHERE rsvp_id=:id ORDER BY created_at');$g->execute(['id'=>$row['id']]);$row['guests']=$g->fetchAll();$slots=$pdo->prepare('SELECT s.*,c.id commitment_id,c.quantity commitment_quantity,c.status commitment_status,c.waitlist_position commitment_waitlist_position FROM gather_signup_slots s LEFT JOIN gather_signup_commitments c ON c.slot_id=s.id AND c.rsvp_id=:rsvp AND c.status IN ("active","waitlist") WHERE s.event_id=:event ORDER BY FIELD(COALESCE(s.category_key,s.slot_type),"food","shift","equipment","supplies","setup","cleanup","transport","childcare","accessibility","other"),s.slot_type,s.starts_at,s.title');$slots->execute(['rsvp'=>$row['id'],'event'=>$row['event_id']]);$row['slots']=array_map(fn(array $slot):array=>$slot+['category_label'=>$this->categoryLabel((string)($slot['category_key']?:$slot['slot_type']))],$slots->fetchAll());$checkin=$pdo->prepare('SELECT party_count,checked_in_at,source FROM gather_checkins WHERE event_id=:event AND rsvp_id=:rsvp AND corrected_at IS NULL LIMIT 1');$checkin->execute(['event'=>$row['event_id'],'rsvp'=>$row['id']]);$row['checkin']=$checkin->fetch()?:null;return $row;
    }

    public function updateRsvp(string $token,string $response,int $partySize,string $note,array $guestNames=[]):void
    {
        if(!in_array($response,['yes','no','maybe','waitlist'],true))throw new RuntimeException('Choose a valid response.');$r=$this->manageByToken($token);if(!$r)throw new RuntimeException('That management link is invalid or expired.');
        $partySize=max(1,$partySize);$max=1+((int)$r['additional_guests_enabled']===1?(int)$r['max_additional_guests']:0);if($partySize>$max)throw new RuntimeException('That party exceeds the event limit.');
        $this->database->transaction(function(PDO $pdo)use($r,$response,$partySize,$note,$guestNames):void{$pdo->prepare('UPDATE gather_rsvps SET response=:response,party_size=:party,note=:note,status="active",updated_at=UTC_TIMESTAMP() WHERE id=:id')->execute(['response'=>$response,'party'=>$partySize,'note'=>$note,'id'=>$r['id']]);$pdo->prepare('DELETE FROM gather_rsvp_guests WHERE rsvp_id=:id')->execute(['id'=>$r['id']]);foreach(array_slice(array_filter(array_map('trim',$guestNames)),0,$partySize-1) as $name){$pdo->prepare('INSERT INTO gather_rsvp_guests (id,rsvp_id,guest_name,created_at,updated_at) VALUES (:id,:rsvp,:name,UTC_TIMESTAMP(),UTC_TIMESTAMP())')->execute(['id'=>self::uuid(),'rsvp'=>$r['id'],'name'=>$name]);}});
    }

    public function cancelRsvp(string $token):void
    {
        $r=$this->manageByToken($token);if(!$r)throw new RuntimeException('That management link is invalid or expired.');$this->database->transaction(function(PDO $pdo)use($r):void{$pdo->prepare('UPDATE gather_rsvps SET status="cancelled",response="no",management_token_revoked_at=UTC_TIMESTAMP(),updated_at=UTC_TIMESTAMP() WHERE id=:id')->execute(['id'=>$r['id']]);$pdo->prepare('UPDATE gather_signup_commitments SET status="cancelled",updated_at=UTC_TIMESTAMP() WHERE rsvp_id=:id AND status IN ("active","waitlist")')->execute(['id'=>$r['id']]);});$this->promoteEventWaitlist((string)$r['event_id']);
    }

    public function promoteEventWaitlist(string $eventId):?string
    {
        $pdo=$this->database->pdo();$e=$pdo->prepare('SELECT * FROM gather_events WHERE id=:id');$e->execute(['id'=>$eventId]);$event=$e->fetch();if(!$event||!$event['capacity'])return null;
        $used=$pdo->prepare('SELECT COALESCE(SUM(party_size),0) FROM gather_rsvps WHERE event_id=:id AND status="active" AND response="yes"');$used->execute(['id'=>$eventId]);$available=(int)$event['capacity']-(int)$used->fetchColumn();if($available<1)return null;
        $w=$pdo->prepare('SELECT * FROM gather_rsvps WHERE event_id=:id AND status="active" AND response="waitlist" AND party_size<=:available ORDER BY waitlist_position,created_at LIMIT 1');$w->execute(['id'=>$eventId,'available'=>$available]);$r=$w->fetch();if(!$r)return null;
        $minutes=max(15,(int)$event['waitlist_offer_minutes']);$pdo->prepare('UPDATE gather_rsvps SET waitlist_offer_status="offered",waitlist_offer_expires_at=DATE_ADD(UTC_TIMESTAMP(),INTERVAL :minutes MINUTE),promoted_at=UTC_TIMESTAMP(),updated_at=UTC_TIMESTAMP() WHERE id=:id')->execute(['minutes'=>$minutes,'id'=>$r['id']]);$this->queuePromotionMail($r,$event);return (string)$r['id'];
    }

    public function claimSlot(string $slotId,string $rsvpId,int $quantity):string
    {
        $pdo=$this->database->pdo();$s=$pdo->prepare('SELECT s.*,e.max_signups_per_participant FROM gather_signup_slots s JOIN gather_events e ON e.id=s.event_id WHERE s.id=:id');$s->execute(['id'=>$slotId]);$slot=$s->fetch();if(!$slot)throw new RuntimeException('Signup slot not found.');$r=$pdo->prepare('SELECT * FROM gather_rsvps WHERE id=:id AND event_id=:event AND status="active"');$r->execute(['id'=>$rsvpId,'event'=>$slot['event_id']]);$rsvp=$r->fetch();if(!$rsvp)throw new RuntimeException('An active RSVP is required.');if($rsvp['response']!=='yes')throw new RuntimeException('Confirm attendance before claiming this signup.');
        $quantity=max(1,$quantity);if($slot['max_quantity_per_commitment']!==null&&$quantity>(int)$slot['max_quantity_per_commitment'])throw new RuntimeException('That quantity exceeds the slot limit.');
        $count=$pdo->prepare('SELECT COUNT(*) FROM gather_signup_commitments c JOIN gather_signup_slots s ON s.id=c.slot_id WHERE s.event_id=:event AND c.rsvp_id=:rsvp AND c.status IN ("active","waitlist")');$count->execute(['event'=>$slot['event_id'],'rsvp'=>$rsvpId]);$total=(int)$count->fetchColumn();$max=$slot['max_signups_per_participant']??$slot['max_signups_per_participant'];if($max!==null&&$total>=(int)$max)throw new RuntimeException('You have reached the signup limit for this event.');if(!(bool)$slot['multiple_signups_allowed']&&$total>0)throw new RuntimeException('Only one signup is allowed.');
        if($slot['slot_type']==='shift'&&!(bool)$slot['overlapping_shifts_allowed']&&$slot['starts_at']&&$slot['ends_at']){$o=$pdo->prepare('SELECT 1 FROM gather_signup_commitments c JOIN gather_signup_slots s ON s.id=c.slot_id WHERE c.rsvp_id=:rsvp AND c.status="active" AND s.slot_type="shift" AND s.starts_at<:ends AND s.ends_at>:starts LIMIT 1');$o->execute(['rsvp'=>$rsvpId,'ends'=>$slot['ends_at'],'starts'=>$slot['starts_at']]);if($o->fetchColumn())throw new RuntimeException('That shift overlaps another active shift.');}
        $status=((int)$slot['quantity_claimed']+$quantity<=(int)$slot['quantity_needed'])?'active':((int)$slot['waitlist_enabled']===1?'waitlist':'');if($status==='')throw new RuntimeException('That signup is full.');$position=null;if($status==='waitlist'){$p=$pdo->prepare('SELECT COALESCE(MAX(waitlist_position),0)+1 FROM gather_signup_commitments WHERE slot_id=:id AND status="waitlist"');$p->execute(['id'=>$slotId]);$position=(int)$p->fetchColumn();}
        $id=self::uuid();$pdo->prepare('INSERT INTO gather_signup_commitments (id,slot_id,rsvp_id,participant_name,participant_email,quantity,status,waitlist_position,created_at,updated_at) VALUES (:id,:slot,:rsvp,:name,:email,:quantity,:status,:position,UTC_TIMESTAMP(),UTC_TIMESTAMP())')->execute(['id'=>$id,'slot'=>$slotId,'rsvp'=>$rsvpId,'name'=>$rsvp['guest_name'],'email'=>$rsvp['guest_email'],'quantity'=>$quantity,'status'=>$status,'position'=>$position]);if($status==='active')$pdo->prepare('UPDATE gather_signup_slots SET quantity_claimed=quantity_claimed+:quantity,updated_at=UTC_TIMESTAMP() WHERE id=:id')->execute(['quantity'=>$quantity,'id'=>$slotId]);return $status;
    }

    public function cancelCommitment(string $token,string $commitmentId):void
    {
        $r=$this->manageByToken($token);if(!$r)throw new RuntimeException('That management link is invalid or expired.');$this->database->transaction(function(PDO $pdo)use($r,$commitmentId):void{$s=$pdo->prepare('SELECT c.id,c.quantity,c.status,c.slot_id FROM gather_signup_commitments c JOIN gather_signup_slots gs ON gs.id=c.slot_id WHERE c.id=:id AND c.rsvp_id=:rsvp AND gs.event_id=:event AND c.status IN ("active","waitlist") FOR UPDATE');$s->execute(['id'=>$commitmentId,'rsvp'=>$r['id'],'event'=>$r['event_id']]);$c=$s->fetch();if(!$c)throw new RuntimeException('That signup commitment is unavailable.');$pdo->prepare('UPDATE gather_signup_commitments SET status="cancelled",updated_at=UTC_TIMESTAMP() WHERE id=:id')->execute(['id'=>$commitmentId]);if($c['status']==='active')$pdo->prepare('UPDATE gather_signup_slots SET quantity_claimed=GREATEST(0,quantity_claimed-:quantity),updated_at=UTC_TIMESTAMP() WHERE id=:id')->execute(['quantity'=>$c['quantity'],'id'=>$c['slot_id']]);});
    }

    private function queuePromotionMail(array $r,array $event):void{$base=rtrim((string)(getenv('APP_URL')?:''),'/');$url=$base.'/gather/rsvp/manage/REISSUE-LINK';$subject='A place opened for '.$event['title'];$text='A place is available for your party. Use your RSVP management link to accept before the offer expires.';$html='<p>A place is available for your party for <strong>'.htmlspecialchars($event['title'],ENT_QUOTES).'</strong>.</p><p>Use your RSVP management link to accept before the offer expires.</p>';(new MailQueue($this->database))->enqueue('gather.waitlist.offer',(string)$r['guest_email'],(string)$r['guest_name'],$subject,$html,$text,$event['organizer_reply_to_email']??null,'Event organizer');}
    private function categoryLabel(string $key):string{$normalized=preg_replace('/[^a-z0-9_]+/','_',strtolower(trim($key)))?:'other';return match($normalized){'food','potluck'=>'Food and potluck','shift'=>'Volunteer shifts','equipment'=>'Equipment and gear','supplies','item'=>'Supplies and materials','setup'=>'Setup crew','cleanup'=>'Cleanup crew','transport'=>'Transportation','childcare'=>'Childcare and care roles','accessibility'=>'Accessibility support',default=>'Other needs'};}
    private function assertOwner(string $ownerId,string $eventId):void{(new GatherAuthorization($this->database))->requireManage($ownerId,$eventId);}
    private static function uuid():string{$d=random_bytes(16);$d[6]=chr((ord($d[6])&0x0f)|0x40);$d[8]=chr((ord($d[8])&0x3f)|0x80);return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($d),4));}
}
