<?php

declare(strict_types=1);

namespace Koravik\Districts\Gather;

use Koravik\Districts\Beacon\BeaconService;
use Koravik\Platform\Database\Database;
use PDO;
use RuntimeException;

final class GatherService
{
    public function __construct(private readonly Database $database) {}

    public function dashboard(string $accountId): array
    {
        return $this->rows($this->database->pdo(),'SELECT * FROM gather_events WHERE account_id=:account_id ORDER BY starts_at DESC',$accountId);
    }

    public function event(string $accountId,string $id): ?array
    {
        $pdo=$this->database->pdo();$s=$pdo->prepare('SELECT e.*,o.public_name organization_public_name,o.name organization_name,o.summary organization_summary,o.brand_color organization_brand_color,o.contact_email organization_contact_email FROM gather_events e LEFT JOIN organizations o ON o.id=e.organization_id WHERE e.id=:id AND e.account_id=:account_id');$s->execute(['id'=>$id,'account_id'=>$accountId]);$event=$s->fetch();if(!$event)return null;
        return $this->hydrateEvent($pdo,$event);
    }

    public function publicEvent(string $id): ?array
    {
        $pdo=$this->database->pdo();$s=$pdo->prepare('SELECT e.*,o.public_name organization_public_name,o.name organization_name,o.summary organization_summary,o.brand_color organization_brand_color,o.contact_email organization_contact_email FROM gather_events e LEFT JOIN organizations o ON o.id=e.organization_id WHERE e.id=:id AND e.status="published" AND e.visibility IN ("unlisted","public")');$s->execute(['id'=>$id]);$event=$s->fetch();return $event?$this->hydrateEvent($pdo,$event):null;
    }

    public function managedRsvp(string $token): ?array
    {
        $hash=hash('sha256',$token);$pdo=$this->database->pdo();$s=$pdo->prepare('SELECT r.*,e.title,e.starts_at,e.venue,e.additional_guests_enabled,e.max_additional_guests FROM gather_rsvps r JOIN gather_events e ON e.id=r.event_id WHERE r.management_token_hash=:hash AND r.management_token_revoked_at IS NULL');$s->execute(['hash'=>$hash]);$r=$s->fetch();if(!$r)return null;$r['guests']=$this->rowsBy($pdo,'SELECT * FROM gather_rsvp_guests WHERE rsvp_id=:id ORDER BY created_at',$r['id']);return $r;
    }

    public function createEvent(string $accountId,array $input): string
    {
        $title=trim((string)($input['title']??''));if($title==='')throw new RuntimeException('Give the event a title.');
        $starts=str_replace('T',' ',trim((string)($input['starts_at']??'')));if($starts==='')throw new RuntimeException('Choose a start time.');
        $id=self::uuid();$visibility=in_array(($input['visibility']??''),['restricted','unlisted','public'],true)?$input['visibility']:'unlisted';$additional=isset($input['additional_guests_enabled'])?1:0;$maxAdditional=$additional?max(0,(int)($input['max_additional_guests']??0)):0;
        $accent=preg_match('/^#[0-9a-fA-F]{6}$/',(string)($input['event_accent_color']??''))?strtoupper((string)$input['event_accent_color']):null;
        $headerStyle=in_array(($input['event_header_style']??''),['classic','forest','gold','navy','custom'],true)?(string)$input['event_header_style']:'classic';
        $this->database->pdo()->prepare('INSERT INTO gather_events (id,account_id,title,description,venue,starts_at,ends_at,timezone,visibility,status,capacity,guest_registration_enabled,additional_guests_enabled,max_additional_guests,waitlist_enabled,automatic_waitlist_promotion,event_accent_color,event_header_style,created_at,updated_at) VALUES (:id,:account_id,:title,:description,:venue,:starts_at,:ends_at,:timezone,:visibility,"published",:capacity,:guest_registration,:additional,:max_additional,:waitlist,:auto_promote,:accent,:header_style,UTC_TIMESTAMP(),UTC_TIMESTAMP())')->execute([
            'id'=>$id,'account_id'=>$accountId,'title'=>$title,'description'=>(string)($input['description']??''),'venue'=>(string)($input['venue']??''),'starts_at'=>$starts,'ends_at'=>($input['ends_at']??'')!==''?str_replace('T',' ',(string)$input['ends_at']):null,'timezone'=>(string)($input['timezone']??'UTC'),'visibility'=>$visibility,'capacity'=>($input['capacity']??'')!==''?(int)$input['capacity']:null,'guest_registration'=>isset($input['guest_registration_enabled'])?1:0,'additional'=>$additional,'max_additional'=>$maxAdditional,'waitlist'=>isset($input['waitlist_enabled'])?1:0,'auto_promote'=>isset($input['automatic_waitlist_promotion'])?1:0,'accent'=>$accent,'header_style'=>$headerStyle
        ]);
        $this->provisionBeacon($accountId,$id,$title,$visibility);return $id;
    }

