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
        $pdo=$this->database->pdo();$s=$pdo->prepare('SELECT * FROM gather_events WHERE id=:id AND account_id=:account_id');$s->execute(['id'=>$id,'account_id'=>$accountId]);$event=$s->fetch();if(!$event)return null;
        $event['rsvps']=$this->rowsBy($pdo,'SELECT * FROM gather_rsvps WHERE event_id=:id ORDER BY created_at DESC',$id);
        $event['slots']=$this->rowsBy($pdo,'SELECT * FROM gather_signup_slots WHERE event_id=:id ORDER BY slot_type,title',$id);
        foreach($event['slots'] as &$slot){$slot['commitments']=$this->rowsBy($pdo,'SELECT * FROM gather_signup_commitments WHERE slot_id=:id AND status="active" ORDER BY created_at',$slot['id']);}
        return $event;
    }

    public function createEvent(string $accountId,array $input): string
    {
        $title=trim((string)($input['title']??''));if($title==='')throw new RuntimeException('Give the event a title.');
        $starts=str_replace('T',' ',trim((string)($input['starts_at']??'')));if($starts==='')throw new RuntimeException('Choose a start time.');
        $id=self::uuid();$visibility=in_array(($input['visibility']??''),['private','unlisted','public'],true)?$input['visibility']:'unlisted';
        $this->database->pdo()->prepare('INSERT INTO gather_events (id,account_id,title,description,venue,starts_at,ends_at,timezone,visibility,status,capacity,created_at,updated_at) VALUES (:id,:account_id,:title,:description,:venue,:starts_at,:ends_at,:timezone,:visibility,"published",:capacity,UTC_TIMESTAMP(),UTC_TIMESTAMP())')->execute([
            'id'=>$id,'account_id'=>$accountId,'title'=>$title,'description'=>(string)($input['description']??''),'venue'=>(string)($input['venue']??''),'starts_at'=>$starts,'ends_at'=>($input['ends_at']??'')!==''?str_replace('T',' ',(string)$input['ends_at']):null,'timezone'=>(string)($input['timezone']??'UTC'),'visibility'=>$visibility,'capacity'=>($input['capacity']??'')!==''?(int)$input['capacity']:null
        ]);
        $this->provisionBeacon($accountId,$id,$title,$visibility);
        return $id;
    }

    public function addRsvp(string $accountId,string $eventId,string $response,int $partySize,string $note=''): void
    {
        if(!in_array($response,['yes','no','maybe'],true))throw new RuntimeException('Choose a valid RSVP response.');$event=$this->event($accountId,$eventId);if(!$event)throw new RuntimeException('Event not found.');
        $this->database->pdo()->prepare('INSERT INTO gather_rsvps (id,event_id,account_id,response,party_size,note,created_at,updated_at) VALUES (:id,:event_id,:account_id,:response,:party_size,:note,UTC_TIMESTAMP(),UTC_TIMESTAMP())')->execute(['id'=>self::uuid(),'event_id'=>$eventId,'account_id'=>$accountId,'response'=>$response,'party_size'=>max(1,$partySize),'note'=>$note]);
    }

    public function addSlot(string $accountId,string $eventId,string $type,string $title,int $quantity,?string $startsAt=null,?string $endsAt=null): void
    {
        if(!in_array($type,['shift','potluck','item','task'],true))throw new RuntimeException('Choose a valid planning tool.');if(!$this->event($accountId,$eventId))throw new RuntimeException('Event not found.');
        $this->database->pdo()->prepare('INSERT INTO gather_signup_slots (id,event_id,slot_type,title,starts_at,ends_at,quantity_needed,created_at,updated_at) VALUES (:id,:event_id,:type,:title,:starts_at,:ends_at,:quantity,UTC_TIMESTAMP(),UTC_TIMESTAMP())')->execute(['id'=>self::uuid(),'event_id'=>$eventId,'type'=>$type,'title'=>$title,'starts_at'=>$startsAt?:null,'ends_at'=>$endsAt?:null,'quantity'=>max(1,$quantity)]);
    }

    public function claimSlot(string $accountId,string $slotId,int $quantity): void
    {
        $pdo=$this->database->pdo();$s=$pdo->prepare('SELECT s.*,e.account_id FROM gather_signup_slots s JOIN gather_events e ON e.id=s.event_id WHERE s.id=:id');$s->execute(['id'=>$slotId]);$slot=$s->fetch();if(!$slot)throw new RuntimeException('Signup slot not found.');$quantity=max(1,$quantity);if(((int)$slot['quantity_claimed']+$quantity)>(int)$slot['quantity_needed'])throw new RuntimeException('That slot no longer has enough availability.');
        $this->database->transaction(function(PDO $pdo)use($accountId,$slotId,$quantity):void{$pdo->prepare('INSERT INTO gather_signup_commitments (id,slot_id,account_id,quantity,status,created_at,updated_at) VALUES (:id,:slot_id,:account_id,:quantity,"active",UTC_TIMESTAMP(),UTC_TIMESTAMP())')->execute(['id'=>self::uuid(),'slot_id'=>$slotId,'account_id'=>$accountId,'quantity'=>$quantity]);$pdo->prepare('UPDATE gather_signup_slots SET quantity_claimed=quantity_claimed+:quantity,updated_at=UTC_TIMESTAMP() WHERE id=:id')->execute(['quantity'=>$quantity,'id'=>$slotId]);});
    }

    private function provisionBeacon(string $accountId,string $eventId,string $title,string $visibility): void
    {
        $beacon=new BeaconService($this->database);$publicPath='/gather/events/'.$eventId;$base=rtrim((string)(getenv('APP_URL')?:''),'/');$destination=($base!==''?$base:'http://localhost').$publicPath;
        $linkId=$beacon->createLink($accountId,$title,$destination,'gather',$eventId);$link=$beacon->getLinkById($accountId,$linkId);$pageId=$beacon->createPage($accountId,'event_landing',$title,'Event details, RSVP, and planning signups.',['event_id'=>$eventId,'event_url'=>$destination],$visibility==='private'?'private':$visibility,'gather',$eventId);$beacon->createQr($accountId,'gather_event',$eventId,$destination,$title.' event QR');
        $this->database->pdo()->prepare('UPDATE gather_events SET beacon_short_link_id=:link_id,beacon_page_id=:page_id,updated_at=UTC_TIMESTAMP() WHERE id=:id')->execute(['link_id'=>$linkId,'page_id'=>$pageId,'id'=>$eventId]);
    }

    private function rows(PDO $pdo,string $sql,string $accountId):array{$s=$pdo->prepare($sql);$s->execute(['account_id'=>$accountId]);return $s->fetchAll();}
    private function rowsBy(PDO $pdo,string $sql,string $id):array{$s=$pdo->prepare($sql);$s->execute(['id'=>$id]);return $s->fetchAll();}
    private static function uuid():string{$d=random_bytes(16);$d[6]=chr((ord($d[6])&0x0f)|0x40);$d[8]=chr((ord($d[8])&0x3f)|0x80);return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($d),4));}
}
