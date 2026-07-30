<?php

declare(strict_types=1);

namespace Koravik\Districts\Gather;

use Koravik\Platform\Database\Database;
use RuntimeException;

final class GatherAuthorization
{
    public function __construct(private readonly Database $database) {}

    public function requireManage(string $accountId,string $eventId): array
    {
        $s=$this->database->pdo()->prepare('SELECT e.* FROM gather_events e LEFT JOIN organizations o ON o.id=e.organization_id LEFT JOIN organization_memberships m ON m.organization_id=e.organization_id AND m.account_id=:account AND m.status="active" LEFT JOIN households h ON h.id=e.household_id LEFT JOIN household_memberships hm ON hm.household_id=e.household_id AND hm.account_id=:household_account AND hm.status="active" WHERE e.id=:id AND ((e.owner_type="account" AND e.account_id=:account_owner) OR (e.owner_type="organization" AND o.status="active" AND m.role IN ("owner","admin","creator")) OR (e.owner_type="household" AND h.status="active" AND hm.role IN ("owner","admin","member"))) LIMIT 1');
        $s->execute(['account'=>$accountId,'household_account'=>$accountId,'account_owner'=>$accountId,'id'=>$eventId]);$event=$s->fetch();if(!$event)throw new RuntimeException('Event not found or you do not have permission to manage it.');return $event;
    }

    public function requireView(string $accountId,string $eventId): array
    {
        $s=$this->database->pdo()->prepare('SELECT e.* FROM gather_events e LEFT JOIN organizations o ON o.id=e.organization_id LEFT JOIN organization_memberships m ON m.organization_id=e.organization_id AND m.account_id=:account AND m.status="active" LEFT JOIN households h ON h.id=e.household_id LEFT JOIN household_memberships hm ON hm.household_id=e.household_id AND hm.account_id=:household_account AND hm.status="active" WHERE e.id=:id AND ((e.owner_type="account" AND e.account_id=:account_owner) OR (e.owner_type="organization" AND o.status="active" AND m.id IS NOT NULL) OR (e.owner_type="household" AND h.status="active" AND hm.id IS NOT NULL)) LIMIT 1');
        $s->execute(['account'=>$accountId,'household_account'=>$accountId,'account_owner'=>$accountId,'id'=>$eventId]);$event=$s->fetch();if(!$event)throw new RuntimeException('Event not found.');return $event;
    }
}
