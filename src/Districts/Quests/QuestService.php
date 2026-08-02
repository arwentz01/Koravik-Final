<?php

declare(strict_types=1);

namespace Koravik\Districts\Quests;

use Koravik\Platform\Database\Database;
use PDO;
use RuntimeException;

final class QuestService
{
    private const TYPES = ['action', 'habit', 'project', 'journey', 'responsibility', 'world_objective'];
    private const ORIGINS = ['personal', 'story', 'health', 'beacon', 'gather', 'companion'];
    private const OUTCOMES = ['completed', 'partial', 'changed_direction', 'released', 'paused', 'deferred'];

    public function __construct(private readonly Database $database)
    {
    }

    public function create(string $accountId, string $title, string $description = '', array $options = []): string
    {
        $title = trim($title);
        $description = trim($description);
        $purpose = trim((string) ($options['purpose'] ?? ''));
        $nextStep = trim((string) ($options['next_step'] ?? ''));
        $type = strtolower((string) ($options['quest_type'] ?? 'action'));
        $origin = strtolower((string) ($options['origin_type'] ?? 'personal'));
        $originReference = trim((string) ($options['origin_reference'] ?? ''));
        if ($title === '') throw new RuntimeException('Give this Quest a title.');
        if (mb_strlen($title) > 180) throw new RuntimeException('Quest titles must be 180 characters or fewer.');
        if (mb_strlen($description) > 4000) throw new RuntimeException('Quest notes must be 4,000 characters or fewer.');
        if (mb_strlen($purpose) > 2000) throw new RuntimeException('Why this matters must be 2,000 characters or fewer.');
        if (mb_strlen($nextStep) > 180) throw new RuntimeException('The next step must be 180 characters or fewer.');
        if (!in_array($type, self::TYPES, true)) throw new RuntimeException('Choose a valid Quest type.');
        if (!in_array($origin, self::ORIGINS, true)) throw new RuntimeException('Choose a valid Quest origin.');

        return $this->database->transaction(function (PDO $pdo) use ($accountId, $title, $description, $purpose, $nextStep, $options, $type, $origin, $originReference): string {
            $questId = self::uuid();
            $now = gmdate('Y-m-d H:i:s');
            $statement = $pdo->prepare('INSERT INTO quests (id, account_id, title, description, purpose, next_step, quest_type, origin_type, origin_reference, status, lifecycle_status, created_at, updated_at) VALUES (:id, :account_id, :title, :description, :purpose, :next_step, :quest_type, :origin_type, :origin_reference, "active", "active", :created_at, :updated_at)');
            $statement->execute(['id'=>$questId,'account_id'=>$accountId,'title'=>$title,'description'=>$description,'purpose'=>$purpose !== '' ? $purpose : null,'next_step'=>$nextStep !== '' ? $nextStep : null,'quest_type'=>$type,'origin_type'=>$origin,'origin_reference'=>$originReference !== '' ? $originReference : null,'created_at'=>$now,'updated_at'=>$now]);

            $frequency = strtolower((string) ($options['frequency'] ?? 'none'));
            if ($frequency === 'none') {
                $scheduledFor = trim((string) ($options['starts_on'] ?? gmdate('Y-m-d')));
                $pdo->prepare('INSERT INTO quest_occurrences (id, quest_id, account_id, scheduled_for, status, available_at, created_at, updated_at) VALUES (:id, :quest_id, :account_id, :scheduled_for, "available", :available_at, :created_at, :updated_at)')->execute(['id'=>self::uuid(),'quest_id'=>$questId,'account_id'=>$accountId,'scheduled_for'=>$scheduledFor,'available_at'=>$now,'created_at'=>$now,'updated_at'=>$now]);
            } else {
                (new RecurrenceService($this->database))->saveRule($pdo, $questId, $options, $accountId);
            }

            if (in_array($type, ['project', 'journey'], true)) {
                foreach ([25 => 'First foothold', 50 => 'Halfway marker', 100 => 'Journey complete'] as $threshold => $label) {
                    $pdo->prepare('INSERT INTO quest_milestones (id, quest_id, title, threshold_percent, created_at) VALUES (:id, :quest_id, :title, :threshold, :created_at)')->execute(['id'=>self::uuid(),'quest_id'=>$questId,'title'=>$label,'threshold'=>$threshold,'created_at'=>$now]);
                }
            }

            $this->audit($pdo, $accountId, 'quest.created', $questId, $now);
            return $questId;
        });
    }

