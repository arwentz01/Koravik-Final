<?php

declare(strict_types=1);

namespace Koravik\Platform\ReturnExperience;

use Koravik\Platform\Database\Database;
use PDO;
use RuntimeException;

final class ReturnService
{
    public function __construct(private readonly Database $database) {}

    public function observe(string $accountId): bool
    {
        return $this->database->transaction(function (PDO $pdo) use ($accountId): bool {
            $row = $pdo->prepare('SELECT last_seen_at, return_pending FROM account_activity WHERE account_id = :account_id FOR UPDATE');
            $row->execute(['account_id' => $accountId]);
            $activity = $row->fetch();
            $now = gmdate('Y-m-d H:i:s');
            $returned = false;
            if (!$activity) {
                $pdo->prepare('INSERT INTO account_activity (account_id, last_seen_at, return_pending, updated_at) VALUES (:account_id, :last_seen_at, 0, :updated_at)')->execute(['account_id'=>$accountId,'last_seen_at'=>$now,'updated_at'=>$now]);
                return false;
            }
            if (!(bool)$activity['return_pending'] && $activity['last_seen_at'] && strtotime($now) - strtotime((string)$activity['last_seen_at']) >= 7 * 86400) {
                $returned = true;
                $pdo->prepare('UPDATE account_activity SET returned_at = :returned_at, return_pending = 1, last_seen_at = :last_seen_at, updated_at = :updated_at WHERE account_id = :account_id')->execute(['returned_at'=>$now,'last_seen_at'=>$now,'updated_at'=>$now,'account_id'=>$accountId]);
                $eventId = self::uuid();
                $daysAway = max(7, (int)floor((strtotime($now) - strtotime((string)$activity['last_seen_at'])) / 86400));
                $pdo->prepare('INSERT INTO platform_outbox (id,event_name,event_version,account_id,payload_json,status,attempts,available_at,occurred_at,created_at) VALUES (:id,"Platform.PlayerReturned",1,:account_id,:payload,"pending",0,:now,:now,:now)')->execute(['id'=>$eventId,'account_id'=>$accountId,'payload'=>json_encode(['days_away'=>$daysAway], JSON_THROW_ON_ERROR),'now'=>$now]);
                $this->audit($pdo,$accountId,'platform.player.returned',$accountId,$now);
            } else {
                $pdo->prepare('UPDATE account_activity SET last_seen_at = :last_seen_at, updated_at = :updated_at WHERE account_id = :account_id')->execute(['last_seen_at'=>$now,'updated_at'=>$now,'account_id'=>$accountId]);
            }
            return $returned || (bool)$activity['return_pending'];
        });
    }

    public function pending(string $accountId): bool
    {
        $statement = $this->database->pdo()->prepare('SELECT return_pending FROM account_activity WHERE account_id = :account_id');
        $statement->execute(['account_id'=>$accountId]);
        return (bool)$statement->fetchColumn();
    }

    public function acknowledge(string $accountId): void
    {
        $this->database->pdo()->prepare('UPDATE account_activity SET return_pending = 0, updated_at = UTC_TIMESTAMP() WHERE account_id = :account_id')->execute(['account_id'=>$accountId]);
    }

    public function summary(string $accountId): array
    {
        $pdo = $this->database->pdo();
        $base = 'SELECT qo.id AS occurrence_id, qo.scheduled_for, qo.status, q.id AS quest_id, q.title, q.quest_type, q.lifecycle_status, r.frequency FROM quest_occurrences qo JOIN quests q ON q.id=qo.quest_id LEFT JOIN quest_recurrence_rules r ON r.quest_id=q.id WHERE qo.account_id=:account_id';
        $stale = $pdo->prepare($base . ' AND q.lifecycle_status="active" AND qo.status IN ("available","scheduled") AND qo.scheduled_for < DATE_SUB(CURRENT_DATE(), INTERVAL 3 DAY) ORDER BY qo.scheduled_for ASC LIMIT 12');
        $stale->execute(['account_id'=>$accountId]);
        $relevant = $pdo->prepare($base . ' AND q.lifecycle_status="active" AND qo.status IN ("available","scheduled") AND qo.scheduled_for BETWEEN DATE_SUB(CURRENT_DATE(), INTERVAL 3 DAY) AND CURRENT_DATE() ORDER BY qo.scheduled_for ASC LIMIT 6');
        $relevant->execute(['account_id'=>$accountId]);
        $upcoming = $pdo->prepare($base . ' AND q.lifecycle_status="active" AND qo.status IN ("available","scheduled") AND qo.scheduled_for > CURRENT_DATE() ORDER BY qo.scheduled_for ASC LIMIT 6');
        $upcoming->execute(['account_id'=>$accountId]);
        $completed = $pdo->prepare($base . ' AND qo.status="completed" ORDER BY qo.completed_at DESC LIMIT 6');
        $completed->execute(['account_id'=>$accountId]);
        $archived = $pdo->prepare('SELECT id AS quest_id,title,quest_type,lifecycle_status FROM quests WHERE account_id=:account_id AND lifecycle_status="archived" ORDER BY archived_at DESC LIMIT 6');
        $archived->execute(['account_id'=>$accountId]);
        return ['stale'=>$stale->fetchAll(),'relevant'=>$relevant->fetchAll(),'upcoming'=>$upcoming->fetchAll(),'completed'=>$completed->fetchAll(),'archived'=>$archived->fetchAll()];
    }