    public function registerGuest(string $eventId,array $input): string
    {
        $event=$this->publicEvent($eventId);if(!$event||!(int)$event['guest_registration_enabled'])throw new RuntimeException('Guest registration is not available for this event.');
        $name=trim((string)($input['guest_name']??''));$email=strtolower(trim((string)($input['guest_email']??'')));if($name===''||!filter_var($email,FILTER_VALIDATE_EMAIL))throw new RuntimeException('Enter your name and a valid email address.');
        $additional=max(0,(int)($input['additional_guests']??0));if(!(int)$event['additional_guests_enabled'])$additional=0;if($additional>(int)$event['max_additional_guests'])throw new RuntimeException('This event allows fewer additional guests.');$partySize=1+$additional;
        $response=(string)($input['response']??'yes');if(!in_array($response,['yes','no','maybe'],true))throw new RuntimeException('Choose a valid RSVP response.');
        $status=$response;if($response==='yes'&&$event['capacity']!==null&&$this->confirmedAttendance($eventId)+$partySize>(int)$event['capacity']){$status=(int)$event['waitlist_enabled']?'waitlist':throw new RuntimeException('This event is full.');}
        $token=self::token();$id=self::uuid();$position=$status==='waitlist'?$this->nextWaitlistPosition($eventId):null;
        $this->database->transaction(function(PDO $pdo)use($id,$eventId,$name,$email,$status,$partySize,$input,$token,$position,$additional):void{
            $pdo->prepare('INSERT INTO gather_rsvps (id,event_id,account_id,guest_name,guest_email,response,party_size,note,management_token_hash,management_token_created_at,waitlist_position,created_at,updated_at) VALUES (:id,:event_id,NULL,:name,:email,:response,:party_size,:note,:token_hash,UTC_TIMESTAMP(),:position,UTC_TIMESTAMP(),UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE guest_name=VALUES(guest_name),response=VALUES(response),party_size=VALUES(party_size),note=VALUES(note),management_token_hash=VALUES(management_token_hash),management_token_created_at=UTC_TIMESTAMP(),management_token_revoked_at=NULL,waitlist_position=VALUES(waitlist_position),updated_at=UTC_TIMESTAMP()')->execute(['id'=>$id,'event_id'=>$eventId,'name'=>$name,'email'=>$email,'response'=>$status,'party_size'=>$partySize,'note'=>(string)($input['note']??''),'token_hash'=>hash('sha256',$token),'position'=>$position]);
            $s=$pdo->prepare('SELECT id FROM gather_rsvps WHERE event_id=:event_id AND guest_email=:email');$s->execute(['event_id'=>$eventId,'email'=>$email]);$rsvpId=(string)$s->fetchColumn();$pdo->prepare('DELETE FROM gather_rsvp_guests WHERE rsvp_id=:id')->execute(['id'=>$rsvpId]);
            for($i=1;$i<=$additional;$i++){$guestName=trim((string)($input['additional_guest_names'][$i-1]??''));$pdo->prepare('INSERT INTO gather_rsvp_guests (id,rsvp_id,guest_name,guest_type,created_at) VALUES (:id,:rsvp_id,:name,"unspecified",UTC_TIMESTAMP())')->execute(['id'=>self::uuid(),'rsvp_id'=>$rsvpId,'name'=>$guestName!==''?$guestName:null]);}
            $pdo->prepare('INSERT INTO gather_management_link_deliveries (id,email,rsvp_id,token_hash,delivery_status,requested_at) VALUES (:id,:email,:rsvp_id,:hash,"pending",UTC_TIMESTAMP())')->execute(['id'=>self::uuid(),'email'=>$email,'rsvp_id'=>$rsvpId,'hash'=>hash('sha256',$token)]);
        });
        return $token;
    }