    public function getForAccount(string $questId, string $accountId): ?array
    {
        $statement = $this->database->pdo()->prepare('SELECT q.id, q.title, q.description, q.purpose, q.next_step, q.quest_type, q.origin_type, q.origin_reference, q.status, q.lifecycle_status, r.frequency, r.interval_count, r.starts_on, r.ends_on, (SELECT GROUP_CONCAT(w.weekday ORDER BY w.weekday) FROM quest_recurrence_weekdays w WHERE w.quest_id = q.id) AS weekdays, (SELECT qo.id FROM quest_occurrences qo WHERE qo.quest_id = q.id AND qo.account_id = :occurrence_account AND qo.status IN ("available", "scheduled") ORDER BY qo.scheduled_for ASC LIMIT 1) AS next_occurrence_id, (SELECT qo.scheduled_for FROM quest_occurrences qo WHERE qo.quest_id = q.id AND qo.account_id = :date_account AND qo.status IN ("available", "scheduled") ORDER BY qo.scheduled_for ASC LIMIT 1) AS next_scheduled_for, CASE WHEN EXISTS (SELECT 1 FROM quest_occurrences qo WHERE qo.quest_id = q.id AND qo.status IN ("available", "scheduled")) THEN 0 ELSE 1 END AS completed FROM quests q LEFT JOIN quest_recurrence_rules r ON r.quest_id = q.id WHERE q.id = :quest_id AND q.account_id = :quest_account LIMIT 1');
        $statement->execute(['quest_id'=>$questId,'occurrence_account'=>$accountId,'date_account'=>$accountId,'quest_account'=>$accountId]);
        $quest = $statement->fetch();
        if (!$quest) return null;
        $quest['steps'] = $this->steps($questId, $accountId);
        $quest['progress_percent'] = $this->progressPercent($quest['steps']);
        $quest['milestones'] = $this->milestones($questId);
        $quest['resolutions'] = $this->resolutions($questId, $accountId);
        return $quest;
    }

    public function listForAccount(string $accountId): array
    {
        $statement = $this->database->pdo()->prepare('SELECT q.id, q.title, q.description, q.purpose, q.next_step, q.quest_type, q.origin_type, q.lifecycle_status, r.frequency, r.interval_count, MIN(CASE WHEN qo.status IN ("available", "scheduled") THEN qo.scheduled_for END) AS next_scheduled_for, CASE WHEN SUM(CASE WHEN qo.status IN ("available", "scheduled") THEN 1 ELSE 0 END) = 0 THEN 1 ELSE 0 END AS completed, (SELECT COUNT(*) FROM quest_steps qs WHERE qs.quest_id = q.id) AS step_count, (SELECT COUNT(*) FROM quest_steps qs WHERE qs.quest_id = q.id AND qs.status = "completed") AS completed_step_count FROM quests q LEFT JOIN quest_recurrence_rules r ON r.quest_id = q.id LEFT JOIN quest_occurrences qo ON qo.quest_id = q.id AND qo.account_id = :occurrence_account WHERE q.account_id = :quest_account AND q.lifecycle_status <> "archived" GROUP BY q.id, q.title, q.description, q.purpose, q.next_step, q.quest_type, q.origin_type, q.lifecycle_status, r.frequency, r.interval_count, q.created_at ORDER BY completed ASC, next_scheduled_for ASC, q.created_at DESC');
        $statement->execute(['occurrence_account'=>$accountId,'quest_account'=>$accountId]);
        return $statement->fetchAll();
    }