    public function decide(string $accountId, string $occurrenceId, string $action, ?string $newDate = null): void
    {
        if (!in_array($action,['resume','skip','dismiss','reschedule'],true)) throw new RuntimeException('Unknown return decision.');
        $this->database->transaction(function(PDO $pdo) use($accountId,$occurrenceId,$action,$newDate): void {
            $statement=$pdo->prepare('SELECT qo.id,qo.scheduled_for,q.id AS quest_id FROM quest_occurrences qo JOIN quests q ON q.id=qo.quest_id WHERE qo.id=:id AND qo.account_id=:account_id AND qo.status IN ("available","scheduled") FOR UPDATE');
            $statement->execute(['id'=>$occurrenceId,'account_id'=>$accountId]);
            $row=$statement->fetch();
            if(!$row) throw new RuntimeException('That occurrence is no longer available.');
            $now=gmdate('Y-m-d H:i:s');
            $eventName='';
            $payload=['quest_id'=>(string)$row['quest_id'],'occurrence_id'=>$occurrenceId];
            if($action==='resume') {
                $pdo->prepare('UPDATE quest_occurrences SET status="available", available_at=:now, updated_at=:now WHERE id=:id')->execute(['now'=>$now,'id'=>$occurrenceId]);
                $eventName='Quests.QuestOccurrenceResumed';
            } elseif($action==='skip') {
                $pdo->prepare('UPDATE quest_occurrences SET status="skipped", skipped_at=:now, updated_at=:now WHERE id=:id')->execute(['now'=>$now,'id'=>$occurrenceId]);
                $eventName='Quests.QuestOccurrenceSkipped';
            } elseif($action==='dismiss') {
                $pdo->prepare('UPDATE quest_occurrences SET status="dismissed", dismissed_at=:now, updated_at=:now WHERE id=:id')->execute(['now'=>$now,'id'=>$occurrenceId]);
                $eventName='Quests.QuestOccurrenceDismissed';
            } else {
                $newDate=trim((string)$newDate);
                if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$newDate)) throw new RuntimeException('Choose a valid new date.');
                $pdo->prepare('UPDATE quest_occurrences SET rescheduled_from=scheduled_for, scheduled_for=:scheduled_for, status="scheduled", available_at=:now, updated_at=:now WHERE id=:id')->execute(['scheduled_for'=>$newDate,'now'=>$now,'id'=>$occurrenceId]);
                $eventName='Quests.QuestOccurrenceRescheduled';
                $payload['scheduled_date']=$newDate;
            }
            $eventId=self::uuid();
            $pdo->prepare('INSERT INTO platform_outbox (id,event_name,event_version,account_id,payload_json,status,attempts,available_at,occurred_at,created_at) VALUES (:id,:event_name,1,:account_id,:payload,"pending",0,:now,:now,:now)')->execute(['id'=>$eventId,'event_name'=>$eventName,'account_id'=>$accountId,'payload'=>json_encode($payload,JSON_THROW_ON_ERROR),'now'=>$now]);
            $this->audit($pdo,$accountId,strtolower(str_replace('.','_',$eventName)),$occurrenceId,$now);
        });
    }

    private function audit(PDO $pdo,string $accountId,string $action,string $subjectId,string $now): void
    {
        $pdo->prepare('INSERT INTO audit_log (id,account_id,action,subject_type,subject_id,occurred_at) VALUES (:id,:account_id,:action,"return",:subject_id,:occurred_at)')->execute(['id'=>self::uuid(),'account_id'=>$accountId,'action'=>$action,'subject_id'=>$subjectId,'occurred_at'=>$now]);
    }

    private static function uuid(): string
    {
        $bytes=random_bytes(16);$bytes[6]=chr((ord($bytes[6])&0x0f)|0x40);$bytes[8]=chr((ord($bytes[8])&0x3f)|0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($bytes),4));
    }
}
