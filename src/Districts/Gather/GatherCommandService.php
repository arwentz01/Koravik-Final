<?php

declare(strict_types=1);

namespace Koravik\Districts\Gather;

use Koravik\Platform\Database\Database;
use PDO;
use RuntimeException;

final class GatherCommandService
{
    public function __construct(private readonly Database $database) {}

    public function dashboard(string $ownerId,string $eventId):array
    {
        $event=$this->ownedEvent($ownerId,$eventId);$pdo=$this->database->pdo();
        $event['confirmed']=$this->scalar($pdo,'SELECT COALESCE(SUM(party_size),0) FROM gather_rsvps WHERE event_id=:id AND status="active" AND response="yes"',$eventId);
        $event['waitlisted']=$this->scalar($pdo,'SELECT COALESCE(SUM(party_size),0) FROM gather_rsvps WHERE event_id=:id AND status="active" AND response="waitlist"',$eventId);
        $event['checked_in']=$this->scalar($pdo,'SELECT COALESCE(SUM(party_count),0) FROM gather_checkins WHERE event_id=:id AND corrected_at IS NULL',$eventId);
        $event['open_signup_units']=$this->scalar($pdo,'SELECT COALESCE(SUM(GREATEST(quantity_needed-quantity_claimed,0)),0) FROM gather_signup_slots WHERE event_id=:id',$eventId);
        $event['rsvps']=$this->rows($pdo,'SELECT * FROM gather_rsvps WHERE event_id=:id ORDER BY FIELD(response,"yes","maybe","waitlist","no"),created_at',$eventId);
        $event['slots']=$this->rows($pdo,'SELECT * FROM gather_signup_slots WHERE event_id=:id ORDER BY slot_type,title',$eventId);
        $event['mail']=$this->rows($pdo,'SELECT id,recipient_email,subject,status,attempts,failure_reason,created_at,sent_at FROM platform_mail_deliveries WHERE event_id=:id ORDER BY created_at DESC LIMIT 30',$eventId);
        return $event;
    }

    public function updateSettings(string $ownerId,string $eventId,array $input):void
    {
        $this->ownedEvent($ownerId,$eventId);$visibility=in_array(($input['visibility']??''),['unlisted','public','restricted'],true)?$input['visibility']:'unlisted';
        $this->database->pdo()->prepare('UPDATE gather_events SET visibility=:visibility,capacity=:capacity,guest_registration_enabled=:guest,additional_guests_enabled=:additional,max_additional_guests=:max_guests,waitlist_enabled=:waitlist,automatic_waitlist_promotion=:auto,max_signups_per_participant=:max_signups,waitlist_offer_minutes=:offer,organizer_reply_to_email=:reply,updated_at=UTC_TIMESTAMP() WHERE id=:id')->execute([
            'visibility'=>$visibility,'capacity'=>($input['capacity']??'')!==''?max(1,(int)$input['capacity']):null,'guest'=>isset($input['guest_registration_enabled'])?1:0,'additional'=>isset($input['additional_guests_enabled'])?1:0,'max_guests'=>max(0,(int)($input['max_additional_guests']??0)),'waitlist'=>isset($input['waitlist_enabled'])?1:0,'auto'=>isset($input['automatic_waitlist_promotion'])?1:0,'max_signups'=>($input['max_signups_per_participant']??'')!==''?max(1,(int)$input['max_signups_per_participant']):null,'offer'=>max(15,(int)($input['waitlist_offer_minutes']??1440)),'reply'=>filter_var(($input['organizer_reply_to_email']??''),FILTER_VALIDATE_EMAIL)?:null,'id'=>$eventId
        ]);
    }

    private function ownedEvent(string $ownerId,string $eventId):array{$s=$this->database->pdo()->prepare('SELECT * FROM gather_events WHERE id=:id AND account_id=:owner');$s->execute(['id'=>$eventId,'owner'=>$ownerId]);$event=$s->fetch();if(!$event)throw new RuntimeException('Event not found.');return $event;}
    private function scalar(PDO $pdo,string $sql,string $id):int{$s=$pdo->prepare($sql);$s->execute(['id'=>$id]);return (int)$s->fetchColumn();}
    private function rows(PDO $pdo,string $sql,string $id):array{$s=$pdo->prepare($sql);$s->execute(['id'=>$id]);return $s->fetchAll();}
}