    public function management(string $accountId): array
    {
        $statement=$this->database->pdo()->prepare('SELECT q.id,q.title,q.description,q.quest_type,q.lifecycle_status,q.created_at,q.updated_at,q.archived_at,r.frequency,r.interval_count,MIN(CASE WHEN qo.status IN ("available","scheduled") THEN qo.scheduled_for END) next_scheduled_for,SUM(qo.status="completed") completion_count FROM quests q LEFT JOIN quest_recurrence_rules r ON r.quest_id=q.id LEFT JOIN quest_occurrences qo ON qo.quest_id=q.id AND qo.account_id=:occurrence_account WHERE q.account_id=:quest_account GROUP BY q.id,q.title,q.description,q.quest_type,q.lifecycle_status,q.created_at,q.updated_at,q.archived_at,r.frequency,r.interval_count ORDER BY FIELD(q.lifecycle_status,"active","paused","archived"),q.updated_at DESC');
        $statement->execute(['occurrence_account'=>$accountId,'quest_account'=>$accountId]);$groups=['active'=>[],'paused'=>[],'archived'=>[]];foreach($statement->fetchAll() as $row)$groups[$row['lifecycle_status']][]=$row;return $groups;
    }

    public function history(string $questId,string $accountId): array
    {
        $owned=$this->getForAccount($questId,$accountId);if(!$owned)throw new RuntimeException('Quest not found or unavailable.');
        $s=$this->database->pdo()->prepare('SELECT id,scheduled_for,status,completed_at,skipped_at,dismissed_at,rescheduled_from,updated_at FROM quest_occurrences WHERE quest_id=:quest_id AND account_id=:account_id ORDER BY scheduled_for DESC,updated_at DESC LIMIT 100');$s->execute(['quest_id'=>$questId,'account_id'=>$accountId]);return ['quest'=>$owned,'occurrences'=>$s->fetchAll()];
    }

    public function updateDetails(string $questId,string $accountId,array $input): void
    {
        if(!$this->getForAccount($questId,$accountId))throw new RuntimeException('Quest not found or unavailable.');
        $title=trim((string)($input['title']??''));$description=trim((string)($input['description']??''));$purpose=trim((string)($input['purpose']??''));$next=trim((string)($input['next_step']??''));
        if($title===''||mb_strlen($title)>180)throw new RuntimeException('Use a Quest title between 1 and 180 characters.');if(mb_strlen($description)>4000)throw new RuntimeException('Quest notes must be 4,000 characters or fewer.');if(mb_strlen($purpose)>2000)throw new RuntimeException('Why this matters must be 2,000 characters or fewer.');if(mb_strlen($next)>180)throw new RuntimeException('The next step must be 180 characters or fewer.');
        $this->database->transaction(function(PDO $pdo)use($questId,$accountId,$title,$description,$purpose,$next):void{$s=$pdo->prepare('UPDATE quests SET title=:title,description=:description,purpose=:purpose,next_step=:next_step,updated_at=UTC_TIMESTAMP() WHERE id=:id AND account_id=:account_id');$s->execute(['title'=>$title,'description'=>$description,'purpose'=>$purpose?:null,'next_step'=>$next?:null,'id'=>$questId,'account_id'=>$accountId]);$this->audit($pdo,$accountId,'quest.updated',$questId,gmdate('Y-m-d H:i:s'));});
    }

