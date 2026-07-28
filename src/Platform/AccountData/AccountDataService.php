<?php

declare(strict_types=1);
namespace Koravik\Platform\AccountData;

use Koravik\Platform\Database\Database;
use PDO;
use RuntimeException;

final class AccountDataService
{
    private const OWNERS=['Quests','Chronicle','Companion','Worlds','Platform'];
    public function __construct(private readonly Database $database) {}

    public function requestExport(string $accountId,string $format='json'): string
    {
        if(!in_array($format,['json','html'],true))throw new RuntimeException('Choose JSON or HTML export.');$id=self::uuid();
        $this->database->transaction(function(PDO $pdo) use($id,$accountId,$format): void {$pdo->prepare('INSERT INTO account_exports (id,account_id,status,format,requested_at) VALUES (:id,:account_id,"requested",:format,UTC_TIMESTAMP())')->execute(['id'=>$id,'account_id'=>$accountId,'format'=>$format]);$this->audit($pdo,$accountId,'account.export.requested',$id);});
        $this->generateExport($accountId,$id);return $id;
    }
    public function generateExport(string $accountId,string $id): void
    {
        $pdo=$this->database->pdo();$tables=['account'=>'SELECT id,email,display_name,role,status,created_at,updated_at FROM platform_accounts WHERE id=:account_id','settings'=>'SELECT * FROM account_settings WHERE account_id=:account_id','quests'=>'SELECT * FROM quests WHERE account_id=:account_id','occurrences'=>'SELECT * FROM quest_occurrences WHERE account_id=:account_id','chronicle'=>'SELECT * FROM chronicle_entries WHERE account_id=:account_id AND deleted_at IS NULL','companion_proposals'=>'SELECT id,proposal_type,status,version,title,reasoning,source_context,owning_module,consequence,created_at,updated_at FROM companion_proposals WHERE account_id=:account_id','companion_memories'=>'SELECT * FROM companion_memories WHERE account_id=:account_id AND status<>"deleted"','world_installations'=>'SELECT * FROM world_installations WHERE account_id=:account_id','notifications'=>'SELECT * FROM notifications WHERE account_id=:account_id','audit'=>'SELECT action,subject_type,subject_id,occurred_at FROM audit_log WHERE account_id=:account_id ORDER BY occurred_at'];$data=[];
        foreach($tables as $key=>$sql){$s=$pdo->prepare($sql);$s->execute(['account_id'=>$accountId]);$data[$key]=$s->fetchAll();}
        $manifest=['generated_at'=>gmdate(DATE_ATOM),'account_id'=>$accountId,'sections'=>array_keys($data),'excluded'=>['password hashes','session secrets','encryption material','other accounts data']];
        $pdo->prepare('UPDATE account_exports SET status="completed",manifest_json=:manifest,export_json=:export,completed_at=UTC_TIMESTAMP(),expires_at=DATE_ADD(UTC_TIMESTAMP(),INTERVAL 7 DAY) WHERE id=:id AND account_id=:account_id')->execute(['manifest'=>json_encode($manifest,JSON_THROW_ON_ERROR),'export'=>json_encode($data,JSON_THROW_ON_ERROR),'id'=>$id,'account_id'=>$accountId]);
    }
    public function export(string $accountId,string $id): array {$s=$this->database->pdo()->prepare('SELECT * FROM account_exports WHERE id=:id AND account_id=:account_id AND status="completed" AND expires_at>UTC_TIMESTAMP()');$s->execute(['id'=>$id,'account_id'=>$accountId]);$r=$s->fetch();if(!$r)throw new RuntimeException('Export unavailable or expired.');return $r;}
    public function requestClosure(string $accountId,string $phrase): string
    {
        if(trim($phrase)!=='CLOSE MY ACCOUNT')throw new RuntimeException('Type CLOSE MY ACCOUNT exactly.');$id=self::uuid();
        $this->database->transaction(function(PDO $pdo) use($id,$accountId,$phrase): void {$open=$pdo->prepare('SELECT id FROM account_closures WHERE account_id=:account_id AND status IN ("pending_cancellation","processing")');$open->execute(['account_id'=>$accountId]);if($open->fetchColumn())throw new RuntimeException('An account closure is already pending.');$pdo->prepare('INSERT INTO account_closures (id,account_id,status,confirmation_phrase,requested_at,cancellable_until) VALUES (:id,:account_id,"pending_cancellation",:phrase,UTC_TIMESTAMP(),DATE_ADD(UTC_TIMESTAMP(),INTERVAL 7 DAY))')->execute(['id'=>$id,'account_id'=>$accountId,'phrase'=>$phrase]);foreach(self::OWNERS as $owner)$pdo->prepare('INSERT INTO account_closure_steps (closure_id,owner_module,status) VALUES (:closure,:owner,"pending")')->execute(['closure'=>$id,'owner'=>$owner]);$this->audit($pdo,$accountId,'account.closure.requested',$id);});return $id;
    }
    public function cancelClosure(string $accountId,string $id): void {$s=$this->database->pdo()->prepare('UPDATE account_closures SET status="cancelled" WHERE id=:id AND account_id=:account_id AND status="pending_cancellation" AND cancellable_until>UTC_TIMESTAMP()');$s->execute(['id'=>$id,'account_id'=>$accountId]);if($s->rowCount()!==1)throw new RuntimeException('Closure can no longer be cancelled.');}
    public function processDue(int $limit=10): int
    {
        $s=$this->database->pdo()->prepare('SELECT id,account_id FROM account_closures WHERE status="pending_cancellation" AND cancellable_until<=UTC_TIMESTAMP() ORDER BY requested_at LIMIT :limit');$s->bindValue('limit',$limit,PDO::PARAM_INT);$s->execute();$rows=$s->fetchAll();foreach($rows as $row)$this->processClosure((string)$row['id'],(string)$row['account_id']);return count($rows);
    }
    private function processClosure(string $closureId,string $accountId): void
    {
        $this->database->transaction(function(PDO $pdo) use($closureId,$accountId): void {$pdo->prepare('UPDATE account_closures SET status="processing",processing_started_at=UTC_TIMESTAMP() WHERE id=:id')->execute(['id'=>$closureId]);
            $pdo->prepare('DELETE FROM quests WHERE account_id=:account_id')->execute(['account_id'=>$accountId]);$this->step($pdo,$closureId,'Quests','deleted account-owned Quest records');
            $pdo->prepare('DELETE FROM chronicle_entries WHERE account_id=:account_id')->execute(['account_id'=>$accountId]);$this->step($pdo,$closureId,'Chronicle','deleted eligible Chronicle entries');
            $pdo->prepare('DELETE FROM companion_memories WHERE account_id=:account_id')->execute(['account_id'=>$accountId]);$pdo->prepare('UPDATE companion_proposals SET request_text="[removed]",proposed_payload_json=JSON_OBJECT(),reasoning="[removed]",source_context="[removed]" WHERE account_id=:account_id')->execute(['account_id'=>$accountId]);$this->step($pdo,$closureId,'Companion','removed memories and private proposal content');
            $pdo->prepare('DELETE FROM world_installations WHERE account_id=:account_id')->execute(['account_id'=>$accountId]);$this->step($pdo,$closureId,'Worlds','removed account-specific World installations and state');
            $pdo->prepare('DELETE FROM auth_credentials WHERE account_id=:account_id')->execute(['account_id'=>$accountId]);$pdo->prepare('UPDATE platform_accounts SET email=:email,display_name="Closed account",status="closed",updated_at=UTC_TIMESTAMP() WHERE id=:account_id')->execute(['email'=>'closed+'.$accountId.'@invalid.local','account_id'=>$accountId]);$this->step($pdo,$closureId,'Platform','revoked credentials and anonymized identity');
            $ledger=['completed_at'=>gmdate(DATE_ATOM),'retained'=>['minimal audit evidence','closure receipt'],'deleted_or_anonymized'=>self::OWNERS];$pdo->prepare('UPDATE account_closures SET status="completed",completed_at=UTC_TIMESTAMP(),retention_ledger_json=:ledger WHERE id=:id')->execute(['ledger'=>json_encode($ledger,JSON_THROW_ON_ERROR),'id'=>$closureId]);$this->audit($pdo,$accountId,'account.closure.completed',$closureId);});
    }
    private function step(PDO $pdo,string $closure,string $owner,string $summary): void {$pdo->prepare('UPDATE account_closure_steps SET status="completed",outcome_summary=:summary,processed_at=UTC_TIMESTAMP() WHERE closure_id=:closure AND owner_module=:owner')->execute(['summary'=>$summary,'closure'=>$closure,'owner'=>$owner]);}
    private function audit(PDO $pdo,string $accountId,string $action,string $subject): void {$pdo->prepare('INSERT INTO audit_log (id,account_id,action,subject_type,subject_id,occurred_at) VALUES (:id,:account_id,:action,"account_data",:subject,UTC_TIMESTAMP())')->execute(['id'=>self::uuid(),'account_id'=>$accountId,'action'=>$action,'subject'=>$subject]);}
    private static function uuid(): string {$b=random_bytes(16);$b[6]=chr((ord($b[6])&15)|64);$b[8]=chr((ord($b[8])&63)|128);return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($b),4));}
}
