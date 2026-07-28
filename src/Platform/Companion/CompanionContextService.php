<?php

declare(strict_types=1);
namespace Koravik\Platform\Companion;

use Koravik\Platform\Database\Database;
use PDO;
use RuntimeException;

final class CompanionContextService
{
    public const PERMISSIONS=['quest.selected','chronicle.selected','pillars.summary','accessibility.preferences','companion.memory'];
    public function __construct(private readonly Database $database) {}

    public function permissions(string $accountId): array
    {
        $this->ensure($accountId);$s=$this->database->pdo()->prepare('SELECT context_key,allowed,updated_at FROM companion_context_permissions WHERE account_id=:account_id ORDER BY context_key');$s->execute(['account_id'=>$accountId]);return $s->fetchAll();
    }
    public function savePermissions(string $accountId,array $allowed): void
    {
        $allowed=array_map('strval',$allowed);$this->database->transaction(function(PDO $pdo) use($accountId,$allowed): void {foreach(self::PERMISSIONS as $key)$pdo->prepare('INSERT INTO companion_context_permissions (account_id,context_key,allowed,updated_at) VALUES (:account_id,:key,:allowed,UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE allowed=VALUES(allowed),updated_at=UTC_TIMESTAMP()')->execute(['account_id'=>$accountId,'key'=>$key,'allowed'=>in_array($key,$allowed,true)?1:0]);$this->audit($pdo,$accountId,'companion.permissions.updated',$accountId);});
    }
    public function remember(string $accountId,string $text,string $provenance): string
    {
        $text=trim($text);$provenance=trim($provenance);if($text===''||mb_strlen($text)>500) throw new RuntimeException('Memory must be 1 to 500 characters.');
        if(!$this->allowed($accountId,'companion.memory')) throw new RuntimeException('Enable approved Companion memory first.');
        $id=self::uuid();$this->database->transaction(function(PDO $pdo) use($id,$accountId,$text,$provenance): void {$pdo->prepare('INSERT INTO companion_memories (id,account_id,memory_text,provenance,status,created_at,updated_at) VALUES (:id,:account_id,:text,:provenance,"active",UTC_TIMESTAMP(),UTC_TIMESTAMP())')->execute(['id'=>$id,'account_id'=>$accountId,'text'=>$text,'provenance'=>$provenance?:'Approved directly by the player.']);$this->audit($pdo,$accountId,'companion.memory.approved',$id);});return $id;
    }
    public function memories(string $accountId): array {$s=$this->database->pdo()->prepare('SELECT * FROM companion_memories WHERE account_id=:account_id ORDER BY created_at DESC');$s->execute(['account_id'=>$accountId]);return $s->fetchAll();}
    public function setMemoryStatus(string $accountId,string $id,string $status): void {if(!in_array($status,['active','disabled','deleted'],true)) throw new RuntimeException('Choose a valid memory state.');$s=$this->database->pdo()->prepare('UPDATE companion_memories SET status=:status,updated_at=UTC_TIMESTAMP() WHERE id=:id AND account_id=:account_id');$s->execute(['status'=>$status,'id'=>$id,'account_id'=>$accountId]);if($s->rowCount()!==1) throw new RuntimeException('Memory not found.');}
    public function recordSelectedContext(string $accountId,string $module,string $type,?string $sourceId,string $summary): string
    {
        $key=$module==='Quests'?'quest.selected':($module==='Chronicle'?'chronicle.selected':'pillars.summary');if(!$this->allowed($accountId,$key)) throw new RuntimeException('That Companion context permission is not enabled.');
        $summary=trim($summary);if($summary===''||mb_strlen($summary)>1000) throw new RuntimeException('Context summary must be 1 to 1000 characters.');$id=self::uuid();$this->database->pdo()->prepare('INSERT INTO companion_context_uses (id,account_id,source_module,source_type,source_id,minimized_summary,use_scope,created_at) VALUES (:id,:account_id,:module,:type,:source_id,:summary,"once",UTC_TIMESTAMP())')->execute(['id'=>$id,'account_id'=>$accountId,'module'=>$module,'type'=>$type,'source_id'=>$sourceId,'summary'=>$summary]);return $id;
    }
    private function allowed(string $accountId,string $key): bool {$this->ensure($accountId);$s=$this->database->pdo()->prepare('SELECT allowed FROM companion_context_permissions WHERE account_id=:account_id AND context_key=:key');$s->execute(['account_id'=>$accountId,'key'=>$key]);return (bool)$s->fetchColumn();}
    private function ensure(string $accountId): void {foreach(self::PERMISSIONS as $key)$this->database->pdo()->prepare('INSERT IGNORE INTO companion_context_permissions (account_id,context_key,allowed,updated_at) VALUES (:account_id,:key,0,UTC_TIMESTAMP())')->execute(['account_id'=>$accountId,'key'=>$key]);}
    private function audit(PDO $pdo,string $accountId,string $action,string $subject): void {$pdo->prepare('INSERT INTO audit_log (id,account_id,action,subject_type,subject_id,occurred_at) VALUES (:id,:account_id,:action,"companion",:subject,UTC_TIMESTAMP())')->execute(['id'=>self::uuid(),'account_id'=>$accountId,'action'=>$action,'subject'=>$subject]);}
    private static function uuid(): string {$b=random_bytes(16);$b[6]=chr((ord($b[6])&15)|64);$b[8]=chr((ord($b[8])&63)|128);return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($b),4));}
}