    public function updateRecurrence(string $questId,string $accountId,array $input): void
    {
        if(!$this->getForAccount($questId,$accountId))throw new RuntimeException('Quest not found or unavailable.');
        $frequency=strtolower((string)($input['frequency']??'none'));
        $this->database->transaction(function(PDO $pdo)use($questId,$accountId,$input,$frequency):void{
            $pdo->prepare('DELETE FROM quest_recurrence_weekdays WHERE quest_id=:quest_id')->execute(['quest_id'=>$questId]);
            $pdo->prepare('DELETE FROM quest_recurrence_rules WHERE quest_id=:quest_id')->execute(['quest_id'=>$questId]);
            $removed=$pdo->prepare('DELETE FROM quest_occurrences WHERE quest_id=:quest_id AND account_id=:account_id AND status IN ("available","scheduled") AND completed_at IS NULL AND skipped_at IS NULL AND dismissed_at IS NULL');
            $removed->execute(['quest_id'=>$questId,'account_id'=>$accountId]);
            if($frequency==='none'){
                $date=trim((string)($input['starts_on']??gmdate('Y-m-d')));
                $parsed=\DateTimeImmutable::createFromFormat('!Y-m-d',$date);if(!$parsed||$parsed->format('Y-m-d')!==$date)throw new RuntimeException('Choose a valid start date.');
                $pdo->prepare('INSERT INTO quest_occurrences (id,quest_id,account_id,scheduled_for,status,available_at,created_at,updated_at) VALUES (:id,:quest_id,:account_id,:date,:status,:available,UTC_TIMESTAMP(),UTC_TIMESTAMP())')->execute(['id'=>self::uuid(),'quest_id'=>$questId,'account_id'=>$accountId,'date'=>$date,'status'=>$date<=gmdate('Y-m-d')?'available':'scheduled','available'=>$date.' 00:00:00']);
            }else{
                (new RecurrenceService($this->database))->saveRule($pdo,$questId,$input,$accountId);
            }
            $this->audit($pdo,$accountId,'quest.recurrence.updated',$questId,gmdate('Y-m-d H:i:s'));
            $this->timeline($pdo,$accountId,$questId,'recurrence_rebuilt','Quest Recurrence Occurrence Rebuild preserved completed history and regenerated future occurrences. Removed '.$removed->rowCount().' pending future occurrence(s).','quest',$questId);
        });
    }

    public function rescheduleNext(string $questId,string $accountId,string $date): void
    {
        $parsed=\DateTimeImmutable::createFromFormat('!Y-m-d',$date);if(!$parsed||$parsed->format('Y-m-d')!==$date)throw new RuntimeException('Choose a valid date.');
        $this->database->transaction(function(PDO $pdo)use($questId,$accountId,$date):void{$s=$pdo->prepare('SELECT id,scheduled_for FROM quest_occurrences WHERE quest_id=:quest_id AND account_id=:account_id AND status IN ("available","scheduled") ORDER BY scheduled_for LIMIT 1 FOR UPDATE');$s->execute(['quest_id'=>$questId,'account_id'=>$accountId]);$row=$s->fetch();if(!$row)throw new RuntimeException('There is no available occurrence to reschedule.');$pdo->prepare('UPDATE quest_occurrences SET rescheduled_from=scheduled_for,scheduled_for=:date,status=:status,available_at=:available_at,updated_at=UTC_TIMESTAMP() WHERE id=:id')->execute(['date'=>$date,'status'=>$date<=gmdate('Y-m-d')?'available':'scheduled','available_at'=>$date.' 00:00:00','id'=>$row['id']]);$this->audit($pdo,$accountId,'quest.occurrence.rescheduled',(string)$row['id'],gmdate('Y-m-d H:i:s'));});
    }

    public function updateFocus(string $questId, string $accountId, string $purpose, string $nextStep): void
    {
        $purpose = trim($purpose);
        $nextStep = trim($nextStep);
        if (mb_strlen($purpose) > 2000) throw new RuntimeException('Why this matters must be 2,000 characters or fewer.');
        if (mb_strlen($nextStep) > 180) throw new RuntimeException('The next step must be 180 characters or fewer.');
        $statement = $this->database->pdo()->prepare('UPDATE quests SET purpose = :purpose, next_step = :next_step, updated_at = UTC_TIMESTAMP() WHERE id = :id AND account_id = :account_id');
        $statement->execute(['purpose'=>$purpose !== '' ? $purpose : null,'next_step'=>$nextStep !== '' ? $nextStep : null,'id'=>$questId,'account_id'=>$accountId]);
        if ($statement->rowCount() !== 1) throw new RuntimeException('Quest not found or unavailable.');
    }

