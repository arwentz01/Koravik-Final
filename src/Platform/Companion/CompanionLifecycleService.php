<?php

declare(strict_types=1);
namespace Koravik\Platform\Companion;

use Koravik\Platform\Database\Database;
use PDO;
use RuntimeException;

final class CompanionLifecycleService
{
    public function __construct(private readonly Database $database) {}

    public function expireDue(string $accountId): int
    {
        $s=$this->database->pdo()->prepare('UPDATE companion_proposals SET status="expired",updated_at=UTC_TIMESTAMP() WHERE account_id=:account_id AND status IN ("awaiting_approval","approved") AND expires_at IS NOT NULL AND expires_at<UTC_TIMESTAMP()');
        $s->execute(['account_id'=>$accountId]);
        return $s->rowCount();
    }

    public function clarify(string $accountId,string $id,string $question): void
    {
        $question=trim($question);
        if($question==='' || mb_strlen($question)>1000) throw new RuntimeException('Ask one clarification question in 1 to 1000 characters.');
        $this->database->transaction(function(PDO $pdo) use($accountId,$id,$question): void {
            $p=$pdo->prepare('SELECT status FROM companion_proposals WHERE id=:id AND account_id=:account_id FOR UPDATE');$p->execute(['id'=>$id,'account_id'=>$accountId]);$status=$p->fetchColumn();
            if(!$status || in_array($status,['dismissed','executed'],true)) throw new RuntimeException('That proposal cannot be clarified.');
            $answer='Companion clarification: the proposal changes only the named destination record after you approve the current version and choose execution.';
            $pdo->prepare('UPDATE companion_proposals SET clarification_text=:text,updated_at=UTC_TIMESTAMP() WHERE id=:id')->execute(['text'=>$question."\n\n".$answer,'id'=>$id]);
            $this->audit($pdo,$accountId,'companion.proposal.clarified',$id);
            $this->event($pdo,$accountId,'Companion.ProposalClarified',$id,['proposal_id'=>$id]);
        });
    }

    public function renew(string $accountId,string $id): void
    {
        $this->database->transaction(function(PDO $pdo) use($accountId,$id): void {
            $p=$pdo->prepare('SELECT status FROM companion_proposals WHERE id=:id AND account_id=:account_id FOR UPDATE');$p->execute(['id'=>$id,'account_id'=>$accountId]);$status=$p->fetchColumn();
            if(!in_array($status,['expired','failed'],true)) throw new RuntimeException('Only expired or failed proposals can be renewed.');
            $pdo->prepare('UPDATE companion_proposals SET status="awaiting_approval",version=version+1,approved_version=NULL,approved_at=NULL,failure_code=NULL,failure_message=NULL,expires_at=DATE_ADD(UTC_TIMESTAMP(),INTERVAL 14 DAY),updated_at=UTC_TIMESTAMP() WHERE id=:id')->execute(['id'=>$id]);
            $this->audit($pdo,$accountId,'companion.proposal.renewed',$id);
            $this->event($pdo,$accountId,'Companion.ProposalRevised',$id,['proposal_id'=>$id]);
        });
    }

    public function recordFailure(string $accountId,string $id,string $code,string $message): void
    {
        $this->database->transaction(function(PDO $pdo) use($accountId,$id,$code,$message): void {
            $pdo->prepare('UPDATE companion_proposals SET status="failed",failure_code=:code,failure_message=:message,execution_attempts=execution_attempts+1,last_attempt_at=UTC_TIMESTAMP(),updated_at=UTC_TIMESTAMP() WHERE id=:id AND account_id=:account_id AND status="approved"')->execute(['code'=>$code,'message'=>$message,'id'=>$id,'account_id'=>$accountId]);
            $this->audit($pdo,$accountId,'companion.proposal.execution_failed',$id);
            $this->event($pdo,$accountId,'Companion.ProposalExecutionFailed',$id,['proposal_id'=>$id,'destination'=>'owner']);
        });
    }

    private function event(PDO $pdo,string $accountId,string $name,string $id,array $payload): void
    {
        $now=gmdate('Y-m-d H:i:s');
        $pdo->prepare('INSERT INTO platform_outbox (id,event_name,event_version,account_id,payload_json,status,attempts,available_at,occurred_at,created_at) VALUES (:id,:name,1,:account_id,:payload,"pending",0,:available,:occurred,:created)')->execute(['id'=>self::uuid(),'name'=>$name,'account_id'=>$accountId,'payload'=>json_encode($payload,JSON_THROW_ON_ERROR),'available'=>$now,'occurred'=>$now,'created'=>$now]);
    }
    private function audit(PDO $pdo,string $accountId,string $action,string $subject): void { $pdo->prepare('INSERT INTO audit_log (id,account_id,action,subject_type,subject_id,occurred_at) VALUES (:id,:account_id,:action,"companion_proposal",:subject,UTC_TIMESTAMP())')->execute(['id'=>self::uuid(),'account_id'=>$accountId,'action'=>$action,'subject'=>$subject]); }
    private static function uuid(): string { $b=random_bytes(16);$b[6]=chr((ord($b[6])&15)|64);$b[8]=chr((ord($b[8])&63)|128);return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($b),4)); }
}
