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
        $s=$this->database->pdo()->prepare('SELECT e.* FROM gather_events e LEFT JOIN organization_memberships m ON m.organization_id=e.organization_id AND m.account_id=:account AND m.status="active" WHERE e.id=:id AND ((e.owner_type="account" AND e.account_id=:account_owner) OR (e.owner_type="organization" AND m.role IN ("owner","admin","creator"))) LIMIT 1');
        $s->execute(['account'=>$accountId,'account_owner'=>$accountId,'id'=>$eventId]);$event=$s->fetch();if(!$event)throw new RuntimeException('Event not found or you do not have permission to manage it.');return $event;
    }

    public function requireView(string $accountId,string $eventId): array
    {
        $s=$this->database->pdo()->prepare('SELECT e.* FROM gather_events e LEFT JOIN organization_memberships m ON m.organization_id=e.organization_id AND m.account_id=:account AND m.status="active" WHERE e.id=:id AND ((e.owner_type="account" AND e.account_id=:account_owner) OR (e.owner_type="organization" AND m.id IS NOT NULL)) LIMIT 1');
        $s->execute(['account'=>$accountId,'account_owner'=>$accountId,'id'=>$eventId]);$event=$s->fetch();if(!$event)throw new RuntimeException('Event not found.');return $event;
    }
}