    public function resolve(string $questId, string $accountId, string $outcome, string $reflection = ''): void
    {
        $outcome = strtolower(trim($outcome));
        $reflection = trim($reflection);
        if (!in_array($outcome, self::OUTCOMES, true)) throw new RuntimeException('Choose a valid Quest outcome.');
        if (mb_strlen($reflection) > 4000) throw new RuntimeException('Reflection must be 4,000 characters or fewer.');

        $this->database->transaction(function (PDO $pdo) use ($questId, $accountId, $outcome, $reflection): void {
            $quest = $pdo->prepare('SELECT id FROM quests WHERE id = :id AND account_id = :account_id FOR UPDATE');
            $quest->execute(['id'=>$questId,'account_id'=>$accountId]);
            if (!$quest->fetchColumn()) throw new RuntimeException('Quest not found or unavailable.');
            $now = gmdate('Y-m-d H:i:s');
            $pdo->prepare('INSERT INTO quest_resolutions (id, quest_id, account_id, outcome, reflection, resolved_at, created_at) VALUES (:id, :quest_id, :account_id, :outcome, :reflection, :resolved_at, :created_at)')->execute(['id'=>self::uuid(),'quest_id'=>$questId,'account_id'=>$accountId,'outcome'=>$outcome,'reflection'=>$reflection !== '' ? $reflection : null,'resolved_at'=>$now,'created_at'=>$now]);
            $lifecycle = match ($outcome) {'paused'=>'paused','released'=>'archived',default=>'active'};
            $pdo->prepare('UPDATE quests SET lifecycle_status = :lifecycle, archived_at = :archived_at, updated_at = :updated_at WHERE id = :id AND account_id = :account_id')->execute(['lifecycle'=>$lifecycle,'archived_at'=>$lifecycle === 'archived' ? $now : null,'updated_at'=>$now,'id'=>$questId,'account_id'=>$accountId]);
            $this->audit($pdo, $accountId, 'quest.resolved.' . $outcome, $questId, $now);
        });
    }

    public function addStep(string $questId, string $accountId, string $title, bool $required = true): string
    {
        $title = trim($title);
        if ($title === '') throw new RuntimeException('Give this step a title.');
        if (mb_strlen($title) > 180) throw new RuntimeException('Step titles must be 180 characters or fewer.');
        return $this->database->transaction(function (PDO $pdo) use ($questId, $accountId, $title, $required): string {
            $quest = $pdo->prepare('SELECT quest_type FROM quests WHERE id = :id AND account_id = :account_id AND lifecycle_status <> "archived" FOR UPDATE');
            $quest->execute(['id'=>$questId,'account_id'=>$accountId]);
            $type = $quest->fetchColumn();
            if (!$type) throw new RuntimeException('Quest not found or unavailable.');
            if (!in_array($type, ['project','journey','responsibility','world_objective'], true)) throw new RuntimeException('This Quest type does not use steps.');
            $order = (int) $pdo->query('SELECT COALESCE(MAX(sort_order), 0) + 10 FROM quest_steps WHERE quest_id = ' . $pdo->quote($questId))->fetchColumn();
            $id = self::uuid();
            $pdo->prepare('INSERT INTO quest_steps (id, quest_id, title, is_required, sort_order, status, created_at, updated_at) VALUES (:id, :quest_id, :title, :required, :sort_order, "pending", UTC_TIMESTAMP(), UTC_TIMESTAMP())')->execute(['id'=>$id,'quest_id'=>$questId,'title'=>$title,'required'=>$required ? 1 : 0,'sort_order'=>$order]);
            $this->audit($pdo, $accountId, 'quest.step.created', $id, gmdate('Y-m-d H:i:s'));
            return $id;
        });
    }