    public function requestManagementLinks(string $email): void
    {
        $email=strtolower(trim($email));if(!filter_var($email,FILTER_VALIDATE_EMAIL))return;$pdo=$this->database->pdo();$s=$pdo->prepare('SELECT id FROM gather_rsvps WHERE guest_email=:email AND management_token_revoked_at IS NULL');$s->execute(['email'=>$email]);foreach($s->fetchAll() as $row){$token=self::token();$hash=hash('sha256',$token);$pdo->prepare('UPDATE gather_rsvps SET management_token_hash=:hash,management_token_created_at=UTC_TIMESTAMP() WHERE id=:id')->execute(['hash'=>$hash,'id'=>$row['id']]);$pdo->prepare('INSERT INTO gather_management_link_deliveries (id,email,rsvp_id,token_hash,delivery_status,requested_at) VALUES (:id,:email,:rsvp_id,:hash,"pending",UTC_TIMESTAMP())')->execute(['id'=>self::uuid(),'email'=>$email,'rsvp_id'=>$row['id'],'hash'=>$hash]);}
    }

    public function updateManagedRsvp(string $token,array $input): void
    {
        $r=$this->managedRsvp($token);if(!$r)throw new RuntimeException('That management link is invalid or expired.');$response=(string)($input['response']??'yes');if(!in_array($response,['yes','no','maybe'],true))throw new RuntimeException('Choose a valid RSVP response.');$additional=max(0,(int)($input['additional_guests']??0));if(!(int)$r['additional_guests_enabled'])$additional=0;if($additional>(int)$r['max_additional_guests'])throw new RuntimeException('This event allows fewer additional guests.');$this->database->pdo()->prepare('UPDATE gather_rsvps SET response=:response,party_size=:party_size,note=:note,updated_at=UTC_TIMESTAMP() WHERE id=:id')->execute(['response'=>$response,'party_size'=>1+$additional,'note'=>(string)($input['note']??''),'id'=>$r['id']]);
    }

    public function addSlot(string $accountId,string $eventId,string $type,string $title,int $quantity,array $rules=[]): void
    {
        if(!in_array($type,['shift','potluck','item','task'],true))throw new RuntimeException('Choose a valid planning tool.');if(!$this->event($accountId,$eventId))throw new RuntimeException('Event not found.');
        $category=$this->categoryKey((string)($rules['category_key']??$type));
        $this->database->pdo()->prepare('INSERT INTO gather_signup_slots (id,event_id,slot_type,category_key,title,description,starts_at,ends_at,quantity_needed,multiple_signups_allowed,max_signups_per_participant,waitlist_enabled,overlapping_shifts_allowed,max_quantity_per_commitment,require_attending_rsvp,created_at,updated_at) VALUES (:id,:event_id,:type,:category,:title,:description,:starts_at,:ends_at,:quantity,:multiple,:maximum,:waitlist,:overlap,:max_quantity,:require_rsvp,UTC_TIMESTAMP(),UTC_TIMESTAMP())')->execute(['id'=>self::uuid(),'event_id'=>$eventId,'type'=>$type,'category'=>$category,'title'=>$title,'description'=>trim((string)($rules['description']??''))?:null,'starts_at'=>($rules['starts_at']??'')?:null,'ends_at'=>($rules['ends_at']??'')?:null,'quantity'=>max(1,$quantity),'multiple'=>isset($rules['multiple_signups_allowed'])?1:0,'maximum'=>($rules['max_signups_per_participant']??'')!==''?max(1,(int)$rules['max_signups_per_participant']):null,'waitlist'=>isset($rules['waitlist_enabled'])?1:0,'overlap'=>isset($rules['overlapping_shifts_allowed'])?1:0,'max_quantity'=>($rules['max_quantity_per_commitment']??'')!==''?max(1,(int)$rules['max_quantity_per_commitment']):null,'require_rsvp'=>1]);
    }

