<?php

declare(strict_types=1);
namespace Koravik\Platform\Experience;

use Koravik\Platform\Database\Database;
use PDO;
use RuntimeException;

final class ChronicleProposalExecutor
{
    public function __construct(private readonly Database $database) {}

    public function execute(string $accountId,string $proposalId): string
    {
        return $this->database->transaction(function(PDO $pdo) use($accountId,$proposalId): string {
            $receipt=$pdo->prepare('SELECT record_id FROM companion_execution_receipts WHERE proposal_id=:proposal_id LIMIT 1');$receipt->execute(['proposal_id'=>$proposalId]);$existing=$receipt->fetchColumn();if($existing) return (string)$existing;
            $s=$pdo->prepare('SELECT id,status,proposal_type,version,approved_version,expires_at,proposed_payload_json FROM companion_proposals WHERE id=:id AND account_id=:account_id FOR UPDATE');$s->execute(['id'=>$proposalId,'account_id'=>$accountId]);$p=$s->fetch();
            if(!$p) throw new RuntimeException('That proposal is unavailable.');
            if($p['proposal_type']!=='chronicle.reflection.create') throw new RuntimeException('Chronicle cannot execute that proposal type.');
            if($p['status']!=='approved' || (int)$p['approved_version']!==(int)$p['version']) throw new RuntimeException('Review and approve the current reflection version first.');
            if($p['expires_at'] && strtotime((string)$p['expires_at'])<time()) throw new RuntimeException('That reflection proposal expired and must be reviewed again.');
            $payload=json_decode((string)$p['proposed_payload_json'],true,512,JSON_THROW_ON_ERROR);$title=trim((string)($payload['title']??''));$body=trim((string)($payload['body']??''));
            if($title==='' || mb_strlen($title)>180) throw new RuntimeException('The reflection title is no longer valid.');if($body==='' || mb_strlen($body)>4000) throw new RuntimeException('The reflection text is no longer valid.');
            $entryId=self::uuid();$now=gmdate('Y-m-d H:i:s');
            $pdo->prepare('INSERT INTO chronicle_entries (id,account_id,entry_type,title,body,status,created_at) VALUES (:id,:account_id,"reflection",:title,:body,"active",:created_at)')->execute(['id'=>$entryId,'account_id'=>$accountId,'title'=>$title,'body'=>$body,'created_at'=>$now]);
            $pdo->prepare('INSERT INTO companion_execution_receipts (proposal_id,account_id,proposal_version,owner_module,record_id,executed_at) VALUES (:proposal_id,:account_id,:proposal_version,"Chronicle",:record_id,:executed_at)')->execute(['proposal_id'=>$proposalId,'account_id'=>$accountId,'proposal_version'=>(int)$p['version'],'record_id'=>$entryId,'executed_at'=>$now]);
            $pdo->prepare('UPDATE companion_proposals SET status="executed",executed_module="Chronicle",executed_record_id=:record_id,executed_at=:executed_at,failure_message=NULL,updated_at=:updated_at WHERE id=:id')->execute(['record_id'=>$entryId,'executed_at'=>$now,'updated_at'=>$now,'id'=>$proposalId]);
            $pdo->prepare('INSERT INTO audit_log (id,account_id,action,subject_type,subject_id,occurred_at) VALUES (:id,:account_id,"companion.proposal.executed","chronicle_entry",:subject_id,:occurred_at)')->execute(['id'=>self::uuid(),'account_id'=>$accountId,'subject_id'=>$entryId,'occurred_at'=>$now]);
            return $entryId;
        });
    }

    private static function uuid(): string { $b=random_bytes(16);$b[6]=chr((ord($b[6])&15)|64);$b[8]=chr((ord($b[8])&63)|128);return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($b),4)); }
}