    public function setStepStatus(string $questId, string $stepId, string $accountId, string $status): void
    {
        if (!in_array($status, ['pending','completed','skipped'], true)) throw new RuntimeException('Choose a valid step status.');
        $this->database->transaction(function (PDO $pdo) use ($questId, $stepId, $accountId, $status): void {
            $statement = $pdo->prepare('UPDATE quest_steps qs JOIN quests q ON q.id = qs.quest_id SET qs.status = :status, qs.completed_at = :completed_at, qs.updated_at = UTC_TIMESTAMP() WHERE qs.id = :step_id AND qs.quest_id = :quest_id AND q.account_id = :account_id');
            $statement->execute(['status'=>$status,'completed_at'=>$status === 'completed' ? gmdate('Y-m-d H:i:s') : null,'step_id'=>$stepId,'quest_id'=>$questId,'account_id'=>$accountId]);
            if ($statement->rowCount() !== 1) throw new RuntimeException('Step not found or unavailable.');
            $this->refreshMilestones($pdo, $questId);
            $this->audit($pdo, $accountId, 'quest.step.' . $status, $stepId, gmdate('Y-m-d H:i:s'));
        });
    }

    public function complete(string $questId, string $accountId): string
    {
        return $this->database->transaction(function (PDO $pdo) use ($questId, $accountId): string {
            $questStatement = $pdo->prepare('SELECT q.id, q.title, q.quest_type, r.quest_id AS recurring FROM quests q LEFT JOIN quest_recurrence_rules r ON r.quest_id = q.id WHERE q.id = :quest_id AND q.account_id = :account_id AND q.lifecycle_status = "active" FOR UPDATE');
            $questStatement->execute(['quest_id'=>$questId,'account_id'=>$accountId]);
            $quest = $questStatement->fetch();
            if (!$quest) throw new RuntimeException('Quest not found or unavailable.');
            if (in_array($quest['quest_type'], ['project','journey'], true)) {
                $remaining = $pdo->prepare('SELECT COUNT(*) FROM quest_steps WHERE quest_id = :quest_id AND is_required = 1 AND status <> "completed"');
                $remaining->execute(['quest_id'=>$questId]);
                if ((int) $remaining->fetchColumn() > 0) throw new RuntimeException('Complete the required steps before finishing this Quest.');
            }
            $occurrenceStatement = $pdo->prepare('SELECT id, scheduled_for FROM quest_occurrences WHERE quest_id = :quest_id AND account_id = :account_id AND status IN ("available", "scheduled") ORDER BY scheduled_for ASC LIMIT 1 FOR UPDATE');
            $occurrenceStatement->execute(['quest_id'=>$questId,'account_id'=>$accountId]);
            $occurrence = $occurrenceStatement->fetch();
            if (!$occurrence) throw new RuntimeException('There is no available occurrence to complete.');
            $now = gmdate('Y-m-d H:i:s');
            $pdo->prepare('UPDATE quest_occurrences SET status = "completed", completed_at = :completed_at, updated_at = :updated_at WHERE id = :id')->execute(['completed_at'=>$now,'updated_at'=>$now,'id'=>$occurrence['id']]);
            if (!$quest['recurring']) $pdo->prepare('INSERT IGNORE INTO quest_completions (id, quest_id, account_id, completed_at) VALUES (:id, :quest_id, :account_id, :completed_at)')->execute(['id'=>self::uuid(),'quest_id'=>$questId,'account_id'=>$accountId,'completed_at'=>$now]);
            $eventId = self::uuid();
            $payload = json_encode(['quest_id'=>$questId,'occurrence_id'=>(string)$occurrence['id'],'scheduled_date'=>(string)$occurrence['scheduled_for'],'recurring'=>(bool)$quest['recurring'],'title'=>(string)$quest['title'],'quest_type'=>(string)$quest['quest_type']], JSON_THROW_ON_ERROR);
            $pdo->prepare('INSERT INTO platform_outbox (id, event_name, event_version, account_id, payload_json, status, attempts, available_at, occurred_at, created_at) VALUES (:id, "Quests.QuestCompleted", 1, :account_id, :payload_json, "pending", 0, :available_at, :occurred_at, :created_at)')->execute(['id'=>$eventId,'account_id'=>$accountId,'payload_json'=>$payload,'available_at'=>$now,'occurred_at'=>$now,'created_at'=>$now]);
            $this->audit($pdo, $accountId, 'quest.occurrence.completed', (string)$occurrence['id'], $now);
            $this->timeline($pdo,$accountId,$questId,'completed','Daily Focus + Quest Completion Loop Polish: occurrence completed with optional reflection and World eligibility next steps.','quest_occurrence',(string)$occurrence['id']);
            return $eventId;
        });
    }