    public function claimSlot(string $slotId,array $participant,int $quantity): string
    {
        $pdo=$this->database->pdo();$s=$pdo->prepare('SELECT s.* FROM gather_signup_slots s WHERE s.id=:id');$s->execute(['id'=>$slotId]);$slot=$s->fetch();if(!$slot)throw new RuntimeException('Signup slot not found.');$quantity=max(1,$quantity);$email=strtolower(trim((string)($participant['email']??'')));$accountId=$participant['account_id']??null;if($email===''&&!$accountId)throw new RuntimeException('A participant identity is required.');
        if($slot['max_quantity_per_commitment']!==null&&$quantity>(int)$slot['max_quantity_per_commitment'])throw new RuntimeException('That quantity exceeds the slot limit.');
        $rsvp=$pdo->prepare('SELECT id FROM gather_rsvps WHERE event_id=:event AND status="active" AND response="yes" AND ((guest_email=:email AND :email<>"") OR (account_id IS NOT NULL AND account_id=:account_id)) LIMIT 1');$rsvp->execute(['event'=>$slot['event_id'],'email'=>$email?:'','account_id'=>$accountId]);$rsvpId=$rsvp->fetchColumn();if(!$rsvpId)throw new RuntimeException('RSVP yes before claiming this signup.');$participant['rsvp_id']=(string)$rsvpId;
        $count=$pdo->prepare('SELECT COUNT(*) FROM gather_signup_commitments WHERE slot_id=:slot_id AND status IN ("active","waitlist") AND ((participant_email IS NOT NULL AND participant_email=:email) OR (account_id IS NOT NULL AND account_id=:account_id))');$count->execute(['slot_id'=>$slotId,'email'=>$email?:'','account_id'=>$accountId]);$existing=(int)$count->fetchColumn();if(!(int)$slot['multiple_signups_allowed']&&$existing>0)throw new RuntimeException('This signup allows only one commitment per participant.');if($slot['max_signups_per_participant']!==null&&$existing>=(int)$slot['max_signups_per_participant'])throw new RuntimeException('You have reached the signup limit for this item.');
        $status=((int)$slot['quantity_claimed']+$quantity)>(int)$slot['quantity_needed']?((int)$slot['waitlist_enabled']?'waitlist':throw new RuntimeException('That signup is full.')):'active';$position=$status==='waitlist'?$this->nextSlotWaitlistPosition($slotId):null;
        $this->database->transaction(function(PDO $pdo)use($slotId,$accountId,$email,$participant,$quantity,$status,$position):void{$pdo->prepare('INSERT INTO gather_signup_commitments (id,slot_id,account_id,rsvp_id,participant_name,participant_email,quantity,status,note,waitlist_position,created_at,updated_at) VALUES (:id,:slot_id,:account_id,:rsvp_id,:name,:email,:quantity,:status,:note,:position,UTC_TIMESTAMP(),UTC_TIMESTAMP())')->execute(['id'=>self::uuid(),'slot_id'=>$slotId,'account_id'=>$accountId,'rsvp_id'=>$participant['rsvp_id']??null,'name'=>$participant['name']??null,'email'=>$email?:null,'quantity'=>$quantity,'status'=>$status,'note'=>$participant['note']??null,'position'=>$position]);if($status==='active')$pdo->prepare('UPDATE gather_signup_slots SET quantity_claimed=quantity_claimed+:quantity,updated_at=UTC_TIMESTAMP() WHERE id=:id')->execute(['quantity'=>$quantity,'id'=>$slotId]);});return $status;
    }

