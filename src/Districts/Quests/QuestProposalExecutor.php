<?php

declare(strict_types=1);
namespace Koravik\Districts\Quests;

use Koravik\Platform\Database\Database;
use PDO;
use RuntimeException;

final class QuestProposalExecutor
{
    public function __construct(private readonly Database $database) {}

    public function execute(string $accountId,string $proposalId): string
    {
        return $this->database->transaction(function(PDO $pdo) use($accountId,$proposalId): string {
            $receipt=$pdo->prepare('SELECT record_id FROM companion_execution_receipts WHERE proposal_id=:proposal_id LIMIT 1');
            $receipt->execute(['proposal_id'=>$proposalId]);
            $existing=$receipt->fetchColumn();
            if($existing) return (string)$existing;

            $s=$pdo->prepare('SELECT id,status,proposal_type,version,approved_version,expires_at,proposed_payload_json FROM companion_proposals WHERE id=:id AND account_id=:account_id FOR UPDATE');
            $s->execute(['id'=>$proposalId,'account_id'=>$accountId]);
            $p=$s->fetch();
            if(!$p) throw new RuntimeException('That proposal is unavailable.');
            if($p['proposal_type']!=='quest.create') throw new RuntimeException('Quests cannot execute that proposal type.');
            if($p['status']!=='approved' || (int)$p['approved_version']!==(int)$p['version']) throw new RuntimeException('Review and approve the current proposal version first.');
            if($p['expires_at'] && strtotime((string)$p['expires_at'])<time()) throw new RuntimeException('That proposal expired and must be reviewed again.');
            $payload=json_decode((string)$p['proposed_payload_json'],true,512,JSON_THROW_ON_ERROR);
            $title=trim((string)($payload['title']??''));$notes=trim((string)($payload['notes']??''));
            if($title==='' || mb_strlen($title)>180) throw new RuntimeException('The proposed Quest title is no longer valid.');
            if(mb_strlen($notes)>4000) throw new RuntimeException('The proposed Quest notes are too long.');

            $questId=self::uuid();$occurrenceId=self::uuid();$now=gmdate('Y-m-d H:i:s');
            $pdo->prepare('INSERT INTO quests (id,account_id,title,description,quest_type,status,lifecycle_status,created_at,updated_at) VALUES (:id,:account_id,:title,:description,"action","active","active",:created_at,:updated_at)')->execute(['id'=>$questId,'account_id'=>$accountId,'title'=>$title,'description'=>$notes,'created_at'=>$now,'updated_at'=>$now]);
            $pdo->prepare('INSERT INTO quest_occurrences (id,quest_id,account_id,scheduled_for,status,available_at,created_at,updated_at) VALUES (:id,:quest_id,:account_id,CURRENT_DATE(),"available",:available_at,:created_at,:updated_at)')->execute(['id'=>$occurrenceId,'quest_id'=>$questId,'account_id'=>$accountId,'available_at'=>$now,'created_at'=>$now,'updated_at'=>$now]);
            $pdo->prepare('INSERT INTO companion_execution_receipts (proposal_id,account_id,proposal_version,owner_module,record_id,executed_at) VALUES (:proposal_id,:account_id,:proposal_version,"Quests",:record_id,:executed_at)')->execute(['proposal_id'=>$proposalId,'account_id'=>$accountId,'proposal_version'=>(int)$p['version'],'record_id'=>$questId,'executed_at'=>$now]);
            $pdo->prepare('UPDATE companion_proposals SET status="executed",executed_module="Quests",executed_record_id=:record_id,executed_at=:executed_at,failure_message=NULL,updated_at=:updated_at WHERE id=:id')->execute(['record_id'=>$questId,'executed_at'=>$now,'updated_at'=>$now,'id'=>$proposalId]);
            $pdo->prepare('INSERT INTO audit_log (id,account_id,action,subject_type,subject_id,occurred_at) VALUES (:id,:account_id,"companion.proposal.executed","quest",:subject_id,:occurred_at)')->execute(['id'=>self::uuid(),'account_id'=>$accountId,'subject_id'=>$questId,'occurred_at'=>$now]);
            return $questId;
        });
    }

    private static function uuid(): string { $b=random_bytes(16);$b[6]=chr((ord($b[6])&15)|64);$b[8]=chr((ord($b[8])&63)|128);return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($b),4)); }
}