    public function timelineFor(string $questId,string $accountId): array
    {
        $quest=$this->getForAccount($questId,$accountId);if(!$quest)throw new RuntimeException('Quest not found or unavailable.');
        $events=$this->database->pdo()->prepare('SELECT event_type,summary,source_type,source_id,occurred_at FROM quest_timeline_events WHERE quest_id=:quest_id AND account_id=:account_id ORDER BY occurred_at DESC LIMIT 100');
        $events->execute(['quest_id'=>$questId,'account_id'=>$accountId]);
        $occ=$this->database->pdo()->prepare('SELECT status,scheduled_for,completed_at,skipped_at,dismissed_at,rescheduled_from,updated_at FROM quest_occurrences WHERE quest_id=:quest_id AND account_id=:account_id ORDER BY updated_at DESC LIMIT 50');
        $occ->execute(['quest_id'=>$questId,'account_id'=>$accountId]);
        $audit=$this->database->pdo()->prepare('SELECT action,subject_type,subject_id,occurred_at FROM audit_log WHERE account_id=:account_id AND subject_id IN (:quest_id) ORDER BY occurred_at DESC LIMIT 50');
        $audit->execute(['account_id'=>$accountId,'quest_id'=>$questId]);
        return ['quest'=>$quest,'events'=>$events->fetchAll(),'occurrences'=>$occ->fetchAll(),'audit'=>$audit->fetchAll()];
    }

    public function setLifecycle(string $questId, string $accountId, string $status): void
    {
        if (!in_array($status, ['active','paused','archived'], true)) throw new RuntimeException('Choose a valid Quest status.');
        $this->database->transaction(function (PDO $pdo) use ($questId,$accountId,$status): void {
            $statement = $pdo->prepare('UPDATE quests SET lifecycle_status = :status, archived_at = :archived_at, updated_at = UTC_TIMESTAMP() WHERE id = :id AND account_id = :account_id');
            $statement->execute(['status'=>$status,'archived_at'=>$status === 'archived' ? gmdate('Y-m-d H:i:s') : null,'id'=>$questId,'account_id'=>$accountId]);
            if ($statement->rowCount() !== 1) throw new RuntimeException('Quest not found or unavailable.');
            $this->audit($pdo, $accountId, 'quest.' . $status, $questId, gmdate('Y-m-d H:i:s'));
        });
    }

    public static function recurrenceLabel(array $quest): ?string
    {
        if (empty($quest['frequency'])) return null;
        $interval = (int) ($quest['interval_count'] ?? 1);
        $prefix = $interval === 1 ? 'Every' : 'Every ' . $interval;
        $unit = match ($quest['frequency']) {'daily'=>$interval === 1 ? 'day':'days','weekly'=>$interval === 1 ? 'week':'weeks','monthly'=>$interval === 1 ? 'month':'months','yearly'=>$interval === 1 ? 'year':'years',default=>(string)$quest['frequency']};
        if ($quest['frequency'] === 'weekly' && !empty($quest['weekdays'])) {
            $names = [1=>'Mon',2=>'Tue',3=>'Wed',4=>'Thu',5=>'Fri',6=>'Sat',7=>'Sun'];
            return $prefix . ' ' . $unit . ' on ' . implode(', ', array_map(static fn(string $day): string => $names[(int)$day], explode(',', (string)$quest['weekdays'])));
        }
        return $prefix . ' ' . $unit;
    }