    private function hydrateEvent(PDO $pdo,array $event): array{$event['rsvps']=$this->rowsBy($pdo,'SELECT * FROM gather_rsvps WHERE event_id=:id ORDER BY created_at DESC',$event['id']);$event['slots']=$this->rowsBy($pdo,'SELECT * FROM gather_signup_slots WHERE event_id=:id ORDER BY FIELD(COALESCE(category_key,slot_type),"food","shift","equipment","supplies","setup","cleanup","transport","childcare","accessibility","other"),slot_type,title',$event['id']);foreach($event['slots'] as &$slot){$slot['category_label']=$this->categoryLabel((string)($slot['category_key']?:$slot['slot_type']));$slot['commitments']=$this->rowsBy($pdo,'SELECT * FROM gather_signup_commitments WHERE slot_id=:id AND status IN ("active","waitlist") ORDER BY status,waitlist_position,created_at',$slot['id']);}return $event;}
    private function categoryKey(string $value): string{$key=preg_replace('/[^a-z0-9_]+/','_',strtolower(trim($value)))?:'other';return in_array($key,['food','shift','equipment','supplies','setup','cleanup','transport','childcare','accessibility','other','potluck','item','task'],true)?match($key){'potluck'=>'food','item'=>'supplies','task'=>'other',default=>$key}:$key;}
    private function categoryLabel(string $key): string{return match($this->categoryKey($key)){'food'=>'Food and potluck','shift'=>'Volunteer shifts','equipment'=>'Equipment and gear','supplies'=>'Supplies and materials','setup'=>'Setup crew','cleanup'=>'Cleanup crew','transport'=>'Transportation','childcare'=>'Childcare and care roles','accessibility'=>'Accessibility support',default=>'Other needs'};}
    private function confirmedAttendance(string $eventId):int{$s=$this->database->pdo()->prepare('SELECT COALESCE(SUM(party_size),0) FROM gather_rsvps WHERE event_id=:id AND response="yes"');$s->execute(['id'=>$eventId]);return(int)$s->fetchColumn();}
    private function nextWaitlistPosition(string $eventId):int{$s=$this->database->pdo()->prepare('SELECT COALESCE(MAX(waitlist_position),0)+1 FROM gather_rsvps WHERE event_id=:id AND response="waitlist"');$s->execute(['id'=>$eventId]);return(int)$s->fetchColumn();}
    private function nextSlotWaitlistPosition(string $slotId):int{$s=$this->database->pdo()->prepare('SELECT COALESCE(MAX(waitlist_position),0)+1 FROM gather_signup_commitments WHERE slot_id=:id AND status="waitlist"');$s->execute(['id'=>$slotId]);return(int)$s->fetchColumn();}
    private function provisionBeacon(string $accountId,string $eventId,string $title,string $visibility): void{$beacon=new BeaconService($this->database);$publicPath='/gather/events/'.$eventId;$base=rtrim((string)(getenv('APP_URL')?:''),'/');$destination=($base!==''?$base:'http://localhost').$publicPath;$linkId=$beacon->createLink($accountId,$title,$destination,'gather',$eventId);$pageId=$beacon->createPage($accountId,'event_landing',$title,'Event details, RSVP, and planning signups.',['event_id'=>$eventId,'event_url'=>$destination],$visibility==='restricted'?'private':$visibility,'gather',$eventId);$beacon->createQr($accountId,'gather_event',$eventId,$destination,$title.' event QR');$this->database->pdo()->prepare('UPDATE gather_events SET beacon_short_link_id=:link_id,beacon_page_id=:page_id,updated_at=UTC_TIMESTAMP() WHERE id=:id')->execute(['link_id'=>$linkId,'page_id'=>$pageId,'id'=>$eventId]);}
    private function rows(PDO $pdo,string $sql,string $accountId):array{$s=$pdo->prepare($sql);$s->execute(['account_id'=>$accountId]);return $s->fetchAll();}
    private function rowsBy(PDO $pdo,string $sql,string $id):array{$s=$pdo->prepare($sql);$s->execute(['id'=>$id]);return $s->fetchAll();}
    private static function token():string{return rtrim(strtr(base64_encode(random_bytes(32)),'+/','-_'),'=');}
    private static function uuid():string{$d=random_bytes(16);$d[6]=chr((ord($d[6])&0x0f)|0x40);$d[8]=chr((ord($d[8])&0x3f)|0x80);return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($d),4));}
}
