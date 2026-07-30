<?php

declare(strict_types=1);

namespace Koravik\Platform\Hearth;

use PDO;

final class DailyFocusRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function find(string $accountId,string $date): ?array
    {
        $s=$this->pdo->prepare('SELECT id,intention,focus_date,updated_at FROM hearth_daily_focus WHERE account_id=:account_id AND focus_date=:focus_date LIMIT 1');
        $s->execute(['account_id'=>$accountId,'focus_date'=>$date]);$focus=$s->fetch();
        if(!$focus)return null;
        $e=$this->pdo->prepare(
            'SELECT e.quest_occurrence_id,e.position,q.id quest_id,q.title,q.next_step,q.quest_type,
                    qo.scheduled_for,qo.status
             FROM hearth_daily_focus_entries e
             JOIN quest_occurrences qo ON qo.id=e.quest_occurrence_id
             JOIN quests q ON q.id=qo.quest_id
             WHERE e.focus_id=:focus_id AND qo.account_id=:account_id
             ORDER BY e.position'
        );
        $e->execute(['focus_id'=>$focus['id'],'account_id'=>$accountId]);$focus['entries']=$e->fetchAll();
        return $focus;
    }

    public function candidates(string $accountId,string $date): array
    {
        $s=$this->pdo->prepare(
            'SELECT qo.id occurrence_id,qo.scheduled_for,qo.status,q.id quest_id,q.title,q.next_step,q.quest_type
             FROM quest_occurrences qo JOIN quests q ON q.id=qo.quest_id
             WHERE qo.account_id=:account_id AND q.account_id=:owner
               AND q.lifecycle_status="active" AND qo.status IN ("available","scheduled")
               AND qo.id=(SELECT qo2.id FROM quest_occurrences qo2 WHERE qo2.quest_id=q.id AND qo2.account_id=:next_owner AND qo2.status IN ("available","scheduled") ORDER BY qo2.scheduled_for,qo2.created_at LIMIT 1)
             ORDER BY CASE WHEN qo.scheduled_for<=:focus_date THEN 0 ELSE 1 END,qo.scheduled_for,q.created_at DESC
             LIMIT 30'
        );
        $s->execute(['account_id'=>$accountId,'owner'=>$accountId,'next_owner'=>$accountId,'focus_date'=>$date]);return $s->fetchAll();
    }

    public function ownedCandidateIds(string $accountId,array $ids): array
    {
        if($ids===[])return [];
        $marks=implode(',',array_fill(0,count($ids),'?'));
        $s=$this->pdo->prepare("SELECT qo.id,q.id quest_id FROM quest_occurrences qo JOIN quests q ON q.id=qo.quest_id WHERE qo.id IN ({$marks}) AND qo.account_id=? AND q.account_id=? AND q.lifecycle_status='active' AND qo.status IN ('available','scheduled')");
        $s->execute([...$ids,$accountId,$accountId]);return $s->fetchAll();
    }

    public function replace(string $accountId,string $date,string $intention,array $ids): string
    {
        $s=$this->pdo->prepare(
            'INSERT INTO hearth_daily_focus (id,account_id,focus_date,intention,created_at,updated_at)
             VALUES (:id,:account_id,:focus_date,:intention,UTC_TIMESTAMP(),UTC_TIMESTAMP())
             ON DUPLICATE KEY UPDATE intention=VALUES(intention),updated_at=UTC_TIMESTAMP()'
        );
        $newId=self::uuid();$s->execute(['id'=>$newId,'account_id'=>$accountId,'focus_date'=>$date,'intention'=>$intention!==''?$intention:null]);
        $lookup=$this->pdo->prepare('SELECT id FROM hearth_daily_focus WHERE account_id=:account_id AND focus_date=:focus_date LIMIT 1');
        $lookup->execute(['account_id'=>$accountId,'focus_date'=>$date]);$id=(string)$lookup->fetchColumn();
        $this->pdo->prepare('DELETE FROM hearth_daily_focus_entries WHERE focus_id=:focus_id')->execute(['focus_id'=>$id]);
        $insert=$this->pdo->prepare('INSERT INTO hearth_daily_focus_entries (focus_id,quest_occurrence_id,position,created_at) VALUES (:focus_id,:occurrence_id,:position,UTC_TIMESTAMP())');
        foreach($ids as $position=>$occurrenceId)$insert->execute(['focus_id'=>$id,'occurrence_id'=>$occurrenceId,'position'=>$position+1]);
        return $id;
    }

    public function clear(string $accountId,string $date): bool
    {
        $s=$this->pdo->prepare('DELETE FROM hearth_daily_focus WHERE account_id=:account_id AND focus_date=:focus_date');
        $s->execute(['account_id'=>$accountId,'focus_date'=>$date]);return $s->rowCount()===1;
    }

    private static function uuid():string{$b=random_bytes(16);$b[6]=chr((ord($b[6])&15)|64);$b[8]=chr((ord($b[8])&63)|128);return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($b),4));}
}