    public static function typeLabel(string $type): string
    {
        return match ($type) {'habit'=>'Habit','project'=>'Project','journey'=>'Journey','responsibility'=>'Responsibility','world_objective'=>'World objective',default=>'Action'};
    }

    public static function originLabel(string $origin): string
    {
        return match ($origin) {'story'=>'Story invitation','health'=>'Health','beacon'=>'Beacon','gather'=>'Gather','companion'=>'Companion',default=>'Personal'};
    }

    private function steps(string $questId, string $accountId): array
    {
        $statement = $this->database->pdo()->prepare('SELECT qs.id, qs.title, qs.is_required, qs.sort_order, qs.status, qs.completed_at FROM quest_steps qs JOIN quests q ON q.id = qs.quest_id WHERE qs.quest_id = :quest_id AND q.account_id = :account_id ORDER BY qs.sort_order');
        $statement->execute(['quest_id'=>$questId,'account_id'=>$accountId]);
        return $statement->fetchAll();
    }

    private function milestones(string $questId): array
    {
        $statement = $this->database->pdo()->prepare('SELECT id, title, threshold_percent, reached_at FROM quest_milestones WHERE quest_id = :quest_id ORDER BY threshold_percent');
        $statement->execute(['quest_id'=>$questId]);
        return $statement->fetchAll();
    }

    private function resolutions(string $questId, string $accountId): array
    {
        $statement = $this->database->pdo()->prepare('SELECT outcome, reflection, resolved_at FROM quest_resolutions WHERE quest_id = :quest_id AND account_id = :account_id ORDER BY resolved_at DESC LIMIT 20');
        $statement->execute(['quest_id'=>$questId,'account_id'=>$accountId]);
        return $statement->fetchAll();
    }

    private function progressPercent(array $steps): int
    {
        if ($steps === []) return 0;
        $complete = count(array_filter($steps, static fn(array $step): bool => $step['status'] === 'completed'));
        return (int) round(($complete / count($steps)) * 100);
    }

    private function refreshMilestones(PDO $pdo, string $questId): void
    {
        $statement = $pdo->prepare('SELECT COUNT(*) AS total, SUM(status = "completed") AS completed FROM quest_steps WHERE quest_id = :quest_id');
        $statement->execute(['quest_id'=>$questId]);
        $counts = $statement->fetch();
        $percent = (int)($counts['total'] ?? 0) > 0 ? (int)round(((int)$counts['completed'] / (int)$counts['total']) * 100) : 0;
        $pdo->prepare('UPDATE quest_milestones SET reached_at = CASE WHEN threshold_percent <= :percent THEN COALESCE(reached_at, UTC_TIMESTAMP()) ELSE NULL END WHERE quest_id = :quest_id')->execute(['percent'=>$percent,'quest_id'=>$questId]);
    }

    private function audit(PDO $pdo, string $accountId, string $action, string $subjectId, string $occurredAt): void
    {
        $pdo->prepare('INSERT INTO audit_log (id, account_id, action, subject_type, subject_id, occurred_at) VALUES (:id, :account_id, :action, "quest", :subject_id, :occurred_at)')->execute(['id'=>self::uuid(),'account_id'=>$accountId,'action'=>$action,'subject_id'=>$subjectId,'occurred_at'=>$occurredAt]);
    }
    private function timeline(PDO $pdo,string $accountId,string $questId,string $type,string $summary,string $sourceType,string $sourceId): void
    {
        $pdo->prepare('INSERT INTO quest_timeline_events (id,account_id,quest_id,event_type,summary,source_type,source_id,occurred_at) VALUES (:id,:account_id,:quest_id,:type,:summary,:source_type,:source_id,UTC_TIMESTAMP())')->execute(['id'=>self::uuid(),'account_id'=>$accountId,'quest_id'=>$questId,'type'=>$type,'summary'=>$summary,'source_type'=>$sourceType,'source_id'=>$sourceId]);
    }

    private static function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
