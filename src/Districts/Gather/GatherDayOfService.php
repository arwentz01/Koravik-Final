<?php

declare(strict_types=1);

namespace Koravik\Districts\Gather;

use Koravik\Platform\Database\Database;
use RuntimeException;

final class GatherDayOfService
{
    public function __construct(private readonly Database $database) {}

    public function checkIn(string $ownerId,string $eventId,string $rsvpId,int $partyCount,string $source='manual'):void
    {
        $this->assertOwner($ownerId,$eventId);$pdo=$this->database->pdo();$s=$pdo->prepare('SELECT * FROM gather_rsvps WHERE id=:id AND event_id=:event AND status="active"');$s->execute(['id'=>$rsvpId,'event'=>$eventId]);$r=$s->fetch();if(!$r)throw new RuntimeException('RSVP not found.');$partyCount=max(1,min((int)$r['party_size'],$partyCount));
        $pdo->prepare('INSERT INTO gather_checkins (id,event_id,rsvp_id,account_id,attendee_label,party_count,checked_in_at,source) VALUES (:id,:event,:rsvp,:account,:label,:count,UTC_TIMESTAMP(),:source) ON DUPLICATE KEY UPDATE party_count=VALUES(party_count),attendee_label=VALUES(attendee_label),checked_in_at=UTC_TIMESTAMP(),corrected_at=NULL,correction_note=NULL')->execute(['id'=>self::uuid(),'event'=>$eventId,'rsvp'=>$rsvpId,'account'=>$r['account_id'],'label'=>$r['guest_name']?:'Koravik member','count'=>$partyCount,'source'=>$source==='beacon_qr'?'beacon_qr':'manual']);
    }

    public function correct(string $ownerId,string $eventId,string $rsvpId,string $note):void
    {
        $this->assertOwner($ownerId,$eventId);$this->database->pdo()->prepare('UPDATE gather_checkins SET corrected_at=UTC_TIMESTAMP(),corrected_by_account_id=:owner,correction_note=:note WHERE event_id=:event AND rsvp_id=:rsvp')->execute(['owner'=>$ownerId,'note'=>trim($note),'event'=>$eventId,'rsvp'=>$rsvpId]);
    }

    private function assertOwner(string $ownerId,string $eventId):void{(new GatherAuthorization($this->database))->requireManage($ownerId,$eventId);}
    private static function uuid():string{$d=random_bytes(16);$d[6]=chr((ord($d[6])&0x0f)|0x40);$d[8]=chr((ord($d[8])&0x3f)|0x80);return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($d),4));}
}
